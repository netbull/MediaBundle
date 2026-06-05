<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Exception;

use RuntimeException;

/**
 * Thrown when a video provider cannot retrieve or decode the oEmbed/metadata for a video.
 *
 * Extends RuntimeException so existing broad catches keep working, while allowing callers to
 * react specifically to a metadata failure (e.g. retry vs. disable the media).
 */
class VideoMetadataException extends RuntimeException
{
}
