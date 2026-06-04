<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Signature;

use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Core\Signature\Exception\InvalidSignatureException;

#[CoversClass(SimpleSignatureHasher::class)]
class SimpleSignatureHasherTest extends TestCase
{
    private const SECRET = 's3cr3t-test-key';

    private const USER = 'user-42';

    private SimpleSignatureHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new SimpleSignatureHasher(self::SECRET);
    }

    public function testSignatureValidatesForTheMediaItWasIssuedFor(): void
    {
        $expires = time() + 300;
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, '100');

        // Neither call throws => the signature is accepted for media 100.
        $this->hasher->acceptSignatureHash(self::USER, $expires, '100', $hash);
        $this->hasher->verifySignatureHash(self::USER, $expires, '100', $hash);

        $this->expectNotToPerformAssertions();
    }

    /**
     * Regression test for the signed-URL IDOR (broken access control).
     *
     * A signature issued for media 100 must NOT authorise media 101. Before the fix the
     * media id was not part of the signed payload, so one signature granted access to every
     * media id by simply swapping the {id} path segment.
     */
    public function testSignatureIssuedForOneMediaIsRejectedForAnother(): void
    {
        $expires = time() + 300;
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, '100');

        $this->expectException(InvalidSignatureException::class);
        $this->hasher->acceptSignatureHash(self::USER, $expires, '101', $hash);
    }

    public function testVerifyAlsoRejectsADifferentMedia(): void
    {
        $expires = time() + 300;
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, '100');

        $this->expectException(InvalidSignatureException::class);
        $this->hasher->verifySignatureHash(self::USER, $expires, '101', $hash);
    }

    public function testSignatureIsRejectedForADifferentUser(): void
    {
        $expires = time() + 300;
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, '100');

        $this->expectException(InvalidSignatureException::class);
        $this->hasher->acceptSignatureHash('someone-else', $expires, '100', $hash);
    }

    public function testExpiredSignatureIsRejected(): void
    {
        $expires = time() - 1;
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, '100');

        $this->expectException(ExpiredSignatureException::class);
        $this->hasher->acceptSignatureHash(self::USER, $expires, '100', $hash);
    }

    public function testTamperedHashIsRejected(): void
    {
        $expires = time() + 300;
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, '100');

        // Flip the first character of the HMAC segment.
        $tampered = substr_replace($hash, 'A' === $hash[0] ? 'B' : 'A', 0, 1);

        $this->expectException(InvalidSignatureException::class);
        $this->hasher->acceptSignatureHash(self::USER, $expires, '100', $tampered);
    }
}
