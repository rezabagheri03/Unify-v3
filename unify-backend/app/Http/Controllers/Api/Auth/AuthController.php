<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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

        // Create Sanctum token
        $token = $user->createToken('unify-token')->plainTextToken;

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

        return response()->json(['message' => 'آنبوردینگ با موفقیت انجام شد']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password_hash)) {
            return response()->json(['message' => 'رمز فعلی اشتباه است'], 400);
        }

        // Check password history (last 3)
        $history = $user->passwordHistories()->orderBy('created_at', 'desc')->take(3)->get();
        foreach ($history as $old) {
            if (Hash::check($request->new_password, $old->hash)) {
                return response()->json(['message' => 'رمز جدید نباید با ۳ رمز قبلی یکسان باشد'], 400);
            }
        }

        $user->password_hash = Hash::make($request->new_password, ['rounds' => 12]);
        $user->must_change_password = false;
        $user->temporary_password_expires_at = null;
        $user->save();

        // Save to password history
        $user->passwordHistories()->create([
            'hash' => $user->password_hash,
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'رمز عبور با موفقیت تغییر کرد']);
    }
}