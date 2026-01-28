<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Imagine\Image\Box;
use NetBull\MediaBundle\Repository\MediaRepository;

#[ORM\Table(name: 'media')]
#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Media implements MediaInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $enabled = false;

    #[ORM\Column(nullable: true)]
    private ?string $providerName = null;

    #[ORM\Column(nullable: true)]
    private ?string $providerReference = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $providerMetadata = [];

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $width = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $height = null;

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $length = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $size = null;

    #[ORM\Column(nullable: true)]
    private ?string $contentType = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $context = null;

    #[ORM\Column(nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $position = null;

    #[ORM\Column(type: 'boolean')]
    private bool $main = false;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    // ################################################
    //               Helper Properties               #
    // ################################################

    private mixed $binaryContent = null;

    private mixed $newBinaryContent = null;

    private ?string $previousProviderReference = null;

    public function __construct()
    {
        try {
            $this->createdAt = new DateTime('now');
            $this->updatedAt = new DateTime('now');
        } catch (Exception) {
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(?string $providerName): self
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function setProviderReference(?string $providerReference): self
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    public function getProviderMetadata(): array
    {
        return $this->providerMetadata;
    }

    public function setProviderMetadata(array $providerMetadata = []): self
    {
        $this->providerMetadata = $providerMetadata;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getLength(): ?string
    {
        return $this->length;
    }

    public function setLength(?string $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): self
    {
        $this->caption = $caption;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isMain(): bool
    {
        return $this->main;
    }

    public function setMain(bool $main): self
    {
        $this->main = $main;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    // ################################################
    //               Helper Methods                  #
    // ################################################

    public function setNewBinaryContent(mixed $binaryContent): void
    {
        $this->newBinaryContent = $binaryContent;
    }

    public function getNewBinaryContent(): mixed
    {
        return $this->newBinaryContent;
    }

    /**
     * @return $this
     */
    public function setBinaryContent(mixed $binaryContent): self
    {
        $this->previousProviderReference = $this->providerReference;
        $this->providerReference = null;
        $this->binaryContent = $binaryContent;

        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getBinaryContent(): mixed
    {
        return $this->binaryContent;
    }

    /**
     * Resets Binary content
     */
    public function resetBinaryContent(): void
    {
        $this->binaryContent = null;
    }

    public function getMetadataValue(string $name, mixed $default = null): mixed
    {
        $metadata = $this->getProviderMetadata();

        return $metadata[$name] ?? $default;
    }

    /**
     * @return $this
     */
    public function setMetadataValue(string $name, mixed $value): self
    {
        $metadata = $this->getProviderMetadata();
        $metadata[$name] = $value;
        $this->setProviderMetadata($metadata);

        return $this;
    }

    /**
     * @return $this
     */
    public function unsetMetadataValue(string $name): self
    {
        $metadata = $this->getProviderMetadata();
        unset($metadata[$name]);
        $this->setProviderMetadata($metadata);

        return $this;
    }

    public function getExtension(): string
    {
        return pathinfo($this->getProviderReference(), \PATHINFO_EXTENSION);
    }

    public function getBox(): Box
    {
        return new Box($this->width, $this->height);
    }

    public function getPreviousProviderReference(): ?string
    {
        return $this->previousProviderReference;
    }

    /**
     * @ORM\PrePersist
     *
     * @ORM\PreUpdate
     */
    public function preFlush(): void
    {
        $timestamp = null;

        try {
            $timestamp = new DateTime('now');
        } catch (Exception) {
        }

        $this->setUpdatedAt($timestamp);

        if (null === $this->getCreatedAt()) {
            $this->setCreatedAt($timestamp);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName() ?: 'n/a';
    }

    /**
     * Clone the Media
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
