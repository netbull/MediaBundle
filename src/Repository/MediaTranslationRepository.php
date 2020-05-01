<?php

namespace NetBull\MediaBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Class MediaTranslationRepository
 * @package NetBull\MediaBundle\Repository
 */
class MediaTranslationRepository extends EntityRepository
{
    /**
     * @param $media
     * @param $locale
     * @return string
     * @throws NonUniqueResultException
     */
    public function getCaptionByLocale($media, $locale)
    {
        $qb = $this->createQueryBuilder('pt');
        $translation = $qb->where($qb->expr()->eq('pt.translatable', ':media'))
            ->andWhere($qb->expr()->eq('pt.locale', ':locale'))
            ->setParameters([
                'media'     => $media,
                'locale'    => $locale
            ])
            ->getQuery()
            ->getOneOrNullResult();

        if (!$translation) {
            return '';
        }

        return $translation->getCaption();
    }
}
