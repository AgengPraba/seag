<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalAccessRequest;
use App\Models\GatewayServiceClient;
use App\Models\SimrsUserMapping;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccessVerificationController extends Controller
{
    /**
     * Verify employee external access eligibility for CI3.
     */
    public function verify(Request $request): JsonResponse
    {
        // 1. Authenticate Service Client (CI3)
        $serviceId = $request->header('X-Gateway-Service-ID');
        $serviceSecret = $request->header('X-Gateway-Service-Secret');
        $configuredInternalSecret = env('INTERNAL_API_SERVICE_SECRET');

        $isAuthorized = false;

        if ($configuredInternalSecret && $serviceSecret === $configuredInternalSecret) {
            $isAuthorized = true;
        } elseif ($serviceId && $serviceSecret) {
            $client = GatewayServiceClient::where('client_id', $serviceId)
                ->where('status', 'active')
                ->first();

            if ($client && Hash::check($serviceSecret, $client->client_secret)) {
                $isAuthorized = true;
            }
        }

        if (! $isAuthorized) {
            AuditLogService::log(
                eventType: 'internal_api.unauthorized',
                actorType: 'service',
                actorId: $serviceId ?? 'unknown',
                eventStatus: 'denied',
                description: 'Akses ke Internal API ditolak: kredensial service tidak valid.',
                metadata: ['ip' => $request->ip()]
            );

            return response()->json([
                'allowed' => false,
                'status' => 'unauthorized',
                'message' => 'Service client authentication failed.',
            ], 401);
        }

        // 2. Validate Request Payload
        $validated = $request->validate([
            'issuer' => 'required|url',
            'subject' => 'required|string|max:255',
        ]);

        $issuer = rtrim($validated['issuer'], '/');
        $subject = $validated['subject'];

        // 3. Find Stable Identity Mapping
        $mapping = SimrsUserMapping::with('accessRequest')
            ->where(function ($q) use ($issuer) {
                $q->where('issuer', $issuer)
                  ->orWhere('issuer', $issuer . '/');
            })
            ->where('subject', $subject)
            ->first();

        if (! $mapping || ! $mapping->isActive()) {
            AuditLogService::log(
                eventType: 'access.eligibility_denied',
                actorType: 'service',
                actorId: $serviceId ?? 'ci3',
                targetUserId: $subject,
                eventStatus: 'denied',
                description: "Verifikasi akses eksternal ditolak: mapping untuk sub={$subject} tidak ditemukan atau non-aktif.",
                metadata: ['issuer' => $issuer, 'subject' => $subject]
            );

            return response()->json([
                'allowed' => false,
                'status' => 'not_mapped',
                'message' => 'User identity is not mapped to any SIMRS account.',
            ], 200);
        }

        // 4. Verify Approval & Expiration
        $accessRequest = $mapping->accessRequest;

        if (! $accessRequest) {
            // Check by employee_id if not directly linked
            $accessRequest = ExternalAccessRequest::where('employee_id', $mapping->simrs_user_id)
                ->latest('created_at')
                ->first();
        }

        if (! $accessRequest || ! $accessRequest->isApproved()) {
            $status = $accessRequest ? $accessRequest->status : 'no_request';

            if ($accessRequest && $accessRequest->expires_at && $accessRequest->expires_at->isPast()) {
                $status = 'expired';
            }

            AuditLogService::log(
                eventType: 'access.eligibility_denied',
                actorType: 'service',
                actorId: $serviceId ?? 'ci3',
                targetUserId: $mapping->simrs_user_id,
                eventStatus: 'denied',
                description: "Verifikasi akses eksternal ditolak untuk SIMRS User ID {$mapping->simrs_user_id}. Status: {$status}",
                metadata: ['subject' => $subject, 'status' => $status]
            );

            return response()->json([
                'allowed' => false,
                'status' => $status,
                'message' => 'External access is not approved, has expired, or was revoked.',
            ], 200);
        }

        // 5. Eligible!
        AuditLogService::log(
            eventType: 'access.eligibility_granted',
            actorType: 'service',
            actorId: $serviceId ?? 'ci3',
            targetUserId: $mapping->simrs_user_id,
            eventStatus: 'success',
            description: "Verifikasi akses eksternal berhasil untuk SIMRS User ID {$mapping->simrs_user_id}.",
            metadata: ['subject' => $subject, 'simrs_user_id' => $mapping->simrs_user_id]
        );

        return response()->json([
            'allowed' => true,
            'status' => 'approved',
            'simrs_user_id' => $mapping->simrs_user_id,
            'expires_at' => $accessRequest->expires_at ? $accessRequest->expires_at->toIso8601String() : null,
        ], 200);
    }
}
