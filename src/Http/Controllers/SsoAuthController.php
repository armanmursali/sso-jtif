<?php

namespace Jtif\SsoJtif\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SsoAuthController extends Controller
{
    /**
     * Mengarahkan pengguna ke halaman login Server SSO Pusat kita.
     */
    public function redirect()
    {
        $clientId = config('sso-jtif.client_id');
        $redirectUri = route('sso.callback');
        $ssoServerUrl = config('sso-jtif.server_url', 'http://localhost:8000');

        return redirect("{$ssoServerUrl}/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope=");
    }

    /**
     * Menerima callback dari Server SSO Pusat, menukar token, dan menarik data user.
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->query('code');
            $ssoServerUrl = config('sso-jtif.server_url', 'http://localhost:8000');

            // 1. Menukar authorization code dengan access token ke server pusat SSO
            $response = Http::post("{$ssoServerUrl}/oauth/token", [
                'grant_type' => 'authorization_code',
                'client_id' => config('sso-jtif.client_id'),
                'client_secret' => config('sso-jtif.client_secret'),
                'redirect_uri' => route('sso.callback'),
                'code' => $code,
            ]);

            if (!$response->successful()) {
                return redirect()->route('login')->withErrors(['sso' => 'Autentikasi SSO pusat gagal.']);
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? null;

            // 2. Mengambil profil lengkap dari endpoint API server pusat SSO
            $userResponse = Http::withToken($accessToken)->get("{$ssoServerUrl}/api/user", [
                'with' => 'dosen.prodi,dosen.jafung,pimpinan.dosen.prodi,pimpinan.dosen.jafung,mahasiswa.prodi,mahasiswa.dosen.user,operator.prodi,admin'
            ]);

            if (!$userResponse->successful()) {
                return redirect()->route('login')->withErrors(['sso' => 'Gagal mengambil data profil dari Server SSO Pusat.']);
            }

            $ssoUserData = $userResponse->json();

            // 3. Sinkronisasi atau buat data user di database lokal klien
            $user = User::updateOrCreate(
                ['email' => $ssoUserData['email']],
                [
                    'name' => $ssoUserData['name'],
                    'password' => bcrypt(str()->random(24)),
                    'avatar' => $ssoUserData['avatar'] ?? null,
                ]
            );

            Auth::login($user);

            // Simpan token akses dan data mentah SSO ke dalam session lokal klien
            session([
                'sso_access_token' => $accessToken,
                'sso_user_data' => $ssoUserData,
            ]);

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('status', 'Error SSO: ' . $e->getMessage());
        }
    }
}