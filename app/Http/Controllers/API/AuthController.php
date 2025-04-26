<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = User::where('email', $request->email)->with('profile')->first();

            $token = $user->createToken('api-token');

            return APIResponse::success('Successfully login!', [
                'user' => $user,
                'token' => $token->plainTextToken
            ]);

        } catch (\Exception $exception) {
            return APIResponse::exception($exception->getMessage());

        }
    }
}
