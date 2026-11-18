<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayServiceClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_secret',
        'client_name',
        'allowed_ips',
        'status',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'allowed_ips' => 'array',
        ];
    }
}
