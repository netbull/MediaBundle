<?php

declare(strict_types=1);

/*
 * Standalone smoke test for the "symfony/messenger is optional" guarantee.
 *
 * Run in a production-style install where the component is ABSENT:
 *
 *     composer install --no-dev      # symfony/messenger lives in require-dev, so it is not installed
 *     php tests/smoke/without-messenger.php
 *
 * It is a plain script (not a PHPUnit test) because PHPUnit itself is a dev dependency and is
 * unavailable under --no-dev. It exits non-zero on the first failed assertion.
 *
 * What it proves:
 *   - symfony/messenger is genuinely not installed in this environment.
 *   - The classes that reference Messenger types still load and instantiate. In particular
 *     FormatThumbnail has a `use Symfony\Component\Messenger\MessageBusInterface;` import and a
 *     nullable typed constructor argument; loading and constructing it with a null bus must NOT
 *     trigger an autoload failure (a `use` statement never autoloads, and a null nullable argument
 *     never resolves its type).
 */

use NetBull\MediaBundle\Message\GenerateThumbnailMessage;
use NetBull\MediaBundle\Thumbnail\FormatThumbnail;
use Psr\Log\NullLogger;

require __DIR__ . '/../../vendor/autoload.php';

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        echo "  ok   - {$message}\n";
    } else {
        echo "  FAIL - {$message}\n";
        ++$failures;
    }
}

echo "Smoke test: bundle without symfony/messenger\n";

// The whole point: this environment must not have the Messenger component.
check(
    !interface_exists(\Symfony\Component\Messenger\MessageBusInterface::class),
    'symfony/messenger is not installed (running the optional-dependency path)',
);

// Loading + constructing FormatThumbnail with a null bus must not fatal even though the class
// imports MessageBusInterface. This is the production default (sync) wiring.
$thumbnail = new FormatThumbnail(new NullLogger(), null, false);
check($thumbnail instanceof FormatThumbnail, 'FormatThumbnail constructs with a null message bus');

// The dispatched message is a plain DTO and must be usable without the component.
$message = new GenerateThumbnailMessage(1, 'thumb');
check(
    1 === $message->getMediaId() && 'thumb' === $message->getFormat(),
    'GenerateThumbnailMessage constructs and exposes its data',
);

if ($failures > 0) {
    echo "\n{$failures} assertion(s) failed.\n";
    exit(1);
}

echo "\nAll smoke assertions passed.\n";
