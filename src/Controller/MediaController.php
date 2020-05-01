<?php

namespace NetBull\MediaBundle\Controller;

use NetBull\MediaBundle\Provider\Pool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Class MediaController
 * @package NetBull\MediaBundle\Controller
 */
class MediaController extends AbstractController
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * MediaController constructor.
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @param MediaInterface $media
     * @return MediaProviderInterface
     */
    public function getProvider(MediaInterface $media)
    {
        return $this->pool->getProvider($media->getProviderName());
    }

    /**
     * @param $id
     * @param Request $request
     * @param string $format
     * @return BinaryFileResponse|Response
     */
    public function downloadAction($id, Request $request, $format = 'reference')
    {
        /** @var MediaInterface|null $media */
        $media = $this->getDoctrine()->getManager()->getRepository(Media::class)->find($id);

        if (!$media) {
            throw new NotFoundHttpException(sprintf('unable to find the media with the id : %s', $id));
        }

        if (!$this->pool->getDownloadSecurity($media)->isGranted($media, $request)) {
            throw new AccessDeniedException();
        }

        $response = $this->getProvider($media)->getDownloadResponse($media, $format, $this->pool->getDownloadMode($media));

        if ($response instanceof BinaryFileResponse) {
            $response->prepare($request);
        }

        return $response;
    }
}
