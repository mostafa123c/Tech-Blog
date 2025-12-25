<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Authentication\LoginRequest;
use App\Http\Requests\Authentication\RegisterRequest;
use App\Http\Resources\JsonResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('users_images', 'public');
        }
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return success_response($user, 'User registered successfully', 201);
    }


    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!$token = Auth::attempt($credentials)) {
            return error_response('Invalid credentials', 401);
        }

        return $this->respondWithToken($token);
    }


    public function logout()
    {
        auth()->logout();

        return success_response(null, 'Successfully logged out');
    }

    public function me()
    {
        return response()->json(JsonResource::noResourceItem(auth()->user(),[
            'id',
            'name',
            'email',
            'image',
            'created_at',
        ] ));
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}
