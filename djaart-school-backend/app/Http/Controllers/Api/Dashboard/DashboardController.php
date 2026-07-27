<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        [$role, $data] = match (true) {
            $user->hasRole('super_admin') => ['super_admin', $this->dashboardService->pourSuperAdmin()],
            $user->hasRole('admin_etablissement') => ['admin_etablissement', $this->dashboardService->pourAdminEtablissement($user)],
            $user->hasRole('comptable') => ['comptable', $this->dashboardService->pourComptable($user)],
            $user->hasRole('enseignant') => ['enseignant', $this->dashboardService->pourEnseignant($user)],
            default => ['autre', []],
        };

        return $this->success(['role' => $role, 'data' => $data]);
    }
}
