<?php

namespace App\Livewire;

use App\Models\AccessAuditLog;
use App\Models\ExternalAccessRequest;
use App\Models\SimrsUserMapping;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $totalRequests = ExternalAccessRequest::count();
        $approvedRequests = ExternalAccessRequest::where('status', 'approved')->count();
        $pendingRequests = ExternalAccessRequest::where('status', 'pending')->count();
        $revokedRequests = ExternalAccessRequest::where('status', 'revoked')->count();
        $totalMappings = SimrsUserMapping::count();

        $recentAudits = AccessAuditLog::latest('created_at')->take(7)->get();
        $recentRequests = ExternalAccessRequest::latest('created_at')->take(5)->get();

        return view('livewire.dashboard', [
            'totalRequests' => $totalRequests,
            'approvedRequests' => $approvedRequests,
            'pendingRequests' => $pendingRequests,
            'revokedRequests' => $revokedRequests,
            'totalMappings' => $totalMappings,
            'recentAudits' => $recentAudits,
            'recentRequests' => $recentRequests,
        ])->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
