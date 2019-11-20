<?php

namespace NetBull\MediaBundle\Model;

use DateTime;
use Imagine\Image\Box;

/**
 * Interface MediaInterface
 * @package NetBull\MediaBundle\Model
 */
interface MediaInterface
{
    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name);

    /**
     * @return bool $enabled
     */
    public function isEnabled();

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled);

    /**
     * @return string|null $providerName
     */
    public function getProviderName();

    /**
     * @param string|null $providerName
     * @return $this
     */
    public function setProviderName(?string $providerName);

    /**
     * @return string|null $providerReference
     */
    public function getProviderReference();

    /**
     * @param string|null $providerReference
     * @return $this
     */
    public function setProviderReference(?string $providerReference);

    /**
     * @return array $providerMetadata
     */
    public function getProviderMetadata();

    /**
     * @param array $providerMetadata
     * @return $this
     */
    public function setProviderMetadata(array $providerMetadata = []);

    /**
     * @return int|null $width
     */
    public function getWidth();

    /**
     * @param int|null $width
     * @return $this
     */
    public function setWidth(?int $width);

    /**
     * @return int|null $height
     */
    public function getHeight();

    /**
     * @param int|null $height
     * @return $this
     */
    public function setHeight(?int $height);

    /**
     * @return float|null $length
     */
    public function getLength();

    /**
     * @param float|null $length
     * @return $this
     */
    public function setLength(?float $length);

    /**
     * @return int|null $size
     */
    public function getSize();

    /**
     * @param int|null $size
     * @return $this
     */
    public function setSize(?int $size);

    /**
     * @return string|null $contentType
     */
    public function getContentType();

    /**
     * @param string|null $contentType
     * @return $this
     */
    public function setContentType(?string $contentType);

    /**
     * @return string|null $context
     */
    public function getContext();

    /**
     * @param string|null $context
     * @return $this
     */
    public function setContext(?string $context);

    /**
     * @return string|null $caption
     */
    public function getCaption();

    /**
     * @param string|null $caption
     * @return $this
     */
    public function setCaption(?string $caption);

    /**
     * @return int|null
     */
    public function getPosition();

    /**
     * @param int|null $position
     * @return $this
     */
    public function setPosition(int $position);

    /**
     * @return boolean
     */
    public function isMain();

    /**
     * @param bool $main
     * @return $this
     */
    public function setMain(bool $main);

    /**
     * @return DateTime
     */
    public function getCreatedAt();

    /**
     * @param DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt);

    /**
     * @return DateTime
     */
    public function getUpdatedAt();

    /**
     * @param DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt(DateTime $updatedAt);

    #################################################
    #               Helper Methods                  #
    #################################################

    /**
     * @param mixed $binaryContent
     */
    public function setBinaryContent($binaryContent);

    /**
     * @return mixed
     */
    public function getBinaryContent();

    /**
     * @param mixed $binaryContent
     */
    public function setNewBinaryContent($binaryContent);

    /**
     * @return mixed
     */
    public function getNewBinaryContent();

    /**
     * @param string $name
     * @param null $default
     */
    public function getMetadataValue($name, $default = null);

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setMetadataValue($name, $value);

    /**
     * Remove a named data from the metadata.
     *
     * @param string $name
     */
    public function unsetMetadataValue($name);

    /**
     * @return string
     */
    public function getExtension();

    /**
     * @return Box
     */
    public function getBox();

    /**
     * @return string
     */
    public function getPreviousProviderReference();
}
