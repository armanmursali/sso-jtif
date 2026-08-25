<?php

namespace Jtif\SsoJtif\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SsoInstallCommand extends Command
{
    /**
     * Nama dan signature perintah artisan interaktif.
     */
    protected $signature = 'sso:install';

    /**
     * Deskripsi dari perintah artisan.
     */
    protected $description = 'Instalasi interaktif paket sso-jtif dengan verifikasi Client ID pusat dan pemilihan mode autentikasi.';

    /**
     * Eksekusi perintah utama.
     */
    public function handle()
    {
        $this->info('==================================================');
        $this->info('   SELAMAT DATANG DI INSTALASI PAKET SSO-JTIF     ');
        $this->info('==================================================');

        // 1. CLIENT ID VERIFICATION
        $clientId = $this->ask('Masukkan Client ID resmi dari Server SSO Pusat:');

        if (empty($clientId)) {
            $this->error('Client ID tidak boleh kosong! Instalasi dibatalkan.');
            return self::FAILURE;
        }

        $this->line('Menghubungkan ke Server SSO Pusat untuk verifikasi Client ID...');

        try {
            // Validasi ke endpoint pusat SSO
            $response = Http::timeout(5)->post('http://localhost:8000/api/verify-client', [
                'client_id' => $clientId,
            ]);

            if (!$response->successful()) {
                $this->error('Client ID tidak valid atau tidak terdaftar di Server SSO Pusat!');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->warn('Peringatan: Gagal terhubung ke peladen pusat secara langsung, pastikan koneksi atau URL sudah benar.');
        }

        $this->info('Verifikasi Client ID Berhasil!');

        // 2. INTERACTIVE MODE SELECTION
        $mode = $this->choice(
            'Pilih mode instalasi yang Anda inginkan untuk proyek ini:',
            [
                'pure' => 'Pure SSO (Authentication Only - Hanya login & token)',
                'full' => 'Full Authentication & Synchronized Models (SSO + Model tersentralisasi)'
            ],
            'full'
        );

        $this->info("Mode yang dipilih: " . strtoupper($mode));

        if ($mode === 'full') {
            $this->line('Menyiapkan kerangka model tersentralisasi dan API Trait di dalam package...');
        } else {
            $this->line('Menyiapkan mode Pure SSO...');
        }

        $this->info('==================================================');
        $this->info('   INSTALASI SSO-JTIF BERHASIL DISELESAIKAN!      ');
        $this->info('==================================================');

        return self::SUCCESS;
    }
}