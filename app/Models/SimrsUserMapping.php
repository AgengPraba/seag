<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimrsUserMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'issuer',
        'subject',
        'simrs_user_id',
        'access_request_id',
        'status',
    ];

    public function accessRequest(): BelongsTo
    {
        return $this->belongsTo(ExternalAccessRequest::class, 'access_request_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
