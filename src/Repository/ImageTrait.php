<?php

namespace NetBull\MediaBundle\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\AbstractQuery;
use NetBull\MediaBundle\Entity\Media;

trait ImageTrait
{
    /**
     * @param string $type
     * @param array $images
     * @return void
     */
    public function reorderImages(string $type, array $images): void
    {
        $sql = 'SET @i=0; SET @Count=0; UPDATE media SET `position` = @Count+(@i:=@i+1)-1 WHERE `id` IN (:images) AND `context` = :type ORDER BY FIELD(id,:images)';

        $params = [
            'images' => $images,
            'type' => $type
        ];

        $types = [
            'images' => ArrayParameterType::INTEGER,
        ];

        $connection = $this->getEntityManager()->getConnection();
        $connection->executeUpdate($sql, $params, $types);
    }

    /**
     * @param mixed $object
     * @param bool $orderById
     * @return array|null
     */
    public function getImages(mixed $object, bool $orderById = false): ?array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial o.{id}', 'partial m.{' . MediaRepository::MEDIA_FIELDS . '}')
            ->from($this->getEntityName(), 'o')
            ->leftJoin('o.photos', 'm')
            ->where($qb->expr()->eq('o.id', ':object'))
            ->orderBy('m.position', 'ASC')
            ->setParameter('object', $object);

        if ($orderById) {
            $qb->addOrderBy('m.id', 'ASC');
        }

        $result = $qb->getQuery()->getSingleResult(AbstractQuery::HYDRATE_ARRAY);
        return $result['photos'] ?? null;
    }

    /**
     * @param array $images
     * @return array
     */
    public function getImagesByIds(array $images): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial m.{' . MediaRepository::MEDIA_FIELDS . '}')
            ->from(Media::class, 'm')
            ->where($qb->expr()->in('m.id', ':images'))
            ->orderBy('m.position')
            ->setParameter('images', $images);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param mixed $object
     * @return int
     */
    public function getImageIndex(mixed $object): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o.id', $qb->expr()->countDistinct('m'))
            ->from($this->getEntityName(), 'o')
            ->leftJoin('o.photos', 'm')
            ->where($qb->expr()->eq('o.id', ':object'))
            ->setParameter('object', $object);

        $result = $qb->getQuery()->getScalarResult();

        $index = 0;
        if (!empty($result) && !empty($result[0][1])) {
            $index = (int)$result[0][1];
        }

        return $index;
    }
}
