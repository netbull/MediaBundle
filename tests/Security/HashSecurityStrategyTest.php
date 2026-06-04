<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Security;

use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Security\HashSecurityStrategy;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(HashSecurityStrategy::class)]
class HashSecurityStrategyTest extends TestCase
{
    private const SECRET = 's3cr3t-test-key';

    private const USER = 'user-42';

    private SimpleSignatureHasher $hasher;

    private HashSecurityStrategy $strategy;

    protected function setUp(): void
    {
        $this->hasher = new SimpleSignatureHasher(self::SECRET);
        $this->strategy = new HashSecurityStrategy($this->hasher);
    }

    public function testGrantsAccessToTheMediaTheUrlWasSignedFor(): void
    {
        $expires = time() + 300;
        $request = $this->signedRequest(100, $expires);

        self::assertTrue($this->strategy->isGranted($this->media(100), $request));
    }

    /**
     * Regression: a URL signed for media 100 must not grant access to media 101 by simply
     * swapping the {id} path segment (horizontal IDOR over the whole media table).
     */
    public function testDeniesAccessWhenMediaIdIsSwapped(): void
    {
        $expires = time() + 300;
        // Signature/params are valid and issued for media 100 ...
        $request = $this->signedRequest(100, $expires);

        // ... but the request targets media 101.
        self::assertFalse($this->strategy->isGranted($this->media(101), $request));
    }

    public function testDeniesExpiredSignature(): void
    {
        $expires = time() - 1;
        $request = $this->signedRequest(100, $expires);

        self::assertFalse($this->strategy->isGranted($this->media(100), $request));
    }

    public function testDeniesWhenSignatureParamsAreMissing(): void
    {
        self::assertFalse($this->strategy->isGranted($this->media(100), new Request()));
    }

    private function media(int $id): MediaInterface
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getId')->willReturn($id);

        return $media;
    }

    private function signedRequest(int $mediaId, int $expires): Request
    {
        $hash = $this->hasher->computeSignatureHash(self::USER, $expires, (string) $mediaId);

        return new Request(['u' => self::USER, 'e' => $expires, 'h' => $hash]);
    }
}
