<?php

namespace NetBull\MediaBundle\EventListener;

use DateTime;
use DateTimeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class HashedMediaViewEvent extends Event
{
    /**
     * @var DateTimeInterface
     */
    private DateTimeInterface $viewedAt;

    /**
     * @param string $mediaId
     * @param string $identifier
     */
    public function __construct(
        private string $mediaId,
        private string $identifier
    ) {
        $this->viewedAt = new DateTime('now');
    }

    /**
     * @return string
     */
    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    /**
     * @param string $mediaId
     */
    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return DateTimeInterface
     */
    public function getViewedAt(): DateTimeInterface
    {
        return $this->viewedAt;
    }

    /**
     * @param DateTimeInterface $viewedAt
     */
    public function setViewedAt(DateTimeInterface $viewedAt): void
    {
        $this->viewedAt = $viewedAt;
    }
}
