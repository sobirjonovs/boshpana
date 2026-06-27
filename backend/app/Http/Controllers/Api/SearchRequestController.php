<?php

namespace App\Http\Controllers\Api;

use App\Enums\SearchStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SearchMatchResource;
use App\Http\Resources\SearchRequestResource;
use App\Jobs\RunSearchJob;
use App\Models\SearchRequest;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchRequestController extends Controller
{
    /** Validation rules shared by store() and update() for the criteria subset. */
    private function criteriaRules(): array
    {
        return [
            'region_id' => ['sometimes', 'nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['sometimes', 'nullable', 'integer', 'exists:districts,id'],
            'price_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'price_max' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'rooms' => ['sometimes', 'nullable', 'array'],
            'rooms.*' => ['integer', 'min:1', 'max:10'],
            'condition' => ['sometimes', 'nullable', 'string', 'in:average,excellent,any'],
            'has_furniture' => ['sometimes', 'nullable', 'string', 'in:yes,no,any'],
            'has_commission' => ['sometimes', 'nullable', 'string', 'in:yes,no,any'],
            'area_min' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'area_max' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mode' => ['sometimes', 'nullable', 'string', 'in:solo,partnership'],
            'partners_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10'],
            'near_metro' => ['sometimes', 'nullable', 'string', 'in:yes,no,any'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,any'],
            'marital_status' => ['sometimes', 'nullable', 'string', 'in:single,married,any'],
            'free_text' => ['sometimes', 'nullable', 'string'],
            'is_simulation' => ['sometimes', 'boolean'],
        ];
    }

    public function store(Request $request): SearchRequestResource
    {
        $data = $request->validate([
            'telegram_id' => ['required', 'integer'],
        ] + $this->criteriaRules());

        $user = TelegramUser::where('telegram_id', $data['telegram_id'])->firstOrFail();
        unset($data['telegram_id']);

        $search = $user->searchRequests()->create($data + [
            'status' => SearchStatus::Draft,
            'currency' => 'USD',
            'is_simulation' => $data['is_simulation']
                ?? (bool) config('boshpana.simulation_default'),
        ]);

        return new SearchRequestResource($search->load(['region', 'district']));
    }

    public function update(Request $request, SearchRequest $searchRequest): SearchRequestResource
    {
        $data = $request->validate($this->criteriaRules());

        $searchRequest->fill($data)->save();

        return new SearchRequestResource($searchRequest->refresh()->load(['region', 'district']));
    }

    public function start(SearchRequest $searchRequest): SearchRequestResource
    {
        $searchRequest->update(['status' => SearchStatus::Queued]);

        RunSearchJob::dispatch($searchRequest);

        return new SearchRequestResource($searchRequest->refresh()->load(['region', 'district']));
    }

    public function cancel(SearchRequest $searchRequest): SearchRequestResource
    {
        $searchRequest->update(['status' => SearchStatus::Cancelled]);

        return new SearchRequestResource($searchRequest->refresh()->load(['region', 'district']));
    }

    public function show(SearchRequest $searchRequest): SearchRequestResource
    {
        return new SearchRequestResource($searchRequest->load(['region', 'district']));
    }

    public function results(SearchRequest $searchRequest): AnonymousResourceCollection
    {
        $matches = $searchRequest->matches()
            ->with([
                'listing.source',
                'listing.region',
                'listing.district',
                'conversation.messages',
            ])
            ->orderByDesc('score')
            ->get();

        return SearchMatchResource::collection($matches);
    }
}
