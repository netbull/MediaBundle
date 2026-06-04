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
        if (!$userIdentifier = $request->query->get('u')) {
            return false;
        }
        if (!$hash = $request->query->get('h')) {
            return false;
        }
        if (!$expires = (int) $request->query->get('e')) {
            return false;
        }

        $mediaId = (string) $media->getId();

        try {
            $this->simpleSignatureHasher->acceptSignatureHash($userIdentifier, $expires, $mediaId, $hash);
            $this->simpleSignatureHasher->verifySignatureHash($userIdentifier, $expires, $mediaId, $hash);
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
