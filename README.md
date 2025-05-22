MediaBundle
==========

Mod of [SonataMediaBundle](https://github.com/sonata-project/SonataMediaBundle)

Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require netbull/media-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require netbull/media-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new NetBull\MediaBundle\NetBullMediaBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Configure the Bundle
----------------------------
Example configuration file
`app/config/netbull_media.yml`
```yaml
parameters:
    quality: 80
    formats:
        tiny: { width: 41, quality: '%quality%' }
        thumb:   { width: 223, quality: '%quality%' }
        normal:   { width: 590, quality: '%quality%' }
        big:   { width: 1280, quality: '%quality%' }

    download:
        strategy: media.security.public_strategy
        mode: http

    default_context:
        download: '%download%'
        providers:
            - media.provider.image
        formats: '%formats%'

netbull_media:
    default_context: 'default'

    providers:
        image:
            service:    media.provider.image
            resizer:    media.resizer.square
            filesystem: media.filesystem.s3
            cdn:        media.cdn.server

        file:
            service:    media.provider.file
            resizer:    false
            filesystem: media.filesystem.s3
            cdn:        media.cdn.server
            thumbnail:  media.thumbnail.format
            allowed_extensions: ['pdf', 'txt', 'rtf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pttx', 'odt', 'odg', 'odp', 'ods', 'odc', 'odf', 'odb', 'csv', 'xml', 'html']
            allowed_mime_types: ['application/pdf', 'application/x-pdf', 'application/rtf', 'text/html', 'text/rtf', 'text/plain']
    cdn:
        server:
            paths:
                - 'YOUR_CDN_DOMAIN'

    filesystem:
        s3:
          defaults: 
            region: eu-central-1 /or other aws zone/
                version: latest
                credentials:
                    key: AWS KEY
                    secret: AWS SECRET
          options:
            bucket: 'AWS BUCKET NAME'
            cache_control:  max-age=604800
            meta:
                Cache-Control: max-age=604800

    contexts:
        default: '%default_context%'
```
