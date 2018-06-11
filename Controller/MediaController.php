<?php

namespace NetBull\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use NetBull\MediaBundle\Entity\Media;
use NetBull\MediaBundle\Model\MediaInterface;
use NetBull\MediaBundle\Provider\MediaProviderInterface;

/**
 * Class MediaController
 * @package NetBull\MediaBundle\Controller
 */
class MediaController extends Controller
{
    /**
     * @param MediaInterface $media
     * @return MediaProviderInterface
     */
    public function getProvider(MediaInterface $media)
    {
        return $this->get('netbull_media.pool')->getProvider($media->getProviderName());
    }

    /**
     * @throws NotFoundHttpException
     *
     * @param string $id
     * @param string $format
     *
     * @return Response
     */
    public function downloadAction($id, $format = 'reference')
    {
        $media = $this->getDoctrine()->getManager()->getRepository(Media::class)->find($id);

        if (!$media) {
            throw new NotFoundHttpException(sprintf('unable to find the media with the id : %s', $id));
        }

        if (!$this->get('netbull_media.pool')->getDownloadSecurity($media)->isGranted($media, $this->get('request_stack')->getCurrentRequest())) {
            throw new AccessDeniedException();
        }

        $response = $this->getProvider($media)->getDownloadResponse($media, $format, $this->get('netbull_media.pool')->getDownloadMode($media));

        if ($response instanceof BinaryFileResponse) {
            $response->prepare($this->get('request_stack')->getCurrentRequest());
        }

        return $response;
    }
}
