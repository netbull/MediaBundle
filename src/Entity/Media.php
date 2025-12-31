<?php

namespace NetBull\MediaBundle\Entity;

use DateTime;
use DateTimeInterface;
use Exception;
use Imagine\Image\Box;
use Doctrine\ORM\Mapping as ORM;
use NetBull\MediaBundle\Repository\MediaRepository;

#[ORM\Table(name: 'media')]
#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Media implements MediaInterface
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $name = null;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $enabled = false;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $providerName = null;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $providerReference = null;

    /**
     * @var array
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $providerMetadata = [];

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $width = null;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $height = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $length = null;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $size = null;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $contentType = null;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $context = null;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $caption = null;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $position = null;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private bool $main = false;

    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    /**
     * @var DateTimeInterface
     */
    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $updatedAt;

    #################################################
    #               Helper Properties               #
    #################################################

    private mixed $binaryContent = null;

    private mixed $newBinaryContent = null;

    /**
     * @var string|null
     */
    private ?string $previousProviderReference = null;

    public function __construct()
    {
        try {
            $this->createdAt = new DateTime('now');
            $this->updatedAt = new DateTime('now');
        } catch (Exception) {}
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return Media
     */
    public function setName(?string $name): Media
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return Media
     */
    public function setEnabled(bool $enabled): Media
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    /**
     * @param string|null $providerName
     * @return Media
     */
    public function setProviderName(?string $providerName): Media
    {
        $this->providerName = $providerName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    /**
     * @param string|null $providerReference
     * @return Media
     */
    public function setProviderReference(?string $providerReference): Media
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    /**
     * @return array
     */
    public function getProviderMetadata(): array
    {
        return $this->providerMetadata;
    }

    /**
     * @param array $providerMetadata
     * @return Media
     */
    public function setProviderMetadata(array $providerMetadata = []): Media
    {
        $this->providerMetadata = $providerMetadata;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int|null $width
     * @return Media
     */
    public function setWidth(?int $width): Media
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @param int|null $height
     * @return Media
     */
    public function setHeight(?int $height): Media
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLength(): ?string
    {
        return $this->length;
    }

    /**
     * @param string|null $length
     * @return Media
     */
    public function setLength(?string $length): Media
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @param int|null $size
     * @return Media
     */
    public function setSize(?int $size): Media
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @param string|null $contentType
     * @return Media
     */
    public function setContentType(?string $contentType): Media
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * @param string|null $context
     * @return Media
     */
    public function setContext(?string $context): Media
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * @param string|null $caption
     * @return Media
     */
    public function setCaption(?string $caption): Media
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int|null $position
     * @return Media
     */
    public function setPosition(?int $position): Media
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->main;
    }

    /**
     * @param bool $main
     * @return Media
     */
    public function setMain(bool $main): Media
    {
        $this->main = $main;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface $createdAt
     * @return Media
     */
    public function setCreatedAt(DateTimeInterface $createdAt): Media
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTimeInterface $updatedAt
     * @return Media
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): Media
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #################################################
    #               Helper Methods                  #
    #################################################

    /**
     * @param mixed $binaryContent
     */
    public function setNewBinaryContent(mixed $binaryContent): void
    {
        $this->newBinaryContent = $binaryContent;
    }

    /**
     * @return mixed
     */
    public function getNewBinaryContent(): mixed
    {
        return $this->newBinaryContent;
    }

    /**
     * @param mixed $binaryContent
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

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getMetadataValue(string $name, mixed $default = null): mixed
    {
        $metadata = $this->getProviderMetadata();

        return $metadata[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $value
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
     * @param string $name
     * @return $this
     */
    public function unsetMetadataValue(string $name): self
    {
        $metadata = $this->getProviderMetadata();
        unset($metadata[$name]);
        $this->setProviderMetadata($metadata);

        return $this;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getProviderReference(), PATHINFO_EXTENSION);
    }

    /**
     * @return Box
     */
    public function getBox(): Box
    {
        return new Box($this->width, $this->height);
    }

    /**
     * @return string|null
     */
    public function getPreviousProviderReference(): ?string
    {
        return $this->previousProviderReference;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preFlush(): void
    {
        $timestamp = null;
        try {
            $timestamp = new DateTime('now');
        } catch (Exception) {}

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
