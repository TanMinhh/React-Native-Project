<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(AuthRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error'=>'Unauthorized'],401);
        }

        $tokens = $this->issueTokens($user);
        
        // Log the token for testing purposes
        Log::info('Login successful for user: ' . $user->email);
        Log::info('Access Token: ' . $tokens['access_token']);

        return response()->json($tokens);
    }

    public function register(AuthRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => User::STAFF,
        ]);

        $tokens = $this->issueTokens($user);

        Log::info('Register successful for user: ' . $user->email);
        Log::info('Access Token: ' . $tokens['access_token']);

        return response()->json($tokens, 201);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        RefreshToken::where('user_id', $request->user()->id)
            ->update(['revoked' => true]);

        return ['message' => 'Logged out'];
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $tokenHash = hash('sha256', $request->refresh_token);

        $storedToken = RefreshToken::where('token_hash', $tokenHash)
            ->where('revoked', false)
            ->first();

        if (!$storedToken || $storedToken->expires_at->isPast()) {
            optional($storedToken)->update(['revoked' => true]);
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $storedToken->update(['revoked' => true]);

        return response()->json($this->issueTokens($storedToken->user));
    }

    private function issueTokens(User $user): array
    {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl'),
            'user' => $user,
        ];
    }

    private function generateAccessToken(User $user): string
    {
        $payload = [
            'iss' => config('jwt.issuer', config('app.url')),
            'sub' => $user->id,
            'role' => $user->role,
            'iat' => now()->timestamp,
            'exp' => now()->addSeconds((int) config('jwt.ttl', 900))->timestamp,
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    private function generateRefreshToken(User $user): string
    {
        $plain = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => Carbon::now()->addSeconds((int) config('jwt.refresh_ttl', 2592000)),
        ]);

        return $plain;
    }
}
