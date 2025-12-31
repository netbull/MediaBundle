<?php

namespace NetBull\MediaBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Exception;
use NetBull\MediaBundle\Entity\MediaInterface;

class MediaRepository extends EntityRepository
{
    const string MEDIA_FIELDS = 'id,enabled,context,providerReference,providerName,name,width,height,main,position,createdAt,updatedAt,caption';

    /**
     * @param array $criteria
     * @return array
     */
    public function count(array $criteria): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('COUNT(m.id) as medias', 'm.context')
            ->where($qb->expr()->neq('m.context', ':context'))
            ->setParameter('context', 'avatar')
            ->groupBy('m.context');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array
     */
    public function getMediaContexts(): array
    {
        $qb = $this->createQueryBuilder('m');
        $contexts = $qb->select('m.context')
            ->groupBy('m.context')
            ->orderBy('m.context', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return $this->normalizeContexts($contexts);
    }

    /**
     * @param MediaInterface|array|int $media
     * @param bool $status
     * @return bool
     */
    public function toggleMain(MediaInterface|array|int $media, bool $status = false): bool
    {
        $qb = $this->createQueryBuilder('m');
        $qb->update($this->getEntityName(), 'm')
            ->set('m.main', ':status')
            ->setParameter('status', $status);

        if (is_array($media)) {
            $qb->where($qb->expr()->in('m.id', ':media'));
        } else {
            $qb->where($qb->expr()->eq('m.id', ':media'));
        }

        $qb->setParameter('media', $media);

        try {
            $qb->getQuery()->execute();
            return true;
        } catch (Exception) {
            return false;
        }
    }

    ################################################
    #               Helper Methods                 #
    ################################################
    /**
     * @param array $contexts
     * @return array
     */
    private function normalizeContexts(array $contexts): array
    {
        $tmp = ['all' => 'All'];
        foreach ($contexts as $context){
            $tmp[$context['context']] = ucwords(str_replace('_', ' ', $context['context']));
        }

        return $tmp;
    }
}
