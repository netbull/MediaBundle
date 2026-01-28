<?php

namespace NetBull\MediaBundle\Signature;

use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;

interface SignatureHasherInterface
{
    /**
     * Verifies the hash using the provided user identifier and expire time.
     *
     * This method must be called before the user object is loaded from a provider.
     *
     * @param string $userIdentifier
     * @param int    $expires The expiry time as a unix timestamp
     * @param string $hash    The plaintext hash provided by the request
     *
     * @throws InvalidSignatureException If the signature does not match the provided parameters
     * @throws ExpiredSignatureException If the signature is no longer valid
     */
    public function acceptSignatureHash(string $userIdentifier, int $expires, string $hash): void;

    /**
     * Verifies the hash using the provided user and expire time.
     *
     * @param string $userIdentifier
     * @param int    $expires The expiry time as a unix timestamp
     * @param string $hash    The plaintext hash provided by the request
     *
     * @throws InvalidSignatureException If the signature does not match the provided parameters
     * @throws ExpiredSignatureException If the signature is no longer valid
     */
    public function verifySignatureHash(string $userIdentifier, int $expires, string $hash): void;

    /**
     * Computes the secure hash for the provided user and expire time.
     *
     * @param string $userIdentifier
     * @param int $expires The expiry time as a unix timestamp
     * @return string
     */
    public function computeSignatureHash(string $userIdentifier, int $expires): string;
}
