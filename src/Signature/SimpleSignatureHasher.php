<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Signature;

use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;

/**
 * Taken from Symfony\Component\Security\Core\Signature
 */
class SimpleSignatureHasher implements SignatureHasherInterface
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Verifies the hash using the provided user identifier and expire time.
     *
     * This method must be called before the user object is loaded from a provider.
     *
     * @param int $expires The expiry time as a unix timestamp
     * @param string $mediaId The id of the media the signature is bound to
     * @param string $hash The plaintext hash provided by the request
     *
     * @throws InvalidSignatureException If the signature does not match the provided parameters
     * @throws ExpiredSignatureException If the signature is no longer valid
     */
    public function acceptSignatureHash(string $userIdentifier, int $expires, string $mediaId, string $hash): void
    {
        if ($expires < time()) {
            throw new ExpiredSignatureException('Signature has expired.');
        }
        $hmac = substr($hash, 0, 44);
        $payload = substr($hash, 44) . ':' . $expires . ':' . $userIdentifier . ':' . $mediaId;

        if (!hash_equals($hmac, $this->generateHash($payload))) {
            throw new InvalidSignatureException('Invalid or expired signature.');
        }
    }

    /**
     * Verifies the hash using the provided user and expire time.
     *
     * @param int $expires The expiry time as a unix timestamp
     * @param string $mediaId The id of the media the signature is bound to
     * @param string $hash The plaintext hash provided by the request
     *
     * @throws InvalidSignatureException If the signature does not match the provided parameters
     * @throws ExpiredSignatureException If the signature is no longer valid
     */
    public function verifySignatureHash(string $userIdentifier, int $expires, string $mediaId, string $hash): void
    {
        if ($expires < time()) {
            throw new ExpiredSignatureException('Signature has expired.');
        }

        if (!hash_equals($hash, $this->computeSignatureHash($userIdentifier, $expires, $mediaId))) {
            throw new InvalidSignatureException('Invalid or expired signature.');
        }
    }

    /**
     * Computes the secure hash for the provided user, expire time and media id.
     *
     * The media id is part of the signed payload so a signature issued for one
     * media object cannot be replayed against another (prevents IDOR).
     *
     * @param int $expires The expiry time as a unix timestamp
     * @param string $mediaId The id of the media the signature is bound to
     */
    public function computeSignatureHash(string $userIdentifier, int $expires, string $mediaId): string
    {
        $fieldsHash = hash_init('sha256');
        hash_update($fieldsHash, ':' . base64_encode($userIdentifier) . ':' . $mediaId);

        $fieldsHash = strtr(base64_encode(hash_final($fieldsHash, true)), '+/=', '-_~');

        return $this->generateHash($fieldsHash . ':' . $expires . ':' . $userIdentifier . ':' . $mediaId) . $fieldsHash;
    }

    private function generateHash(string $tokenValue): string
    {
        return strtr(base64_encode(hash_hmac('sha256', $tokenValue, $this->secret, true)), '+/=', '-_~');
    }
}
