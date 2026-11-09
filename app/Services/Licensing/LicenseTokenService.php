<?php

namespace App\Services\Licensing;

use App\Models\License;
use RuntimeException;

/**
 * Service penandatanganan kriptografi asimetris (Ed25519 via libsodium)
 * untuk token lisensi LARIS.
 *
 * Format token: `<base64url(payload_json)>.<base64url(ed25519_signature)>`
 */
class LicenseTokenService
{
    public function __construct(
        private readonly EntitlementService $entitlementService
    ) {}

    /**
     * Membuat pasangan kunci Ed25519 baru (untuk inisialisasi server / testing).
     *
     * @return array{private_key: string, public_key: string}
     */
    public function generateKeyPair(): array
    {
        $keyPair = sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        return [
            'private_key' => base64_encode($secretKey),
            'public_key' => base64_encode($publicKey),
        ];
    }

    /**
     * Menandatangani token lisensi untuk perangkat tertentu.
     *
     * @return array{token: string, public_key: string, payload: array<string, mixed>}
     */
    public function issueSignedToken(
        License $license,
        string $deviceInstallationId
    ): array {
        $license->loadMissing('package.features');

        [$secretKey, $publicKeyBase64] = $this->resolveSigningKeys();

        $issuedAt = now()->utc();
        $graceDays = max(1, (int) ($license->offline_grace_days ?: config('licensing.default_offline_grace_days', 7)));
        $offlineGraceUntil = $issuedAt->copy()->addDays($graceDays);

        if ($license->expires_at !== null && $license->expires_at->utc()->lt($offlineGraceUntil)) {
            $offlineGraceUntil = $license->expires_at->copy()->utc();
        }

        $payload = [
            'license_id' => (string) $license->id,
            'license_key_masked' => $license->maskedKey(),
            'package_code' => strtoupper($license->package?->code ?? 'FREE'),
            'entitlements' => $this->entitlementService->getFeatureCodesForLicense($license),
            'device_installation_id' => $deviceInstallationId,
            'issued_at' => $issuedAt->toIso8601String(),
            'offline_grace_until' => $offlineGraceUntil->toIso8601String(),
            'expires_at' => $license->expires_at?->copy()->utc()->toIso8601String(),
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $payloadSegment = $this->base64UrlEncode($payloadJson);

        $signature = sodium_crypto_sign_detached($payloadSegment, $secretKey);
        $signatureSegment = $this->base64UrlEncode($signature);

        return [
            'token' => "{$payloadSegment}.{$signatureSegment}",
            'public_key' => $publicKeyBase64,
            'payload' => $payload,
        ];
    }

    /**
     * Memverifikasi token lisensi menggunakan public key Ed25519.
     *
     * @return array<string, mixed>|null
     */
    public function verifyToken(string $token, ?string $publicKeyBase64 = null): ?array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadSegment, $signatureSegment] = $parts;
        $signature = $this->base64UrlDecode($signatureSegment);

        if ($publicKeyBase64 === null || $publicKeyBase64 === '') {
            [, $publicKeyBase64] = $this->resolveSigningKeys();
        }

        $publicKey = base64_decode($publicKeyBase64, true);
        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return null;
        }

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return null;
        }

        $isValid = sodium_crypto_sign_verify_detached($signature, $payloadSegment, $publicKey);
        if (! $isValid) {
            return null;
        }

        $decodedJson = $this->base64UrlDecode($payloadSegment);
        if ($decodedJson === false) {
            return null;
        }

        $data = json_decode($decodedJson, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveSigningKeys(): array
    {
        $configuredPrivate = (string) config('licensing.signing_private_key', '');
        $configuredPublic = (string) config('licensing.signing_public_key', '');

        if ($configuredPrivate !== '' && $configuredPublic !== '') {
            $secretKey = base64_decode($configuredPrivate, true);
            if ($secretKey !== false && strlen($secretKey) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
                return [$secretKey, $configuredPublic];
            }
        }

        // Deterministic keypair dari APP_KEY apabila env khusus belum diset
        $appKey = (string) config('app.key', 'laris-default-seed-key');
        $seed = substr(hash('sha256', $appKey, true), 0, SODIUM_CRYPTO_SIGN_SEEDBYTES);
        $keyPair = sodium_crypto_sign_seed_keypair($seed);
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);

        return [$secretKey, base64_encode($publicKey)];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}

