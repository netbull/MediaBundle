<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Tests\Http;

use NetBull\MediaBundle\Http\UrlSecurityChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlSecurityChecker::class)]
class UrlSecurityCheckerTest extends TestCase
{
    private UrlSecurityChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UrlSecurityChecker();
    }

    public function testPublicIpsAreConsideredPublic(): void
    {
        self::assertTrue($this->checker->isPublicIp('8.8.8.8'));
        self::assertTrue($this->checker->isPublicIp('1.1.1.1'));
        self::assertTrue($this->checker->isPublicIp('2606:4700:4700::1111'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function privateOrReservedIps(): iterable
    {
        yield 'loopback v4' => ['127.0.0.1'];
        yield 'private 10/8' => ['10.0.0.1'];
        yield 'private 172.16/12' => ['172.16.0.1'];
        yield 'private 192.168/16' => ['192.168.1.1'];
        yield 'link-local / cloud metadata' => ['169.254.169.254'];
        yield 'loopback v6' => ['::1'];
    }

    #[DataProvider('privateOrReservedIps')]
    public function testPrivateAndReservedIpsAreBlocked(string $ip): void
    {
        self::assertFalse($this->checker->isPublicIp($ip));
    }

    public function testAllowsHttpsToPublicHost(): void
    {
        self::assertTrue($this->checker->isAllowed('https://8.8.8.8/thumb.jpg'));
        self::assertTrue($this->checker->isAllowed('http://1.1.1.1/x.png'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blockedUrls(): iterable
    {
        yield 'loopback' => ['http://127.0.0.1/x'];
        yield 'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'];
        yield 'private host' => ['https://10.0.0.5/secret'];
        yield 'ipv6 loopback' => ['https://[::1]/x'];
        yield 'ftp scheme' => ['ftp://8.8.8.8/x'];
        yield 'file scheme' => ['file:///etc/passwd'];
        yield 'not a url' => ['not a url'];
        yield 'empty' => [''];
    }

    #[DataProvider('blockedUrls')]
    public function testRejectsInternalOrUnsupportedUrls(string $url): void
    {
        self::assertFalse($this->checker->isAllowed($url));
    }
}
