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
            'barangay' => 'required|string|exists:barangays,name',
            'municipality' => 'required|string|exists:municipalities,name',
            'referrer_contact' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store registration data temporarily with OTP
        $registrationData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'barangay' => $request->barangay,
            'municipality' => $request->municipality,
            'referrer_contact' => $request->referrer_contact,
            'referrer_name' => $request->referrer_name,
            'expires_at' => now()->addMinutes(30) // Registration expires in 30 mins
        ];

        // Store temporary registration data
        cache()->put("registration_{$request->email}", $registrationData, now()->addMinutes(30));

        // Store EmailOtp (without user_id for now)
        EmailOtp::create([
            'email' => $request->email, // Store email instead of user_id
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send email
        try {
            Mail::to($request->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            // Log error but continue
            \Log::error('Email sending failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Registration initiated. Please check your email for OTP.'], 201);
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

        // Check if registration data exists
        $registrationData = cache()->get("registration_{$request->email}");
        if (!$registrationData) {
            return response()->json(['message' => 'Registration expired or not found. Please register again.'], 404);
        }

        // Verify OTP
        $otpRecord = EmailOtp::where('email', $request->email)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($request->otp, $otpRecord->otp_hash)) {
            return response()->json(['message' => 'OTP expired or invalid. Request a new one.'], 422);
        }

        // Create the user now that OTP is verified
        $user = User::create([
            'name' => $registrationData['name'],
            'email' => $registrationData['email'],
            'password' => $registrationData['password'],
            'role' => $registrationData['role'],
            'phone' => $registrationData['phone'],
            'barangay' => $registrationData['barangay'],
            'municipality' => $registrationData['municipality'],
            'verification_status' => 'pending',
            'email_verified_at' => now()
        ]);

        // Create referral if exists
        if ($registrationData['referrer_contact']) {
            Referral::create([
                'new_user_id' => $user->id,
                'referrer_contact' => encrypt($registrationData['referrer_contact']),
                'referrer_name' => $registrationData['referrer_name'] ?? null
            ]);
        }

        // Clean up
        $otpRecord->delete();
        cache()->forget("registration_{$request->email}");

        return response()->json([
            'message' => 'Email verified successfully. Please upload your government ID to complete registration.',
            'user_id' => $user->id
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check if there's pending registration
        $registrationData = cache()->get("registration_{$request->email}");
        if (!$registrationData) {
            return response()->json(['message' => 'Registration expired or not found. Please register again.'], 404);
        }

        $latestOtp = EmailOtp::where('email', $request->email)
            ->latest()
            ->first();

        if ($latestOtp && $latestOtp->created_at > now()->subMinute()) {
            return response()->json(['message' => 'Please wait before requesting a new OTP.'], 429);
        }

        // Delete old OTPs
        EmailOtp::where('email', $request->email)->delete();

        // Generate new OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::create([
            'email' => $request->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)
        ]);

        // Send email
        try {
            Mail::to($request->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            \Log::error('Email sending failed: ' . $e->getMessage());
        }

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

        if (!$user->document_url) {
            return response()->json(['message' => 'ID document not uploaded. Please complete your registration.'], 403);
        }

        if ($user->verification_status !== 'approved') {
            // TEMPORARY: Auto-approve for testing (REMOVE IN PRODUCTION!)
            if (config('app.env') === 'local') {
                $user->update([
                    'verification_status' => 'approved',
                    'verification_badge' => true
                ]);
                \Log::info("Auto-approved user {$user->email} for testing");
            } else {
                return response()->json(['message' => 'Account pending admin approval. Your ID is being reviewed.'], 403);
            }
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
