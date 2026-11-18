<?php

namespace App\Services;

use App\Models\AccessAuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Record a security or administrative audit log.
     */
    public static function log(
        string $eventType,
        string $actorType = 'system',
        ?string $actorId = null,
        ?string $targetUserId = null,
        string $eventStatus = 'success',
        ?string $description = null,
        array $metadata = []
    ): AccessAuditLog {
        // Redact any potentially sensitive keys from metadata
        $sanitizedMetadata = self::sanitize($metadata);

        return AccessAuditLog::create([
            'event_type' => $eventType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'target_user_id' => $targetUserId,
            'source_ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'event_status' => $eventStatus,
            'description' => $description,
            'metadata' => $sanitizedMetadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Sanitize sensitive keys to prevent accidental leaks.
     */
    private static function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'secret', 'client_secret',
            'token', 'access_token', 'refresh_token', 'id_token', 'otp'
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower((string)$key), $sensitiveKeys, true)) {
                $value = '[REDACTED]';
            }
        });

        return $data;
    }
}
