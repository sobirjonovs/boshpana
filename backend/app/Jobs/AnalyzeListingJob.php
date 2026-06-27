<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Services\Ai\ListingAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Enriches a freshly ingested Listing with structured AI/heuristic attributes.
 * Dispatched by IngestController for new/updated rows.
 */
class AnalyzeListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(public readonly Listing $listing)
    {
    }

    public function handle(ListingAnalyzer $analyzer): void
    {
        $analyzer->analyze($this->listing);
    }
}
