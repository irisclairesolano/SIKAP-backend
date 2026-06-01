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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected SemaphoreService $semaphoreService;
    protected \App\Services\SupabaseStorageService $supabaseStorageService;

    public function __construct(SemaphoreService $semaphoreService, \App\Services\SupabaseStorageService $supabaseStorageService)
    {
        $this->semaphoreService = $semaphoreService;
        $this->supabaseStorageService = $supabaseStorageService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:worker,employer',
            'phone' => 'required|string|max:20',
            'barangay' => 'required|string|exists:barangays,name',
            'municipality' => 'required|string|exists:municipalities,name',
            'referrer_contact' => 'nullable|string|max:20',
            'referrer_name' => 'nullable|string|max:255'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $existingUser = User::query()->where('email', $request->email)->first();
        if ($existingUser) {
            $status = $existingUser->getRegistrationStatus();

            if ($status === 'approved') {
                return response()->json(['message' => 'Email already exists.'], 409);
            }

            if ($status === 'pending_email_verification') {
                $existingUser->update([
                    'name' => $request->name,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                    'phone' => $request->phone,
                    'barangay' => $request->barangay,
                    'municipality' => $request->municipality,
                    'verification_status' => 'pending',
                    'registration_status' => 'pending_email_verification'
                ]);

                if ($request->referrer_contact) {
                    Referral::updateOrCreate(
                        ['new_user_id' => $existingUser->id],
                        [
                            'referrer_contact' => encrypt($request->referrer_contact),
                            'referrer_name' => $request->referrer_name
                        ]
                    );
                }

                EmailOtp::query()
                    ->where('email', $request->email)
                    ->where(function ($query) use ($existingUser) {
                        $query->where('user_id', $existingUser->id)
                            ->orWhereNull('user_id');
                    })->delete();
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                EmailOtp::query()->create([
                    'user_id' => $existingUser->id,
                    'email' => $request->email,
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10)
                ]);

                try {
                    Mail::to($request->email)->queue(new OtpMail($otp));
                } catch (\Exception $e) {
                    Log::error('Email sending failed: ' . $e->getMessage(), []);
                }

                return response()->json(['message' => 'Registration resumed. Please check your email for OTP.'], 200);
            }

            if ($status === 'pending_id_upload') {
                return response()->json([
                    'message' => 'Email already exists. Please complete your ID upload to continue registration.',
                    'registration_status' => $status,
                    'next_step' => 'upload_id'
                ], 409);
            }

            if ($status === 'pending_review') {
                return response()->json([
                    'message' => 'Your registration is under review. You will be notified once it is approved.',
                    'registration_status' => $status
                ], 409);
            }

            if ($status === 'rejected') {
                return response()->json([
                    'message' => 'Your registration was rejected. Please contact support or try again with a different email.',
                    'registration_status' => $status
                ], 409);
            }

            return response()->json(['message' => 'Unable to process registration for this email.'], 409);
        }

        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
            'barangay' => $request->barangay,
            'municipality' => $request->municipality,
            'verification_status' => 'pending',
            'registration_status' => 'pending_email_verification'
        ]);

        if ($request->referrer_contact) {
            Referral::updateOrCreate(
                ['new_user_id' => $user->id],
                [
                    'referrer_contact' => encrypt($request->referrer_contact),
                    'referrer_name' => $request->referrer_name
                ]
            );
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::query()->create([
            'user_id' => $user->id,
            'email' => $request->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)
        ]);

        try {
            Mail::to($request->email)->queue(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage(), []);
        }

        return response()->json(['message' => 'Registration initiated. Please check your email for OTP.'], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::query()->where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Registration not found. Please register first.'], 404);
        }

        $status = $user->getRegistrationStatus();
        if ($status === 'approved') {
            return response()->json(['message' => 'This account is already approved. Please log in.'], 409);
        }

        if ($status === 'pending_id_upload') {
            return response()->json([
                'message' => 'Email already verified. Please upload your government ID to complete registration.',
                'registration_status' => $status,
                'next_step' => 'upload_id'
            ], 409);
        }

        if ($status === 'pending_review') {
            return response()->json(['message' => 'Your application is under review. Please wait for approval.'], 409);
        }

        if ($status === 'rejected') {
            return response()->json(['message' => 'Your registration was rejected. Please contact support or try again with a different email.'], 409);
        }

        $otpRecord = EmailOtp::query()
            ->where('email', $request->email)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($request->otp, $otpRecord->otp_hash)) {
            return response()->json(['message' => 'OTP expired or invalid. Request a new one.'], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'registration_status' => 'pending_id_upload',
            'verification_status' => 'pending'
        ]);

        $otpRecord->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully. Please upload your government ID to complete registration.',
            'user_id' => $user->id,
            'token' => $token,
            'registration_status' => 'pending_id_upload',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::query()->where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Registration expired or not found. Please register again.'], 404);
        }

        $status = $user->getRegistrationStatus();
        if ($status !== 'pending_email_verification') {
            return response()->json([
                'message' => 'OTP can only be resent while email verification is pending.',
                'registration_status' => $status
            ], 409);
        }

        $latestOtp = EmailOtp::query()
            ->where('email', $request->email)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->latest()
            ->first();

        if ($latestOtp && $latestOtp->created_at > now()->subMinute()) {
            return response()->json(['message' => 'Please wait before requesting a new OTP.'], 429);
        }

        EmailOtp::query()
            ->where('email', $request->email)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })->delete();

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::query()->create([
            'user_id' => $user->id,
            'email' => $request->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10)
        ]);

        try {
            Mail::to($request->email)->queue(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage(), []);
        }

        return response()->json(['message' => 'OTP resent.']);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::query()->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $status = $user->getRegistrationStatus();
        if ($status === 'pending_email_verification') {
            return response()->json([
                'message' => 'Please verify your email to complete registration.',
                'registration_status' => $status,
                'next_step' => 'verify_email'
            ], 403);
        }

        if ($status === 'pending_id_upload') {
            return response()->json([
                'message' => 'Please upload your government ID to complete registration.',
                'registration_status' => $status,
                'next_step' => 'upload_id'
            ], 403);
        }

        if ($status === 'pending_review') {
            return response()->json([
                'message' => 'Your account is awaiting approval. You will receive an update soon.',
                'registration_status' => $status
            ], 403);
        }

        if ($status === 'rejected') {
            return response()->json([
                'message' => 'Your registration was rejected. Please contact support or register with another email.',
                'registration_status' => $status
            ], 403);
        }

        if ($user->is_suspended) {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        if ($status !== 'approved') {
            if (config('app.env') === 'local') {
                $user->update([
                    'verification_status' => 'approved',
                    'registration_status' => 'approved',
                    'verification_badge' => true
                ]);
                Log::info("Auto-approved user {$user->email} for testing", []);
            } else {
                return response()->json(['message' => 'Account pending admin approval. Your ID is being reviewed.'], 403);
            }
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
                'registration_status' => $user->registration_status ?? 'approved',
                'verification_status' => $user->verification_status,
                'verification_badge' => $user->verification_badge
            ]
        ]);
    }

    public function uploadId(Request $request)
    {
        try {
            $user = $request->user();
            $status = $user->getRegistrationStatus();

            if ($status !== 'pending_id_upload') {
                if ($status === 'pending_email_verification') {
                    return response()->json([
                        'message' => 'Please verify your email before uploading your ID.',
                        'registration_status' => $status,
                        'next_step' => 'verify_email'
                    ], 403);
                }

                if ($status === 'pending_review') {
                    return response()->json([
                        'message' => 'Your application is already under review.',
                        'registration_status' => $status
                    ], 403);
                }

                if ($status === 'approved') {
                    return response()->json(['message' => 'Your account is already approved.'], 409);
                }

                if ($status === 'rejected') {
                    return response()->json([
                        'message' => 'Your registration was rejected. Please contact support or try again with a different email.',
                        'registration_status' => $status
                    ], 403);
                }
            }

            Log::info('Upload ID attempt', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'has_id_file' => $request->hasFile('id_file'),
                'has_selfie_file' => $request->hasFile('selfie_file'),
                'all_files' => array_keys($request->allFiles())
            ]);

            if ($request->hasFile('id_file')) {
                Log::info('ID file info', [
                    'original_name' => $request->file('id_file')->getClientOriginalName(),
                    'mime_type' => $request->file('id_file')->getMimeType(),
                    'size' => $request->file('id_file')->getSize(),
                    'extension' => $request->file('id_file')->getClientOriginalExtension()
                ]);
            }

            if ($request->hasFile('selfie_file')) {
                Log::info('Selfie file info', [
                    'original_name' => $request->file('selfie_file')->getClientOriginalName(),
                    'mime_type' => $request->file('selfie_file')->getMimeType(),
                    'size' => $request->file('selfie_file')->getSize(),
                    'extension' => $request->file('selfie_file')->getClientOriginalExtension()
                ]);
            }

            $rules = [
                'id_file' => 'required|file|mimes:jpeg,png,jpg|max:5120',
            ];

            if ($user->role === 'worker') {
                $rules['selfie_file'] = 'required|file|mimes:jpeg,png,jpg|max:5120';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $idFile = $request->file('id_file');
            $idPath = 'ids/' . $user->id . '_id_' . time() . '.' . $idFile->extension();
            $idUrl = $this->supabaseStorageService->upload($idFile, $idPath);

            $updateData = [
                'document_url' => $idUrl,
                'verification_status' => 'pending',
                'registration_status' => 'pending_review'
            ];

            if ($request->hasFile('selfie_file')) {
                $selfieFile = $request->file('selfie_file');
                $selfiePath = 'selfies/' . $user->id . '_selfie_' . time() . '.' . $selfieFile->extension();
                $updateData['selfie_url'] = $this->supabaseStorageService->upload($selfieFile, $selfiePath);
            }

            $user->update($updateData);

            return response()->json(['message' => 'ID and selfie uploaded. Account is pending admin approval.', 'registration_status' => 'pending_review']);

        } catch (\Exception $e) {
            Log::error('Upload ID Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $token = $user->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }
}
