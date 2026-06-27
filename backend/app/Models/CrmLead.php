<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLead extends Model
{
    protected $fillable = [
        'name', 'company', 'phone', 'telegram', 'email', 'type', 'status',
        'source', 'potential_value', 'assigned_to', 'listing_owner_id',
        'notes', 'last_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'potential_value' => 'decimal:2',
            'last_contacted_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function listingOwner(): BelongsTo
    {
        return $this->belongsTo(ListingOwner::class);
    }
}
