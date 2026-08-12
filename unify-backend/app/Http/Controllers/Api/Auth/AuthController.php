<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('id', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            Log::channel('auth')->warning('LOGIN_FAIL', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'user_exists' => (bool) $user,
                'time' => now()->toIso8601String(),
            ]);

            // Log failed login
            \App\Models\AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'failed_login',
                'resource_type' => 'user',
                'resource_id' => $request->username,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => Crypt::encryptString(json_encode(['reason' => 'invalid_credentials'])),
            ]);

            return response()->json([
                'message' => 'نام کاربری یا رمز اشتباه است'
            ], 401);
        }

        if ($user->is_banned) {
            return response()->json([
                'message' => 'حساب شما بن شده: ' . ($user->banned_reason ?? 'تقلب'),
                'banned_reason' => $user->banned_reason
            ], 403);
        }

        if ($user->temporary_password_expires_at && now()->greaterThan($user->temporary_password_expires_at)) {
            return response()->json([
                'message' => 'رمز موقت منقضی شده است',
                'expired' => true
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        Log::channel('auth')->info('LOGIN_OK', [
            'user_id' => $user->id,
            'role' => $user->role,
            'must_change_password' => $user->must_change_password,
            'ip' => $request->ip(),
            'time' => now()->toIso8601String(),
        ]);

        // Create Sanctum token (SEC-03 fix: explicit expiry; config/sanctum.php
        // `expiration` provides the second enforcement layer, and the nightly
        // `sanctum:prune-expired` schedule clears dead tokens).
        $newToken = $user->createToken('unify-token');
        $newToken->accessToken->forceFill(['expires_at' => now()->addDays(7)])->save();
        $token = $newToken->plainTextToken;

        // Log successful login
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'resource_type' => 'user',
            'resource_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => $user->role,
                'department_id' => $user->department_id,
                'academic_status_declared' => $user->academic_status_declared,
                'must_change_password' => $user->must_change_password,
            ],
            'must_change_password' => $user->must_change_password,
            'access_token' => $token,
        ]);
    }

    public function onboarding(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'supplementary_details' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'supplementary_details' => $request->supplementary_details,
        ]);

        Log::channel('auth')->info('ONBOARDING_OK', ['user_id' => $user->id, 'time' => now()->toIso8601String()]);

        return response()->json(['message' => 'آنبوردینگ با موفقیت انجام شد']);
    }

    public function changePassword(Request $request)
    {
        Log::channel('auth')->info('PASSWORD_CHANGE_ATTEMPT', [
            'user_id' => $request->user()?->id,
            'has_old' => filled($request->old_password),
            'has_new' => filled($request->new_password),
            'has_confirm' => filled($request->new_password_confirmation),
            'new_len' => strlen((string) $request->new_password),
            'new_matches_confirm' => $request->new_password === $request->new_password_confirmation,
            'ip' => $request->ip(),
            'time' => now()->toIso8601String(),
        ]);

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password_hash)) {
            Log::channel('auth')->warning('PASSWORD_CHANGE_FAIL_OLD', [
                'user_id' => $user->id,
                'time' => now()->toIso8601String(),
            ]);
            return response()->json(['message' => 'رمز فعلی اشتباه است'], 400);
        }

        // Check password history (last 3)
        $history = $user->passwordHistories()->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($history as $old) {
            if (Hash::check($request->new_password, $old->hash)) {
                return response()->json(['message' => 'رمز جدید نباید با ۳ رمز قبلی یکسان باشد'], 400);
            }
        }

        // Keep the previous hash before overwriting so it can be stored in history.
        $previousHash = $user->password_hash;

        $user->password_hash = Hash::make($request->new_password, ['rounds' => 12]);
        $user->must_change_password = false;
        $user->temporary_password_expires_at = null;
        $user->save();

        // Save the PREVIOUS password to history (so old passwords can't be reused).
        $user->passwordHistories()->create([
            'hash' => $previousHash,
            'created_at' => now(),
        ]);

        // SEC-03 fix: a password change invalidates every OTHER session/token.
        // The token that just proved knowledge of the old + new password keeps
        // working, so the user is not force-logged-out mid-flow.
        // currentAccessToken() is a TransientToken (no id/delete) under tests and
        // session auth — only a real PersonalAccessToken participates here,
        // otherwise ALL issued tokens are revoked.
        $currentToken = $user->currentAccessToken();
        if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        } else {
            $user->tokens()->delete();
        }

        Log::channel('auth')->info('PASSWORD_CHANGE_OK', ['user_id' => $user->id, 'time' => now()->toIso8601String()]);

        return response()->json(['message' => 'رمز عبور با موفقیت تغییر کرد']);
    }

    /**
     * Logout (SEC-03 fix): revokes the token that made this request.
     * There is intentionally no client-side-only "fake logout" anymore.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        // TransientToken (tests / session auth) has no delete(); only a real
        // PersonalAccessToken can be revoked. Otherwise this is a no-op.
        $currentToken = $user->currentAccessToken();
        if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $currentToken->delete();
        }

        Log::channel('auth')->info('LOGOUT', ['user_id' => $user->id, 'time' => now()->toIso8601String()]);

        return response()->json(['message' => 'خروج انجام شد']);
    }
}