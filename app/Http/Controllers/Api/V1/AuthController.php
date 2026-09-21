<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where(
            'email',
            $request->email
        )->first();

        if (
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password salah.'
                ],
            ]);
        }

        $token = $user
            ->createToken('absensi-web')
            ->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $user,
        ]);
    }
    
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('employee.department', 'employee.position');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'photo' => $user->photo,
                'photo_verified_at' => $user->photo_verified_at,
                'photo_url' => $user->photo
                    ? asset('storage/' . $user->photo)
                    : null,
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'employee_number' => $user->employee->employee_number,
                    'full_name' => $user->employee->full_name,
                    'phone' => $user->employee->phone,
                    'address' => $user->employee->address,
                    'join_date' => $user->employee->join_date,
                    'status' => $user->employee->status,
                    'department' => $user->employee->department?->name,
                    'position' => $user->employee->position?->name,
                ] : null,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'string', function ($attr, $val, $fail) {
                if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $val)) {
                    $fail('Format foto tidak valid.');
                }
            }],
        ]);

        $user = $request->user();

        // Validasi wajah via Python service
        try {
            $response = Http::timeout(config('services.face.timeout'))
                ->post(config('services.face.url') . '/encode', [
                    'photo' => $request->photo,
                ]);

            $result = $response->json();

            if (!($result['valid'] ?? false)) {
                return response()->json([
                    'message' => $result['message'] ?? 'Wajah tidak valid. Ambil ulang foto.',
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error('Face encode error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memverifikasi wajah. Coba lagi.'], 500);
        }

        // Simpan foto
        preg_match('/^data:image\/(\w+);base64,/', $request->photo, $matches);
        $extension = $matches[1] ?? 'jpg';
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $request->photo);
        $imageData = base64_decode($imageData);

        $path = 'users/profile/photo_' . $user->id . '_' . now()->format('YmdHis') . '.' . $extension;
        Storage::disk('public')->put($path, $imageData);

        // Hapus foto lama
        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->update([
            'photo' => $path,
            'photo_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil disimpan',
            'photo_url' => asset('storage/' . $path),
        ]);
    }
}
