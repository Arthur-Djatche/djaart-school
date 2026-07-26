<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApprenantResource;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class ApprenantController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Apprenant::class);

        $apprenants = Apprenant::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('matricule', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->limit(20)
            ->get();

        return $this->success(ApprenantResource::collection($apprenants));
    }

    public function show(Apprenant $apprenant)
    {
        $this->authorize('view', $apprenant);

        return $this->success(new ApprenantResource($apprenant));
    }
}
