<?php
namespace MediaBundle\Validator\Constraints;

use MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator as ParentFileValidator;

class FileValidator extends ParentFileValidator
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $constraint->__set('maxSize', $this->container->getParameter('media_api.upload_max_filesize'));
        $constraint->mimeTypes = array_keys(Media::MIMETYPE_TO_PROVIDER);
        parent::validate($value, $constraint);
    }
}
