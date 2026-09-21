<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait VerifiesFace
{
    /**
     * Verifikasi wajah user via Python face service.
     * Return null kalau valid, atau pesan error kalau gagal.
     */
    protected function verifyFace(string $photoBase64): ?string
    {
        $user = $this->user();

        if (!$user) {
            return 'User tidak terautentikasi.';
        }

        // 1. Cek foto profil wajib
        if (!$user->photo) {
            return 'Anda belum mengatur foto profil. Ambil foto profil dulu di menu Profil.';
        }

        $profilePhotoPath = storage_path('app/public/' . $user->photo);

        if (!file_exists($profilePhotoPath)) {
            return 'Foto profil tidak ditemukan di server. Update ulang foto profil.';
        }

        // 2. Convert foto profil ke base64
        $ext = pathinfo($user->photo, PATHINFO_EXTENSION);
        $mime = $ext === 'png' ? 'png' : 'jpeg';
        $profileBase64 = 'data:image/' . $mime . ';base64,' .
            base64_encode(file_get_contents($profilePhotoPath));

        // 3. Panggil Python face service
        try {
            Log::info('📸 Verifying face for user ' . $user->id);

            $response = Http::timeout(config('services.face.timeout'))
                ->post(config('services.face.url') . '/verify', [
                    'photo_absen' => $photoBase64,
                    'photo_profile' => $profileBase64,
                ]);

            $result = $response->json();

            Log::info('📸 Face verify result:', $result ?? []);

            // 4. Cek match
            if (!($result['match'] ?? false)) {
                Log::warning('❌ Face mismatch! Similarity: ' . ($result['similarity'] ?? 0));

                return $result['message']
                    ?? 'Wajah tidak cocok dengan foto profil. Absen ditolak.';
            }

            Log::info('✅ Face matched! Similarity: ' . ($result['similarity'] ?? 0));

            return null; // ✅ valid

        } catch (\Exception $e) {
            Log::error('Face verify error: ' . $e->getMessage());
            return 'Gagal memverifikasi wajah. Coba lagi.';
        }
    }
}