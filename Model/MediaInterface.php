<?php

namespace NetBull\MediaBundle\Model;

use Imagine\Image\Box;

/**
 * Interface MediaInterface
 * @package NetBull\MediaBundle\Model
 */
interface MediaInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * Get name.
     *
     * @return string $name
     */
    public function getName();

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * Set enabled.
     *
     * @return bool $enabled
     */
    public function isEnabled();

    /**
     * Set enabled.
     *
     * @param $enabled
     * @return $this
     */
    public function setEnabled($enabled);

    /**
     * Get provider_name.
     *
     * @return string $providerName
     */
    public function getProviderName();

    /**
     * Set provider_name.
     *
     * @param string $providerName
     */
    public function setProviderName($providerName);

    /**
     * Get provider_reference.
     *
     * @return string $providerReference
     */
    public function getProviderReference();

    /**
     * Set provider_reference.
     *
     * @param string $providerReference
     */
    public function setProviderReference($providerReference);

    /**
     * Get provider_metadata.
     *
     * @return array $providerMetadata
     */
    public function getProviderMetadata();

    /**
     * Set provider_metadata.
     *
     * @param array $providerMetadata
     */
    public function setProviderMetadata(array $providerMetadata = []);

    /**
     * Get width.
     *
     * @return int $width
     */
    public function getWidth();

    /**
     * Set width.
     *
     * @param int $width
     */
    public function setWidth($width);

    /**
     * Get height.
     *
     * @return int $height
     */
    public function getHeight();

    /**
     * Set height.
     *
     * @param int $height
     */
    public function setHeight($height);

    /**
     * Get length.
     *
     * @return float $length
     */
    public function getLength();

    /**
     * Set length.
     *
     * @param float $length
     */
    public function setLength($length);

    /**
     * Get size.
     *
     * @return int $size
     */
    public function getSize();

    /**
     * Set size.
     *
     * @param int $size
     */
    public function setSize($size);

    /**
     * Get content_type.
     *
     * @return string $contentType
     */
    public function getContentType();

    /**
     * Set content_type.
     *
     * @param string $contentType
     */
    public function setContentType($contentType);

    /**
     * Get context.
     *
     * @return string $context
     */
    public function getContext();

    /**
     * Set context.
     *
     * @param string $context
     */
    public function setContext($context);

    /**
     * Get caption.
     *
     * @return string $caption
     */
    public function getCaption();

    /**
     * Set caption.
     *
     * @param string $caption
     */
    public function setCaption($caption);

    #################################################
    #                                               #
    #               Helper Methods                  #
    #                                               #
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
     * @param string $name
     * @param null   $default
     */
    public function getMetadataValue($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $value
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
