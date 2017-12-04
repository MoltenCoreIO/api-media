<?php

namespace MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonata\MediaBundle\Entity\BaseMedia;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     itemOperations={
 *          "get"={"method"="GET", "access_control"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"method"="GET", "access_control"="is_granted('ROLE_ADMIN')"},
 *          "upload"={"route_name"="api_media_upload"},
 *     },
 *     attributes={
 *          "denormalization_context"={"groups"={"in"}},
 *          "normalization_context"={"groups"={"out"}}
 *     },
 * )
 * @ORM\Entity
 * @ORM\Table(name="media")
 */
class Media extends BaseMedia
{
    const PROVIDER_IMAGE = "sonata.media.provider.image";

    const MIMETYPE_TO_PROVIDER = array(
        'image/png' => self::PROVIDER_IMAGE,
        'image/gif' => self::PROVIDER_IMAGE,
        'image/jpg' => self::PROVIDER_IMAGE,
        'image/jpeg' => self::PROVIDER_IMAGE,
        'image/bmp' => self::PROVIDER_IMAGE,
    );

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"out"})
     */
    protected $id;

    /**
     * @var array
     * @Groups({"out", "publications_get_all_out", "likes_with_author", "likes_with_publication"})
     */
    protected $formats;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getFormats()
    {
        return $this->formats;
    }

    /**
     * @param array $format
     */
    public function addFormat($format)
    {
        $this->formats[] = $format;
    }

    public static function isSupportedMimeType($mimeType)
    {
        return isset(self::MIMETYPE_TO_PROVIDER[$mimeType]);
    }

    public static function getProviderForMimeType($mimeType)
    {
        return isset(self::MIMETYPE_TO_PROVIDER[$mimeType]) ? self::MIMETYPE_TO_PROVIDER[$mimeType] : null;
    }
}
