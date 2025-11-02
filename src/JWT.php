<?php

/**
 * Simple JWT Implementation
 * Basic JWT encoding/decoding for demonstration purposes
 */

class JWT {
    /**
     * Encode payload into JWT token
     */
    public static function encode(array $payload, string $secret, string $algorithm = 'HS256'): string {
        $header = [
            'alg' => $algorithm,
            'typ' => 'JWT'
        ];

        $headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret, true);
        $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    /**
     * Decode JWT token and return payload
     */
    public static function decode(string $token, string $secret): stdClass {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $expectedSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));

        if (!hash_equals($signature, $expectedSignatureEncoded)) {
            throw new Exception('Invalid signature');
        }

        // Decode payload
        $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
        $payloadData = json_decode($payloadJson, false);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid payload JSON');
        }

        // Check expiration
        if (isset($payloadData->exp) && $payloadData->exp < time()) {
            throw new Exception('Token expired');
        }

        return $payloadData;
    }
}

/**
 * Simple Key class for compatibility
 */
class Key {
    public function __construct(public string $key) {}
}
