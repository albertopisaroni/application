<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FiscoapiSession;
use Illuminate\Support\Facades\Auth;

class FiscoApiService
{
    protected string $secret;
    protected string $baseUrl;
    protected string $cacheKeyToken = 'fiscoapi_public_key';
    protected string $cacheKeyRefresh = 'fiscoapi_refresh_token';

    public function __construct()
    {
        $this->secret = config('services.fiscoapi.secret');
        $this->baseUrl = rtrim(config('services.fiscoapi.base_url'), '/');
    }

    /**
     * Ottieni la chiave pubblica valida, gestendo caching e refresh.
     */
    public function getBearerToken(): ?string
    {
        $token = Cache::get($this->cacheKeyToken);
        $refresh = Cache::get($this->cacheKeyRefresh);

        if ($token) {
            return $token;
        }

        // Prova refresh se abbiamo il refresh token
        if ($refresh) {
            $token = $this->refreshPublicKey($refresh);
            if ($token) {
                return $token;
            }
        }

        // Altrimenti genera nuova chiave pubblica
        return $this->generatePublicKey();
    }

    /**
     * Genera una nuova chiave pubblica usando la chiave segreta.
     */
    public function generatePublicKey(): ?string
    {
        $response = Http::post($this->baseUrl . '/crea_chiave_api', [
            'chiave_segreta' => $this->secret,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['chiave_pubblica'] ?? null;
            $refresh = $data['refresh_token'] ?? null;
            if ($token) {
                // Cache per 59 minuti (validità 1h)
                Cache::put($this->cacheKeyToken, $token, now()->addMinutes(59));
            }
            if ($refresh) {
                // Cache per 23h (validità 24h)
                Cache::put($this->cacheKeyRefresh, $refresh, now()->addHours(23));
            }
            return $token;
        }
        Log::error('FiscoApi: errore generazione chiave pubblica', ['response' => $response->body()]);
        return null;
    }

    /**
     * Effettua il refresh della chiave pubblica usando il refresh token.
     */
    public function refreshPublicKey(string $refreshToken): ?string
    {
        $response = Http::post($this->baseUrl . '/refresh_chiave_api', [
            'refresh_token' => $refreshToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['chiave_pubblica'] ?? null;
            if ($token) {
                // Cache per 59 minuti (validità 1h)
                Cache::put($this->cacheKeyToken, $token, now()->addMinutes(59));
            }
            return $token;
        }
        Log::warning('FiscoApi: errore refresh chiave pubblica', ['response' => $response->body()]);
        // Se fallisce il refresh, pulisci cache per forzare nuova generazione
        Cache::forget($this->cacheKeyToken);
        Cache::forget($this->cacheKeyRefresh);
        return null;
    }

    /**
     * Avvia una sessione FiscoApi e la salva su DB.
     */
    public function avviaSessione(string $ente, string $tipoLogin): ?FiscoapiSession
    {
        $token = $this->getBearerToken();
        if (!$token) return null;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/avvia_sessione', [
            'ente' => $ente,
            'tipo_login' => $tipoLogin,
        ]);

        if ($response->successful() && ($sessione = $response->json('sessione'))) {
            return FiscoapiSession::create([
                'user_id' => Auth::id(),
                'id_sessione' => $sessione['_id'],
                'stato' => $sessione['stato'],
                'ente' => $sessione['ente'],
                'tipo_login' => $sessione['tipo_login'],
                'refresh_token' => null, // non fornito qui
                'qr_code' => null,
                'response' => $sessione,
            ]);
        }
        Log::error('FiscoApi: errore avvio sessione', ['response' => $response->body()]);
        return null;
    }

    /**
     * Aggiorna una sessione esistente (stato, qr_code, response completa).
     */
    public function aggiornaSessioneDaWebhook(array $payload): ?FiscoapiSession
    {
        $dato = $payload['dato'] ?? null;
        if (!$dato || empty($dato['_id'])) return null;
        $session = FiscoapiSession::where('id_sessione', $dato['_id'])->first();
        if (!$session) return null;
        $session->update([
            'stato' => $dato['stato'] ?? $session->stato,
            'qr_code' => $dato['qr_code'] ?? $session->qr_code,
            'response' => $dato,
        ]);
        return $session;
    }
} 