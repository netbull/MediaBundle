# NetBull MediaBundle

A Symfony bundle for managing media files (images, files, and video providers like YouTube, Vimeo, and Youku).

## Installation

```bash
composer require netbull/media-bundle
```

## Configuration

Add the bundle configuration to your `config/packages/netbull_media.yaml`:

```yaml
netbull_media:
    default_context: default
    contexts:
        default:
            providers:
                - netbull_media.provider.image
                - netbull_media.provider.file
            formats:
                thumb: { width: 100, height: 100 }
                normal: { width: 400, height: 400 }
    cdn:
        server:
            path: /uploads/media
    filesystem:
        local:
            directory: '%kernel.project_dir%/public/uploads/media'
```

## Features

- Multiple media providers (Image, File, YouTube, Vimeo, Youku)
- Flexible thumbnail generation
- S3 and local filesystem support
- Configurable security strategies for download/view access
- Twig extension for easy template integration

## Usage

### Autowiring

The bundle provides interfaces for autowiring:

```php
use NetBull\MediaBundle\Provider\PoolInterface;
use NetBull\MediaBundle\Signature\SignatureHasherInterface;

class MyService
{
    public function __construct(
        private PoolInterface $pool,
        private SignatureHasherInterface $signatureHasher,
    ) {}
}
```

### Twig Functions

```twig
{# Generate thumbnail #}
{{ media|thumbnail('thumb') }}

{# Generate path #}
{{ media|path('normal') }}

{# Render view #}
{{ media|view('normal') }}
```

## License

AGPL-3.0-or-later
