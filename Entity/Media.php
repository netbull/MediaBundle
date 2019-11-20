<?php

namespace NetBull\MediaBundle\Entity;

use DateTime;
use Exception;
use Imagine\Image\Box;
use Doctrine\ORM\Mapping as ORM;
use NetBull\MediaBundle\Model\MediaInterface;

/**
 * @ORM\Table(name="media")
 * @ORM\Entity(repositoryClass="NetBull\MediaBundle\Repository\MediaRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Media implements MediaInterface
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enabled = false;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $providerName;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $providerReference;

    /**
     * @var array
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $providerMetadata = [];

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $width;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $height;

    /**
     * @var float|null
     *
     * @ORM\Column(type="decimal", nullable=true)
     */
    private $length;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $size;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $contentType;

    /**
     * @var string|null
     *
     * @ORM\Column(length=64, nullable=true)
     */
    private $context;

    /**
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $caption;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $main = false;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    #################################################
    #               Helper Properties               #
    #################################################

    private $binaryContent;

    private $newBinaryContent;

    private $previousProviderReference;

    /**
     * Media constructor.
     */
    public function __construct()
    {
        try {
            $this->createdAt = new DateTime('now');
            $this->updatedAt = new DateTime('now');
        } catch (Exception $e) {}
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
     * @return float|null
     */
    public function getLength(): ?float
    {
        return $this->length;
    }

    /**
     * @param float|null $length
     * @return Media
     */
    public function setLength(?float $length): Media
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
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return Media
     */
    public function setCreatedAt(DateTime $createdAt): Media
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     * @return Media
     */
    public function setUpdatedAt(DateTime $updatedAt): Media
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #################################################
    #               Helper Methods                  #
    #################################################

    /**
     * @param $binaryContent
     */
    public function setNewBinaryContent($binaryContent)
    {
        $this->newBinaryContent = $binaryContent;
    }

    /**
     * @return mixed
     */
    public function getNewBinaryContent()
    {
        return $this->newBinaryContent;
    }

    /**
     * {@inheritdoc}
     */
    public function setBinaryContent($binaryContent)
    {
        $this->previousProviderReference = $this->providerReference;
        $this->providerReference = null;
        $this->binaryContent = $binaryContent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaryContent()
    {
        return $this->binaryContent;
    }

    /**
     * Resets Binary content
     */
    public function resetBinaryContent()
    {
        $this->binaryContent = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataValue($name, $default = null)
    {
        $metadata = $this->getProviderMetadata();

        return isset($metadata[$name]) ? $metadata[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadataValue($name, $value)
    {
        $metadata = $this->getProviderMetadata();
        $metadata[$name] = $value;
        $this->setProviderMetadata($metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetMetadataValue($name)
    {
        $metadata = $this->getProviderMetadata();
        unset($metadata[$name]);
        $this->setProviderMetadata($metadata);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return pathinfo($this->getProviderReference(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    public function getBox()
    {
        return new Box($this->width, $this->height);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousProviderReference()
    {
        return $this->previousProviderReference;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preFlush()
    {
        $timestamp = null;
        try {
            $timestamp = new DateTime('now');
        } catch (Exception $e) {}

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
