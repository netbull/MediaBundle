<?php

namespace NetBull\MediaBundle\Security;

use Exception;
use NetBull\MediaBundle\Signature\SimpleSignatureHasher;
use Symfony\Component\HttpFoundation\Request;
use NetBull\MediaBundle\Entity\MediaInterface;

class HashSecurityStrategy implements SecurityStrategyInterface
{
    /**
     * @var SimpleSignatureHasher
     */
    protected SimpleSignatureHasher $simpleSignatureHasher;

    /**
     * @param SimpleSignatureHasher $simpleSignatureHasher
     */
    public function __construct(SimpleSignatureHasher $simpleSignatureHasher)
    {
        $this->simpleSignatureHasher = $simpleSignatureHasher;
    }

    /**
     * @param MediaInterface $media
     * @param Request $request
     * @return bool
     */
    public function isGranted(MediaInterface $media, Request $request): bool
    {
        if (!$userIdentifier = $request->get('u')) {
            return false;
        }
        if (!$hash = $request->get('h')) {
            return false;
        }
        if (!$expires = (int)$request->get('e')) {
            return false;
        }

        try {
            $this->simpleSignatureHasher->acceptSignatureHash($userIdentifier, $expires, $hash);
            $this->simpleSignatureHasher->verifySignatureHash($userIdentifier, $expires, $hash);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return self::FORBIDDEN_DESCRIPTION;
    }
}
