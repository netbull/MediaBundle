<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Security;

use Exception;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use Symfony\Component\HttpFoundation\Request;

class HashSecurityStrategy implements SecurityStrategyInterface
{
    protected SimpleSignatureHasher $simpleSignatureHasher;

    public function __construct(SimpleSignatureHasher $simpleSignatureHasher)
    {
        $this->simpleSignatureHasher = $simpleSignatureHasher;
    }

    public function isGranted(MediaInterface $media, Request $request): bool
    {
        if (!$userIdentifier = $request->get('u')) {
            return false;
        }
        if (!$hash = $request->get('h')) {
            return false;
        }
        if (!$expires = (int) $request->get('e')) {
            return false;
        }

        try {
            $this->simpleSignatureHasher->acceptSignatureHash($userIdentifier, $expires, $hash);
            $this->simpleSignatureHasher->verifySignatureHash($userIdentifier, $expires, $hash);
        } catch (Exception) {
            return false;
        }

        return true;
    }

    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
