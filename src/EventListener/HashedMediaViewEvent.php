<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\EventListener;

use DateTime;
use DateTimeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class HashedMediaViewEvent extends Event
{
    private DateTimeInterface $viewedAt;

    public function __construct(
        private string $mediaId,
        private string $identifier,
    ) {
        $this->viewedAt = new DateTime('now');
    }

    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getViewedAt(): DateTimeInterface
    {
        return $this->viewedAt;
    }

    public function setViewedAt(DateTimeInterface $viewedAt): void
    {
        $this->viewedAt = $viewedAt;
    }
}
