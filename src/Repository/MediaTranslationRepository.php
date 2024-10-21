<?php

namespace NetBull\MediaBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use NetBull\MediaBundle\Entity\MediaInterface;

class MediaTranslationRepository extends EntityRepository
{
    /**
     * @param int|MediaInterface $media
     * @param string $locale
     * @return string
     * @throws NonUniqueResultException
     */
    public function getCaptionByLocale(int|MediaInterface $media, string $locale): string
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
