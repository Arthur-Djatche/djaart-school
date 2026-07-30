<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            return $this->error('Identifiants incorrects.', null, 401);
        }

        $request->session()->regenerate();

        $user = Auth::user()->load(['etablissement', 'etablissementsGeres']);

        return $this->success(new UserResource($user), 'Connexion réussie.');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Déconnexion réussie.');
    }

    public function me(Request $request)
    {
        return $this->success(new UserResource($request->user()->load(['etablissement', 'etablissementsGeres'])));
    }
}
