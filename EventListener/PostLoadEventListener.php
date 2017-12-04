<?php
namespace MediaBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use MediaBundle\Entity\Media;
use Sonata\MediaBundle\Provider\MediaProviderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;

class PostLoadEventListener
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(Container $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $class = $this->container->getParameter('sonata.media.media.class');
        $entity = $args->getEntity();
        if ($entity instanceof $class && $entity->getProviderName()) {
            /** @var MediaProviderInterface $provider */
            $entityContext = $entity->getContext();
            $provider = $this->container->get($entity->getProviderName());
            // Add format from context
            foreach ($provider->getFormats() as $key => $defintion) {
                if ($key == 'admin') {
                    break;
                }
                list($context, $formatName) = explode('_', $key);
                if ($context == $entityContext) {
                    $this->addFormat($context, $formatName, $provider, $entity);
                }
            }
            // Add reference
            $this->addFormat('reference', null, $provider, $entity);
        }
    }

    /**
     * Add format to Media Entity (serialization output)
     * @param string $context
     * @param ?string $formatName
     * @param MediaProviderInterface $provider
     * @param Media $entity
     */
    private function addFormat($context, $formatName = null, $provider, $entity)
    {
        $key = $context;
        if (!is_null($formatName)) {
            $key = $context.'_'.$formatName;
        }
        $format = $provider->getHelperProperties($entity, $key);
        if (isset($format['src']) && strpos($format['src'], '/') === 0 && $this->requestStack->getCurrentRequest()) {
            $format['src'] = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $format['src'];
        }
        $format['context'] = $context;
        $format['format'] = $formatName;
        unset($format['srcset']);
        unset($format['sizes']);
        $entity->addFormat($format);
    }
}
