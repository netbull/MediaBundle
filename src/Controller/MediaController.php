<?php

namespace NetBull\MediaBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Exception\FileNotFound;
use NetBull\MediaBundle\EventListener\HashedMediaViewEvent;
use NetBull\MediaBundle\Provider\Pool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Entity\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

class MediaController extends AbstractController
{
    /**
     * @param Pool $pool
     * @param EntityManagerInterface $em
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        private Pool $pool,
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param MediaInterface $media
     * @return MediaProviderInterface
     */
    public function getProvider(MediaInterface $media): MediaProviderInterface
    {
        return $this->pool->getProvider($media->getProviderName());
    }

    /**
     * @param $id
     * @param Request $request
     * @param string $format
     * @return BinaryFileResponse|Response
     */
    public function downloadAction($id, Request $request, string $format = 'reference'): BinaryFileResponse|Response
    {
        /** @var MediaInterface|null $media */
        $media = $this->em->getRepository(Media::class)->find($id);

        if (!$media) {
            throw new NotFoundHttpException(sprintf('unable to find the media with the id : %s', $id));
        }

        if (!$this->pool->getDownloadSecurity($media)->isGranted($media, $request)) {
            throw new AccessDeniedException();
        }

        if ('netbull_media.provider.image' !== $media->getProviderName()) {
            $format = 'reference';
        }
        $provider = $this->getProvider($media);

        $filename = $media->getMetadataValue('filename');
        $headers = [
            'x-filename' => $filename,
        ];
        if ($request->query->get('inline')) {
            $headers['Content-Disposition'] = sprintf('inline; filename="%s"', $filename);
        }

        try {
            $response = $provider->getDownloadResponse($media, $provider->getFormatName($media, $format), $this->pool->getDownloadMode($media), $headers);
        } catch (FileNotFound) {
            throw $this->createNotFoundException();
        }

        if ($response instanceof BinaryFileResponse) {
            $response->prepare($request);
        }

        return $response;
    }

    /**
     * @param $id
     * @param Request $request
     * @param string $format
     * @return BinaryFileResponse|Response
     */
    public function viewAction($id, Request $request, string $format = 'reference'): BinaryFileResponse|Response
    {
        /** @var MediaInterface|null $media */
        $media = $this->em->getRepository(Media::class)->find($id);

        if (!$media) {
            throw new NotFoundHttpException(sprintf('unable to find the media with the id : %s', $id));
        }

        if (!$this->pool->getViewSecurity($media)->isGranted($media, $request)) {
            throw new AccessDeniedException();
        }

        $this->dispatcher->dispatch(new HashedMediaViewEvent($media->getId(), $request->query->get('u')));
        if ('netbull_media.provider.image' !== $media->getProviderName()) {
            $format = 'reference';
        }
        $provider = $this->getProvider($media);

        try {
            $response = $provider->getViewResponse($media, $provider->getFormatName($media, $format));
        } catch (FileNotFound) {
            throw $this->createNotFoundException();
        }

        if ($response instanceof BinaryFileResponse) {
            $response->prepare($request);
        }

        return $response;
    }
}
