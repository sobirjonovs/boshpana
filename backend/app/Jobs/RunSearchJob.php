<?php

namespace App\Jobs;

use App\Models\SearchRequest;
use App\Services\Search\SearchOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs a full apartment search (matcher + AI owner negotiation) in the
 * background. Dispatched by SearchRequestController@start.
 */
class RunSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A search can contact many owners; give it generous head-room. */
    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(public readonly SearchRequest $searchRequest)
    {
    }

    public function handle(SearchOrchestrator $orchestrator): void
    {
        $orchestrator->run($this->searchRequest);
    }
}
