<?php

namespace App\Services\Search;

use App\Enums\Condition;
use App\Enums\ConversationStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\MessageRole;
use App\Enums\TriState;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\SearchRequest;
use App\Services\Ai\AiClient;
use Illuminate\Support\Facades\Log;

/**
 * Drives the AI<->owner negotiation for one Conversation.
 *
 * Real mode (userbot transport): each owner message comes in via $ownerMessage;
 * we log it, ask Claude for the next short polite Uzbek reply, and decide when
 * the conversation has concluded (agreed / declined / no_response).
 *
 * Simulation mode (or when Claude is disabled): we ALSO role-play the owner —
 * a realistic owner who agrees when the listing reasonably matches the search,
 * otherwise declines — over a couple of turns, then concludes. Fully offline.
 */
class OwnerNegotiator
{
    /** Hard stop so a runaway conversation can never loop forever. */
    private const MAX_AI_TURNS = 4;

    public function __construct(private readonly AiClient $ai)
    {
    }

    /**
     * @return array{reply: ?string, done: bool, outcome: ?string}
     */
    public function nextMessage(Conversation $c, ?string $ownerMessage): array
    {
        $c->loadMissing(['listing', 'searchRequest']);

        if ($ownerMessage !== null && trim($ownerMessage) !== '') {
            $this->log($c, MessageRole::Owner, trim($ownerMessage));
            $this->touchStatus($c, ConversationStatus::Replied);
        }

        $simulate = $c->is_simulation || ! $this->ai->enabled();

        return $simulate
            ? $this->simulatedTurn($c)
            : $this->liveTurn($c);
    }

    // -------------------------------------------------------------- Live mode

    private function liveTurn(Conversation $c): array
    {
        // Retry transient DeepSeek failures. We do NOT fall back to simulatedTurn
        // here: this is a REAL conversation with a real person, and simulatedTurn
        // would fabricate the owner's replies. Instead, on total failure we send a
        // safe holding message and keep the conversation open for the next turn.
        $data = null;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $data = $this->ai->structured(
                    $this->history($c),
                    $this->liveSchema(),
                    $this->liveSystem($c),
                );
                break;
            } catch (\Throwable $e) {
                Log::warning('OwnerNegotiator live turn attempt failed', [
                    'conversation_id' => $c->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($data === null) {
            return $this->liveFallback($c);
        }

        $reply = isset($data['message']) && is_string($data['message']) && trim($data['message']) !== ''
            ? trim($data['message'])
            : null;
        $done = (bool) ($data['done'] ?? false);
        $outcome = $this->normalizeOutcome($data['outcome'] ?? null);

        // Guarantee the bargaining policy regardless of how obedient the model is.
        [$reply, $done, $outcome] = $this->enforceBargaining($c, $reply, $done, $outcome);

        if ($reply !== null) {
            $this->log($c, MessageRole::Ai, $reply);
            $this->touchStatus($c, ConversationStatus::Contacted);
        }

        if ($done) {
            $this->conclude($c, $outcome ?? 'no_response');
        }

        return ['reply' => $reply, 'done' => $done, 'outcome' => $done ? ($outcome ?? 'no_response') : null];
    }

    /** DeepSeek unavailable: send a safe message and keep the real conversation open. */
    private function liveFallback(Conversation $c): array
    {
        $hasOpening = $c->messages->where('role', MessageRole::Ai)->count() > 0;
        $budget = $c->searchRequest?->price_max;

        if (! $hasOpening) {
            $reply = 'Assalomu alaykum! E’loningiz bo‘yicha qiziqyapman — hali mavjudmi?';
        } elseif ($budget) {
            $reply = "Mening byudjetim {$budget}\$ atrofida. Shu narxda kelishsak, men "
                .'ishonchli va uzoq muddatli ijarachi bo‘laman, to‘lovni o‘z vaqtida qilaman.';
        } else {
            $reply = 'Narx bo‘yicha kelishsak bo‘ladimi? Men ishonchli ijarachi bo‘laman.';
        }

        $this->log($c, MessageRole::Ai, $reply);
        $this->touchStatus($c, ConversationStatus::Contacted);

        return ['reply' => $reply, 'done' => false, 'outcome' => null];
    }

    /**
     * Enforce the price-bargaining policy in code: keep haggling (≥3-4 rounds) and
     * never accept a price above the renter's budget. Returns [reply, done, outcome].
     */
    private function enforceBargaining(Conversation $c, ?string $reply, bool $done, ?string $outcome): array
    {
        $budget = $c->searchRequest?->price_max;
        if (! $budget || ! $done) {
            return [$reply, $done, $outcome];
        }

        $priorOffers = $c->messages->where('role', MessageRole::Ai)->count();
        $ownerPrice = $this->lastOwnerPrice($c);
        $priceAboveBudget = $ownerPrice !== null && $ownerPrice > $budget;

        // Hard stop after many rounds so we never loop forever.
        if ($priorOffers >= 6) {
            if ($priceAboveBudget && $outcome === 'agreed') {
                return [$reply, true, 'declined'];
            }

            return [$reply, $done, $outcome];
        }

        $tooEarlyDecline = $outcome === 'declined' && $priorOffers < 4;
        $agreeAboveBudget = $outcome === 'agreed' && $priceAboveBudget;

        if ($tooEarlyDecline || $agreeAboveBudget) {
            $reply = "Tushundim. Lekin byudjetim {$budget}\$ atrofida. Iltimos, shu narxda "
                ."kelishsak bo‘ladimi? Men uzoq muddatli, ishonchli ijarachiman va to‘lovni "
                .'o‘z vaqtida qilaman.';

            return [$reply, false, null];
        }

        return [$reply, $done, $outcome];
    }

    private function lastOwnerPrice(Conversation $c): ?int
    {
        $last = $c->messages->where('role', MessageRole::Owner)->last();
        if (! $last) {
            return null;
        }
        if (preg_match_all('/\d{2,5}/', (string) $last->content, $m) && ! empty($m[0])) {
            return (int) end($m[0]);
        }

        return null;
    }

    private function liveSystem(Conversation $c): string
    {
        $criteria = $this->criteriaSummary($c);
        $budget = $c->searchRequest?->price_max;
        $budgetLine = $budget
            ? "The renter's MAXIMUM budget is \${$budget} per month."
            : 'The renter wants the best possible price.';

        $priorOffers = $c->messages->where('role', MessageRole::Ai)->count();

        return <<<TXT
You are a polite renter's assistant negotiating in UZBEK with an apartment owner
on Telegram. Keep every message short (1-3 sentences), warm and respectful.

CONTINUITY (very important): the full conversation so far is provided as message
history. GREET ONLY ONCE — in your very first message. If there is already any
previous message from you in the history, do NOT say "Assalomu alaykum" again, do
NOT re-introduce yourself, and do NOT repeat questions you already asked. Simply
continue the dialogue naturally from the owner's last reply.

{$budgetLine}
So far you have sent {$priorOffers} message(s) in this conversation.

Your goal:
1. Confirm the listing is still available and matches the renter's needs below.
2. PRICE BARGAINING — if the owner's price is ABOVE the budget, bargain it DOWN in
   the renter's favour. Each round make a concrete counter-offer near the budget
   with a friendly reason (reliable long-term tenant, pays on time, moves in soon).

HARD BARGAINING RULES (follow exactly):
- You must make AT LEAST 3 counter-offers before you are EVER allowed to set
  outcome="declined". You have made about {$priorOffers} message(s) so far, so if
  that is fewer than 4, you are STILL BARGAINING.
- When the owner replies with a LOWER number than before (e.g. 600 then 550),
  that means they ARE negotiating — this is GOOD. React positively ("juda yaxshi!")
  and immediately counter again toward the budget. This is NOT a refusal — NEVER
  decline or say goodbye in this case.
- While you are still bargaining, you MUST keep "done": false and "outcome": null.
- DO NOT mark "agreed" while the price is still ABOVE the budget. If the owner says
  a number that is still higher than the budget (e.g. 550 when budget is 400), that
  is a counter-offer, NOT acceptance — keep bargaining toward the budget.
- Set "done": true ONLY when: the owner clearly accepts a price AT OR BELOW the
  budget ("outcome": "agreed"), OR you have already made 3-4 counter-offers and the
  owner gives a firm final refusal to come down ("outcome": "declined").
- If the price is already within budget, just confirm → "agreed".

Output JSON per the schema: "message" = your next short Uzbek message to the owner
(empty only when closing), "done" = boolean, "outcome" = agreed / declined /
no_response.

Renter is looking for:
{$criteria}
TXT;
    }

    private function liveSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'message' => ['type' => ['string', 'null']],
                'done' => ['type' => 'boolean'],
                'outcome' => ['type' => ['string', 'null'], 'enum' => ['agreed', 'declined', 'no_response', null]],
            ],
            'required' => ['message', 'done', 'outcome'],
        ];
    }

    /**
     * Build the Anthropic messages array from the stored conversation. Owner
     * turns are "user", our AI turns are "assistant".
     */
    private function history(Conversation $c): array
    {
        $messages = [];
        foreach ($c->messages as $m) {
            if ($m->role === MessageRole::System) {
                continue;
            }
            $messages[] = [
                'role' => $m->role === MessageRole::Ai ? 'assistant' : 'user',
                'content' => $m->content,
            ];
        }

        if (empty($messages)) {
            // Kick-off: prompt the model to write the opening outreach.
            $messages[] = ['role' => 'user', 'content' => '[Boshlanish] Uy egasiga birinchi xabarni yozing.'];
        }

        return $messages;
    }

    // --------------------------------------------------------- Simulation mode

    private function simulatedTurn(Conversation $c): array
    {
        $aiTurns = $c->messages->where('role', MessageRole::Ai)->count();
        $opening = $aiTurns === 0;

        $listing = $c->listing;
        $agree = $this->ownerAgrees($c);

        if ($opening) {
            $aiText = $this->aiOpening($listing);
            $this->log($c, MessageRole::Ai, $aiText);
            $this->touchStatus($c, ConversationStatus::Contacted, contacted: true);

            $ownerText = $agree
                ? 'Assalomu alaykum! Ha, kvartira hali bo‘sh. Qanday savollaringiz bor edi?'
                : 'Assalomu alaykum. Afsus, shartlaringiz bizga to‘g‘ri kelmaydi shekilli.';
            $this->log($c, MessageRole::Owner, $ownerText);
            $this->touchStatus($c, ConversationStatus::Replied);

            return ['reply' => $aiText, 'done' => false, 'outcome' => null];
        }

        // Closing turn: AI confirms the conditions, owner gives a final answer.
        $aiText = $agree
            ? 'Ajoyib! Demak shartlar mos kelyapti. Ko‘rishishni qachon belgilasak bo‘ladi?'
            : 'Tushunarli, vaqtingiz uchun rahmat. Sizga mos ijarachi topilishini tilayman!';
        $this->log($c, MessageRole::Ai, $aiText);

        $ownerText = $agree
            ? 'Yaxshi, men roziman. Bemalol keling, kelishamiz. 🤝'
            : 'Kechirasiz, bu safar bo‘lmaydi. Omad tilayman.';
        $this->log($c, MessageRole::Owner, $ownerText);

        $outcome = $agree ? 'agreed' : 'declined';
        $this->conclude($c, $outcome);

        return ['reply' => $aiText, 'done' => true, 'outcome' => $outcome];
    }

    private function aiOpening(?Listing $listing): string
    {
        $title = $listing?->title ? mb_substr($listing->title, 0, 60) : 'e’loningiz';

        return "Assalomu alaykum! \"{$title}\" bo‘yicha yozyapman. Kvartira hali ijaraga bo‘shmi? "
            .'Bir nechta shartlarni aniqlashtirsak bo‘ladimi?';
    }

    /**
     * Deterministic-but-realistic owner decision: agreement chance is weighted
     * by how well the listing fits the search request; the dice roll is seeded
     * from the conversation id so repeated calls stay consistent.
     */
    private function ownerAgrees(Conversation $c): bool
    {
        $chance = $this->agreeChance($c->searchRequest, $c->listing);
        $roll = (crc32('boshpana-owner-'.$c->id) % 1000) / 1000;

        return $roll <= $chance;
    }

    private function agreeChance(?SearchRequest $r, ?Listing $l): float
    {
        $chance = 0.7;
        if (! $r || ! $l) {
            return $chance;
        }

        if ($r->gender && $r->gender !== Gender::Any && $l->gender_pref
            && $l->gender_pref !== Gender::Any && $l->gender_pref !== $r->gender) {
            $chance -= 0.4;
        }
        if ($r->marital_status && $r->marital_status !== MaritalStatus::Any && $l->marital_pref
            && $l->marital_pref !== MaritalStatus::Any && $l->marital_pref->value !== $r->marital_status->value) {
            $chance -= 0.3;
        }
        if ($r->has_commission === TriState::No && $l->has_commission === true) {
            $chance -= 0.2;
        }
        if ($r->price_max && $l->price && $l->price > $r->price_max) {
            $chance -= 0.2;
        }
        if ($r->condition && $r->condition !== Condition::Any && $l->condition
            && $l->condition !== Condition::Any && $l->condition !== $r->condition) {
            $chance -= 0.1;
        }

        return max(0.05, min(0.95, $chance));
    }

    // -------------------------------------------------------------- Persistence

    private function log(Conversation $c, MessageRole $role, string $content): void
    {
        Message::create([
            'conversation_id' => $c->id,
            'role' => $role->value,
            'content' => $content,
            'sent_at' => now(),
        ]);

        // Keep the in-memory relation fresh for turn counting within one run.
        $c->load('messages');
    }

    private function touchStatus(Conversation $c, ConversationStatus $status, bool $contacted = false): void
    {
        if ($c->status->isTerminal()) {
            return;
        }

        $changes = ['status' => $status->value];
        if ($contacted && ! $c->contacted_at) {
            $changes['contacted_at'] = now();
        }
        $c->forceFill($changes)->save();
    }

    private function conclude(Conversation $c, string $outcome): void
    {
        $status = match ($outcome) {
            'agreed' => ConversationStatus::Agreed,
            'declined' => ConversationStatus::Declined,
            default => ConversationStatus::NoResponse,
        };

        $c->forceFill([
            'status' => $status->value,
            'outcome' => $outcome,
            'summary' => $this->summary($c, $outcome),
            'closed_at' => now(),
        ])->save();
    }

    private function summary(Conversation $c, string $outcome): string
    {
        $verdict = match ($outcome) {
            'agreed' => 'Uy egasi shartlarga rozi bo‘ldi.',
            'declined' => 'Uy egasi rad etdi.',
            default => 'Uy egasidan javob kelmadi.',
        };

        $turns = $c->messages->count();

        return "{$verdict} ({$turns} ta xabar almashildi.)";
    }

    private function normalizeOutcome(mixed $outcome): ?string
    {
        return in_array($outcome, ['agreed', 'declined', 'no_response'], true) ? $outcome : null;
    }

    private function criteriaSummary(Conversation $c): string
    {
        $r = $c->searchRequest;
        if (! $r) {
            return '- (mezonlar mavjud emas)';
        }

        $lines = [];
        if ($r->region) {
            $lines[] = '- Hudud: '.$r->region->name_uz.($r->district ? ', '.$r->district->name_uz : '');
        }
        if ($r->price_min || $r->price_max) {
            $lines[] = '- Narx: '.$r->priceLabel();
        }
        if (! empty($r->rooms)) {
            $lines[] = '- Xonalar: '.implode('/', (array) $r->rooms);
        }
        if ($r->condition && $r->condition !== Condition::Any) {
            $lines[] = '- Holati: '.$r->condition->getLabel();
        }
        if ($r->gender && $r->gender !== Gender::Any) {
            $lines[] = '- Jinsi: '.$r->gender->getLabel();
        }
        if ($r->marital_status && $r->marital_status !== MaritalStatus::Any) {
            $lines[] = '- Oilaviy holati: '.$r->marital_status->getLabel();
        }

        return $lines ? implode("\n", $lines) : '- Aniq mezonlar ko‘rsatilmagan.';
    }
}
