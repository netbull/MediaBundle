<?php

namespace NetBull\MediaBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\DBAL\Connection;

use NetBull\MediaBundle\Entity\Media;

/**
 * Trait ImageTrait
 * @package NetBull\MediaBundle\Repository
 */
trait ImageTrait
{
    /**
     * @inheritdoc
     */
    public function reorderImages($type, $images)
    {
        $sql = 'SET @i=0; SET @Count=0; UPDATE yarduna.media SET `position` = @Count+(@i:=@i+1)-1 WHERE `id` IN (:images) AND `context` = :type ORDER BY FIELD(id,:images)';

        $params = [
            'images'   => $images,
            'type'     => $type
        ];

        $types = [
            'images' => Connection::PARAM_INT_ARRAY,
        ];

        $connection = $this->getEntityManager()->getConnection();
        $connection->executeUpdate($sql, $params, $types);
    }

    /**
     * @inheritdoc
     */
    public function getImages($object, $orderById = false)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial o.{id}', 'partial m.{' . MediaRepository::MEDIA_FIELDS . '}')
            ->from($this->getEntityName(), 'o')
            ->leftJoin('o.photos', 'm')
            ->where($qb->expr()->eq('o.id', ':object'))
            ->orderBy('m.position', 'ASC')
            ->setParameter('object', $object)
        ;

        if ($orderById) {
            $qb->addOrderBy('m.id', 'ASC');
        }

        $result = $qb->getQuery()->getSingleResult(Query::HYDRATE_ARRAY);
        return ($result) ? $result['photos'] : null;
    }

    /**
     * @inheritdoc
     */
    public function getImagesByIds($images)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial m.{' . MediaRepository::MEDIA_FIELDS . '}')
            ->from(Media::class, 'm')
            ->where($qb->expr()->in('m.id', ':images'))
            ->orderBy('m.position')
            ->setParameter('images', $images)
        ;

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @inheritdoc
     */
    public function getImageIndex($object)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o.id', $qb->expr()->countDistinct('m'))
            ->from($this->getEntityName(), 'o')
            ->leftJoin('o.photos', 'm')
            ->where($qb->expr()->eq('o.id', ':object'))
            ->setParameter('object', $object)
        ;

        $result = $qb->getQuery()->getScalarResult();

        $index = 0;
        if (!empty($result) && !empty($result[0][1])) {
            $index = (int)$result[0][1];
        }

        return $index;
    }
}
