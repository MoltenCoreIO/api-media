# API Media implementation

Based on amazing work of https://github.com/benjaminrau/media-api

## Dependencies

* api-platform/core
* Symfony 3.x
* sonata-project/media-bundle 3.8

## Setup

1. Install dependencies
2. copy file into your src/MediaBundle
3. Register the bundle into you composer.json (autoload) and AppKernel.php

	new MediaBundle\MediaBundle(),

## Configuration file

app/config/sonata_media.yml

```yaml

sonata_media:
    force_disable_category: true
    class:
        media: MediaBundle\Entity\Media
    #    gallery: MyVendor\MediaBundle\Entity\Gallery
    #    gallery_has_media: MyVendor\MediaBundle\Entity\GalleryHasMedia
    db_driver: doctrine_orm # or doctrine_mongodb, doctrine_phpcr it is mandatory to choose one here
    default_context: image # you need to set a context
    #providers:
    #    image:
    #        thumbnail:  sonata.media.thumbnail.liip_imagine
    contexts:
        default:  # the default context is mandatory
            providers:
                - sonata.media.provider.file
                - sonata.media.provider.image
                - sonata.media.provider.youtube
                - sonata.media.provider.vimeo

            formats:
                small: { width: 100 , quality: 70}
                big:   { width: 500 , quality: 70}
        image:
            providers:
                - sonata.media.provider.image
            formats:
                small: { width: 100 , quality: 70}
                big:   { width: 500 , quality: 70}
    cdn:
        server:
            path: /uploads # http://media.sonata-project.org/

    filesystem:
        local:
            directory:  "%kernel.root_dir%/../web/uploads"
            create:     false
```

Autowiring: app/config/services.yml

```yaml
	
	[...]

	MediaBundle\:
        resource: '../../src/MediaBundle/*'
        # you can exclude directories or files
        # but if a service is unused, it's removed anyway
        exclude: '../../src/MediaBundle/{Entity,Repository}'

    # controllers are imported separately to make sure they're public
    # and have a tag that allows actions to type-hint services
    
    [...]

    MediaBundle\Action\:
        resource: '../../src/MediaBundle/Action'
        public: true
        tags: ['controller.service_arguments']

    [...]

```