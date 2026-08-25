<?php

namespace Jtif\SsoJtif\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SsoAuthController extends Controller
{
    /**
     * Mengarahkan pengguna langsung ke halaman otorisasi SSO pusat.
     */
    public function redirect()
    {
        $authUrl = 'http://localhost:8000/oauth/authorize?' . http_build_query([
            'client_id' => config('services.sso.client_id'),
            'redirect_uri' => config('services.sso.redirect'),
            'response_type' => 'code',
            'state' => Str::random(40),
        ]);

        return redirect($authUrl);
    }

    /**
     * Menangani callback, menukar token, menarik data user, dan menyediakannya untuk klien.
     */
    public function handleCallback(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return [
                'redirect' => redirect()->route('login')->with('error', 'Autentikasi SSO dibatalkan atau gagal.')
            ];
        }

        $response = Http::asForm()->post('http://localhost:8000/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.sso.client_id'),
            'client_secret' => config('services.sso.client_secret'),
            'redirect_uri' => config('services.sso.redirect'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            return [
                'redirect' => redirect()->route('login')->with('error', 'Gagal mendapatkan token: ' . $response->json('error_description', 'Kesalahan tidak diketahui'))
            ];
        }

        $tokenData = $response->json();
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return [
                'redirect' => redirect()->route('login')->with('error', 'Access token tidak ditemukan.')
            ];
        }

        $userResponse = Http::withToken($accessToken)->get('http://localhost:8000/api/user', [
            'with' => 'dosen.prodi,dosen.jafung,pimpinan.dosen.prodi,pimpinan.dosen.jafung,mahasiswa.prodi,operator.prodi,admin,roles,permissions'
        ]);

        if ($userResponse->failed()) {
            return [
                'redirect' => redirect()->route('login')->with('error', 'Gagal mengambil data profil dari server SSO.')
            ];
        }

        $ssoUserData = $userResponse->json();

        if (empty($ssoUserData)) {
            return [
                'redirect' => redirect()->route('login')->with('error', 'Gagal mengambil data pengguna dari SSO.')
            ];
        }

        // Menyimpan token dan data mentah ke session secara terpusat
        session([
            'sso_user_data' => $ssoUserData,
            'sso_access_token' => $accessToken 
        ]);

        // Mengembalikan data user secara utuh agar dapat dibaca oleh controller klien
        return [
            'ssoUserData' => $ssoUserData,
            'accessToken' => $accessToken,
        ];
    }
}