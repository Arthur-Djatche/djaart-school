<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StoreDemandeDemoRequest;
use App\Http\Resources\DemandeDemoResource;
use App\Models\DemandeDemo;

class DemandeDemoController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $demandes = DemandeDemo::orderByDesc('id')->paginate(20);

        return DemandeDemoResource::collection($demandes)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreDemandeDemoRequest $request)
    {
        $demande = DemandeDemo::create($request->validated());

        return $this->success(new DemandeDemoResource($demande), 'Votre demande a bien été envoyée, notre équipe vous recontactera rapidement.', 201);
    }
}
