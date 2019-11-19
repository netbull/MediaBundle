<?php

namespace NetBull\MediaBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Exception;
use NetBull\MediaBundle\Entity\Media;

/**
 * Class MediaRepository
 * @package NetBull\MediaBundle\Repository
 */
class MediaRepository extends EntityRepository
{
    const MEDIA_FIELDS = 'id,enabled,context,providerReference,providerName,name,width,height,main,position,createdAt,updatedAt';

    /**
     * @param array $criteria
     * @return array|int
     */
    public function count(array $criteria)
    {
        $qb = $this->createQueryBuilder('m');
        $qb
            ->select('COUNT(m.id) as medias', 'm.context')
            ->where($qb->expr()->neq('m.context', ':context'))
            ->setParameter('context', 'avatar')
            ->groupBy('m.context')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array
     */
    public function getMediaContexts()
    {
        $qb = $this->createQueryBuilder('m');
        $contexts = $qb
            ->select('m.context')
            ->groupBy('m.context')
            ->orderBy('m.context', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        return $this->normalizeContexts($contexts);
    }

    /**
     * @param Media|int $media
     * @param bool $status
     * @return bool
     */
    public function toggleMain($media, $status = false)
    {
        $qb = $this->createQueryBuilder('m');
        $qb
            ->update($this->getEntityName(), 'm')
            ->set('m.main', ':status')
            ->setParameter('status', $status)
        ;

        if (is_array($media)) {
            $qb->where($qb->expr()->in('m.id', ':media'));
        } else {
            $qb->where($qb->expr()->eq('m.id', ':media'));
        }

        $qb->setParameter('media', $media);

        try {
            $qb->getQuery()->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    ################################################
    #               Helper Methods                 #
    ################################################

    /**
     * @param $contexts
     * @return array
     */
    private function normalizeContexts($contexts)
    {
        $tmp = ['all' => 'All'];
        foreach ($contexts as $context){
            $tmp[$context['context']] = ucwords(str_replace('_', ' ', $context['context']));
        }

        return $tmp;
    }
}
