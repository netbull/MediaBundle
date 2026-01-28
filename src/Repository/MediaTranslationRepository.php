<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use NetBull\MediaBundle\Entity\MediaInterface;

class MediaTranslationRepository extends EntityRepository
{
    /**
     * @throws NonUniqueResultException
     */
    public function getCaptionByLocale(int|MediaInterface $media, string $locale): string
    {
        $qb = $this->createQueryBuilder('pt');
        $translation = $qb->where($qb->expr()->eq('pt.translatable', ':media'))
            ->andWhere($qb->expr()->eq('pt.locale', ':locale'))
            ->setParameters(new ArrayCollection([
                new Parameter('media', $media),
                new Parameter('locale', $locale),
            ]))
            ->getQuery()
            ->getOneOrNullResult();

        if (!$translation) {
            return '';
        }

        return $translation->getCaption();
    }
}
