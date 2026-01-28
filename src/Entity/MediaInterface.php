<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Entity;

use DateTimeInterface;
use Imagine\Image\Box;

interface MediaInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    /**
     * @return $this
     */
    public function setName(?string $name): self;

    public function isEnabled(): bool;

    /**
     * @return $this
     */
    public function setEnabled(bool $enabled): self;

    public function getProviderName(): ?string;

    /**
     * @return $this
     */
    public function setProviderName(?string $providerName): self;

    public function getProviderReference(): ?string;

    /**
     * @return $this
     */
    public function setProviderReference(?string $providerReference): self;

    public function getProviderMetadata(): array;

    /**
     * @return $this
     */
    public function setProviderMetadata(array $providerMetadata = []): self;

    public function getWidth(): ?int;

    /**
     * @return $this
     */
    public function setWidth(?int $width): self;

    public function getHeight(): ?int;

    /**
     * @return $this
     */
    public function setHeight(?int $height): self;

    public function getLength(): ?string;

    public function setLength(?string $length): self;

    public function getSize(): ?int;

    /**
     * @return $this
     */
    public function setSize(?int $size): self;

    public function getContentType(): ?string;

    /**
     * @return $this
     */
    public function setContentType(?string $contentType): self;

    public function getContext(): ?string;

    /**
     * @return $this
     */
    public function setContext(?string $context): self;

    public function getCaption(): ?string;

    /**
     * @return $this
     */
    public function setCaption(?string $caption): self;

    public function getPosition(): ?int;

    /**
     * @return $this
     */
    public function setPosition(?int $position): self;

    public function isMain(): bool;

    /**
     * @return $this
     */
    public function setMain(bool $main): self;

    public function getCreatedAt(): DateTimeInterface;

    /**
     * @return $this
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self;

    public function getUpdatedAt(): DateTimeInterface;

    /**
     * @return $this
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): self;

    // ################################################
    //               Helper Methods                  #
    // ################################################

    public function setBinaryContent(mixed $binaryContent);

    public function getBinaryContent(): mixed;

    public function setNewBinaryContent(mixed $binaryContent);

    public function getNewBinaryContent(): mixed;

    public function getMetadataValue(string $name, mixed $default = null): mixed;

    public function setMetadataValue(string $name, mixed $value);

    /**
     * Remove a named data from the metadata.
     */
    public function unsetMetadataValue(string $name);

    public function getExtension(): string;

    public function getBox(): Box;

    public function getPreviousProviderReference(): ?string;
}
