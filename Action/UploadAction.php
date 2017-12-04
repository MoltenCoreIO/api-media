<?php
namespace MediaBundle\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\HttpFoundation\JsonResponse;
use MediaBundle\Dto as Dto;
use MediaBundle\Entity\Media;
use Sonata\MediaBundle\Entity\MediaManager;

class UploadAction
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Serializer $serializer, MediaManager $mediaManager, Router $router, ContainerInterface $container)
    {
        $this->serializer = $serializer;
        $this->mediaManager = $mediaManager;
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @Route(
     *     name="api_media_upload",
     *     path="/media",
     *     defaults={"_api_resource_class"="MediaBundle\Entity\Media", "_api_collection_operation_name"="upload"}
     * )
     * @Method("POST")
     */
    public function __invoke(Request $request)
    {
        // Payload json:
        // {
        //  "fileName": "myfileName.png",
        //  "data": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA4gAAAMrCAIAAACEWivZAAAAA3NCSVQICAjb4U/gAAAAGXRFWHRTb2Z0d2FyZQBnbm..."
        // }

        // Retrieve Media from request
        if ($request->getContentType() === 'json') {
            /** @var Dto\Media $mediaElementDto */
            $mediaElementDto = $this->serializer->deserialize($request->getContent(), Dto\Media::class, $request->getContentType());
        } else {
            $mediaElementDto = new Dto\Media();
            $form = $this->container->get('form.factory')->createNamed('', 'MediaBundle\Form\MediaType', $mediaElementDto);
            $form->handleRequest($request);
            if (!$form->isValid()) {
                $errors = [];
                foreach ($this->getFormErrors($form) as $error) {
                    $errors[] = $error['message'].' ';
                }
                return new JsonResponse(json_encode($errors), Response::HTTP_BAD_REQUEST, $headers = array("Content-Type" => "application/json"), true);
            }
        }

        if (
            Media::isSupportedMimeType($mediaElementDto->getMimeType()) &&
            $mediaElement = self::createMedia(
                $this->container->getParameter('sonata.media.media.class'),
                $mediaElementDto)
        ) {
            // Save Media with Manager
            $tmpFile = $mediaElement->getBinaryContent();
            $this->mediaManager->save($mediaElement);

            // Remove temporary File
            unlink($tmpFile->getRealPath());

            // Refresh Entity to trigger event and attach Formats
            $this->mediaManager->getEntityManager()->refresh($mediaElement);

            return new JsonResponse($this->serializer->serialize($mediaElement, 'jsonld'), Response::HTTP_CREATED, $headers = array("Content-Type" => "application/ld+json"), true);
        }
        return new Response('', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param string $class
     * @param Dto\Media $mediaElementDto
     * @return Media
     */
    public static function createMedia($class, Dto\Media $mediaElementDto)
    {
        /** @var Media $mediaElement */
        $mediaElement = new $class;
        $mediaElement->setBinaryContent(self::createTempFile($mediaElementDto));
        $providerName = $mediaElement->getProviderForMimeType($mediaElementDto->getMimeType());
        $providerNameParts = explode('.', $providerName);
        $mediaElement->setName($mediaElementDto->getFileName());
        $mediaElement->setContext(end($providerNameParts));
        $mediaElement->setProviderName($providerName);
        $mediaElement->setContentType($mediaElementDto->getMimeType());
        return $mediaElement;
    }

    /**
     * @param Dto\Media $mediaElementDto
     * @return UploadedFile
     */
    private static function createTempFile(Dto\Media $mediaElementDto)
    {
        if (!$binaryContent = $mediaElementDto->getBinaryContent()) {
            return false;
        }
        // @TODO: manage tmp file and remove them after save
        $temporaryFileName = tempnam(sys_get_temp_dir(), 'upload_action_') . "." . pathinfo($mediaElementDto->getFileName(), PATHINFO_EXTENSION);
        file_put_contents($temporaryFileName, $binaryContent);
        return new UploadedFile(
            $temporaryFileName,
            $mediaElementDto->getFileName()
        );
    }

    protected function getFormErrors(FormInterface $form)
    {
        $errors = array();
        // Global
        foreach ($form->getErrors() as $error) {
            $errors[] = [
                'propertyPath' => $form->getName(),
                'message' => $error->getMessage()
            ];
        }
        // Fields
        foreach ($form as $child /** @var FormInterface $child */) {
            if (!$child->isValid()) {
                if ($child->count()) {
                    foreach ($child as $subchild) {
                        if (!$subchild->isValid()) {
                            foreach ($subchild->getErrors(true) as $error) {
                                $errors[] = [
                                    'propertyPath' => $child->getName().ucfirst($subchild->getName()),
                                    'message' => $error->getMessage()
                                ];
                            }
                        }
                    }
                } else {
                    foreach ($child->getErrors(true) as $error) {
                        $errors[] = [
                            'propertyPath' => $child->getName(),
                            'message' => $error->getMessage()
                        ];
                    }
                }
            }
        }
        return $errors;
    }
}
