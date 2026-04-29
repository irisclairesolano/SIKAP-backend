<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\EmailOtp;
use App\Models\Referral;
use App\Models\User;
use App\Services\SemaphoreService;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $semaphoreService;
    protected $supabaseStorageService;

    public function __construct(SemaphoreService $semaphoreService, SupabaseStorageService $supabaseStorageService)
    {
        $this->semaphoreService = $semaphoreService;
        $this->supabaseStorageService = $supabaseStorageService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:worker,employer',
            'phone' => 'required|string|max:20',
            'barangay' => 'required|string',
            'municipality' => 'required|string',
            'referrer_contact' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'barangay' => $request->barangay,
            'municipality' => $request->municipality,
            'verification_status' => 'pending'
        ]);

        // If referrer_contact: create Referral
        if ($request->referrer_contact) {
            Referral::create([
                'new_user_id' => $user->id,
                'referrer_contact' => encrypt($request->referrer_contact),
                'referrer_name' => $request->referrer_name ?? null
            ]);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store EmailOtp
        EmailOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send email
        Mail::to($user->email)->send(new OtpMail($otp));

        // Send SMS
        $message = "SIKAP verification code: {$otp} (expires in 10 minutes)";
        $this->semaphoreService->send($user->phone, $message);

        return response()->json(['message' => 'Account created. OTP sent.'], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otpRecord = EmailOtp::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($request->otp, $otpRecord->otp_hash)) {
            return response()->json(['message' => 'OTP expired or invalid. Request a new one.'], 422);
        }

        $user->update(['email_verified_at' => now()]);
        $otpRecord->delete();

        return response()->json(['message' => 'Email verified. Please upload your government ID.']);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $latestOtp = EmailOtp::where('user_id', $user->id)
            ->latest()
            ->first();

        if ($latestOtp && $latestOtp->created_at > now()->subMinute()) {
            return response()->json(['message' => 'Please wait before requesting a new OTP.'], 429);
        }

        // Delete old OTPs
        EmailOtp::where('user_id', $user->id)->delete();

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send email
        Mail::to($user->email)->send(new OtpMail($otp));

        // Send SMS
        $message = "SIKAP verification code: {$otp} (expires in 10 minutes)";
        $this->semaphoreService->send($user->phone, $message);

        return response()->json(['message' => 'OTP resent.']);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Email not verified.'], 403);
        }

        if ($user->is_suspended) {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'verification_status' => $user->verification_status,
                'verification_badge' => $user->verification_badge
            ]
        ]);
    }

    public function uploadId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_file' => 'required|file|mimes:jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $file = $request->file('id_file');
        $path = 'ids/' . $user->id . '_' . time() . '.' . $file->extension();
        $url = $this->supabaseStorageService->upload($file, $path);

        $user->update([
            'document_url' => $url,
            'verification_status' => 'pending'
        ]);

        return response()->json(['message' => 'ID uploaded. Account is pending admin approval.']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }
}
