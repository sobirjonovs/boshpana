<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\TelegramUser;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /** Upsert a Telegram user by telegram_id. */
    public function sync(Request $request): UserResource
    {
        $data = $request->validate([
            'telegram_id' => ['required', 'integer'],
            'username' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'in:uz,ru,en'],
        ]);

        $user = TelegramUser::updateOrCreate(
            ['telegram_id' => $data['telegram_id']],
            array_filter([
                'username' => $data['username'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'language' => $data['language'] ?? null,
            ], fn ($v) => $v !== null) + [
                'last_seen_at' => now(),
            ],
        );

        // Apply defaults only on first creation.
        if ($user->wasRecentlyCreated) {
            $user->forceFill([
                'language' => $user->language?->value ?? ($data['language'] ?? 'uz'),
                'free_searches_left' => $user->free_searches_left
                    ?? (int) config('boshpana.search.free_searches'),
            ])->save();
        }

        return new UserResource($user->refresh());
    }

    public function show(int $telegramId): UserResource
    {
        $user = TelegramUser::where('telegram_id', $telegramId)->firstOrFail();

        return new UserResource($user);
    }

    public function update(Request $request, int $telegramId): UserResource
    {
        $data = $request->validate([
            'language' => ['sometimes', 'string', 'in:uz,ru,en'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'gender' => ['sometimes', 'string', 'in:male,female,any'],
            'marital_status' => ['sometimes', 'string', 'in:single,married,any'],
        ]);

        $user = TelegramUser::where('telegram_id', $telegramId)->firstOrFail();
        $user->fill($data)->save();

        return new UserResource($user->refresh());
    }
}
