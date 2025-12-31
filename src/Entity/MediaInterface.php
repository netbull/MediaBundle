<?php

namespace NetBull\MediaBundle\Entity;

use DateTimeInterface;
use Imagine\Image\Box;

interface MediaInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self;

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self;

    /**
     * @return string|null
     */
    public function getProviderName(): ?string;

    /**
     * @param string|null $providerName
     * @return $this
     */
    public function setProviderName(?string $providerName): self;

    /**
     * @return string|null
     */
    public function getProviderReference(): ?string;

    /**
     * @param string|null $providerReference
     * @return $this
     */
    public function setProviderReference(?string $providerReference): self;

    /**
     * @return array
     */
    public function getProviderMetadata(): array;

    /**
     * @param array $providerMetadata
     * @return $this
     */
    public function setProviderMetadata(array $providerMetadata = []): self;

    /**
     * @return int|null
     */
    public function getWidth(): ?int;

    /**
     * @param int|null $width
     * @return $this
     */
    public function setWidth(?int $width): self;

    /**
     * @return int|null
     */
    public function getHeight(): ?int;

    /**
     * @param int|null $height
     * @return $this
     */
    public function setHeight(?int $height): self;

    /**
     * @return string|null
     */
    public function getLength(): ?string;

    /**
     * @param string|null $length
     * @return $this
     */
    public function setLength(?string $length): self;

    /**
     * @return int|null
     */
    public function getSize(): ?int;

    /**
     * @param int|null $size
     * @return $this
     */
    public function setSize(?int $size): self;

    /**
     * @return string|null
     */
    public function getContentType(): ?string;

    /**
     * @param string|null $contentType
     * @return $this
     */
    public function setContentType(?string $contentType): self;

    /**
     * @return string|null
     */
    public function getContext(): ?string;

    /**
     * @param string|null $context
     * @return $this
     */
    public function setContext(?string $context): self;

    /**
     * @return string|null
     */
    public function getCaption(): ?string;

    /**
     * @param string|null $caption
     * @return $this
     */
    public function setCaption(?string $caption): self;

    /**
     * @return int|null
     */
    public function getPosition(): ?int;

    /**
     * @param int|null $position
     * @return $this
     */
    public function setPosition(?int $position): self;

    /**
     * @return bool
     */
    public function isMain(): bool;

    /**
     * @param bool $main
     * @return $this
     */
    public function setMain(bool $main): self;

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * @param DateTimeInterface $createdAt
     * @return $this
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self;

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): DateTimeInterface;

    /**
     * @param DateTimeInterface $updatedAt
     * @return $this
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): self;

    #################################################
    #               Helper Methods                  #
    #################################################

    /**
     * @param mixed $binaryContent
     */
    public function setBinaryContent(mixed $binaryContent);

    /**
     * @return mixed
     */
    public function getBinaryContent(): mixed;

    /**
     * @param mixed $binaryContent
     */
    public function setNewBinaryContent(mixed $binaryContent);

    /**
     * @return mixed
     */
    public function getNewBinaryContent(): mixed;

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getMetadataValue(string $name, mixed $default = null): mixed;

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setMetadataValue(string $name, mixed $value);

    /**
     * Remove a named data from the metadata.
     *
     * @param string $name
     */
    public function unsetMetadataValue(string $name);

    /**
     * @return string
     */
    public function getExtension(): string;

    /**
     * @return Box
     */
    public function getBox(): Box;

    /**
     * @return string|null
     */
    public function getPreviousProviderReference(): ?string;
}
