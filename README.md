# NetBull MediaBundle

A Symfony bundle for managing media — images, files and video providers (YouTube, Vimeo, Youku) —
with pluggable storage (local filesystem or Amazon S3), thumbnail generation and configurable
access-control strategies for downloads/views. Originally a trimmed fork of
[SonataMediaBundle](https://github.com/sonata-project/SonataMediaBundle).

- **PHP:** >= 8.3
- **Symfony:** 7.4 (LTS)

## Installation

```console
composer require netbull/media-bundle
```

### Register the bundle

With Symfony Flex this is automatic. Otherwise add it to `config/bundles.php`:

```php
return [
    // ...
    NetBull\MediaBundle\NetBullMediaBundle::class => ['all' => true],
];
```

### Import the routes

The download/view endpoints live in the bundle. Add to `config/routes.yaml`:

```yaml
netbull_media:
    resource: '@NetBullMediaBundle/config/routes.yaml'
```

This registers `netbull_media_view` (`/media/view/{id}/{format}`) and
`netbull_media_download` (`/media/download/{id}/{format}`).

## Configuration

Create `config/packages/netbull_media.yaml`. A minimal local-development setup:

```yaml
netbull_media:
    default_context: default

    filesystem:
        local:
            directory: '%kernel.project_dir%/public/uploads/media'
            create: true

    cdn:
        server:
            path: /uploads/media

    contexts:
        default:
            providers:
                - netbull_media.provider.image
            formats:
                thumb:  { width: 150, height: 150 }
                normal: { width: 600 }
```

Each **context** declares which providers it accepts, its image **formats**, and the
**download/view** security strategy. See [`docs/example_config.yaml`](docs/example_config.yaml)
for a full, annotated example (multiple contexts, S3, signed URLs, custom strategies).

### Available services

| Kind | Service id |
|------|-----------|
| Providers | `netbull_media.provider.image`, `.file`, `.youtube`, `.vimeo`, `.youku` |
| Filesystems | `netbull_media.filesystem.local`, `netbull_media.filesystem.s3` |
| CDN | `netbull_media.cdn.server`, `netbull_media.cdn.local.server` |
| Security strategies | `netbull_media.security.public_strategy`, `.forbidden_strategy`, `.superadmin_strategy`, `.connected_strategy`, `.hash_strategy` |

> **Upload restrictions** — each provider's `allowed_extensions` / `allowed_mime_types` are
> enforced on upload using the file's **sniffed** content (not the client-supplied name/type).
> A file is accepted only if it matches at least one of the configured lists.

> **S3 ACL** — default to `acl: private` and serve public assets through the CDN; a `public-read`
> ACL makes objects world-readable and bypasses the bundle's download/view security strategies.

> **Secured downloads** — for contexts behind a download/view security strategy, S3-backed media is
> served by redirecting to a short-lived (300s) **pre-signed S3 URL**, so the file streams S3 →
> client and never passes through PHP. Local storage streams the file in chunks. Either way the
> access-control check runs in the controller before the response is issued.

> **Video providers (SSRF)** — video providers fetch oEmbed metadata and remote thumbnails over
> HTTP. Thumbnail URLs are validated before fetching (http/https to public hosts only; private,
> loopback and cloud-metadata addresses are refused) and redirects are disabled. To route this
> egress through your own controls, enable `framework.http_client` and the bundle will use the
> configured client.

### Thumbnail generation (sync or async)

By default thumbnails are generated **in-process** when the media is flushed. To offload resizing
to a worker, enable the async strategy:

```yaml
netbull_media:
    thumbnail:
        async: true
```

> **Optional dependency** — async mode needs the [Messenger](https://symfony.com/doc/current/messenger.html)
> component, which the bundle treats as an optional (suggested) dependency. Install it before enabling
> async:
>
> ```console
> composer require symfony/messenger
> ```
>
> The message handler is only registered when `symfony/messenger` is installed, and enabling
> `thumbnail.async: true` without it fails fast with a clear error at container compile time. In
> the default sync mode the bundle works without Messenger.

When `async: true`, the bundle dispatches a
`NetBull\MediaBundle\Message\GenerateThumbnailMessage` per format to the Messenger bus. Route it to
an async transport so a worker does the resizing (worker recycling provides the memory isolation
that long-running image processing needs):

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            NetBull\MediaBundle\Message\GenerateThumbnailMessage: async
```

```console
php bin/console messenger:consume async --memory-limit=256M
```

With no transport routing configured, Messenger handles the message synchronously, so async mode is
safe to enable before you have a worker.

## Usage

### Attach media to your entity

The bundle ships a mapped `NetBull\MediaBundle\Entity\Media` entity — reference it from your own
entities:

```php
use Doctrine\ORM\Mapping as ORM;
use NetBull\MediaBundle\Entity\Media;

#[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist', 'remove'])]
private ?Media $image = null;
```

### Upload

Set the binary content and the provider/context, then persist. The bundle's Doctrine listener
transforms the upload, stores it and generates thumbnails on flush:

```php
$media = new Media();
$media->setContext('default');
$media->setProviderName('netbull_media.provider.image');
$media->setBinaryContent($uploadedFile); // Symfony UploadedFile / SplFileInfo / path

$em->persist($media);
$em->flush();
```

In forms, use `NetBull\MediaBundle\Form\Type\MediaType` (or `MediaShortType`) with the `provider`
and `context` options.

### Render in Twig

The bundle registers these Twig **filters**:

```twig
{# Public URL for a format #}
<img src="{{ media|path('normal') }}">

{# Signed (access-controlled) URL — image/file providers #}
<img src="{{ media|secure_path(user_identifier, 'normal') }}">

{# Rendered <img>/thumbnail markup #}
{{ media|thumbnail('thumb') }}

{# Rendered provider view (image tag, video embed, file link) #}
{{ media|view('normal') }}
```

### Autowiring

```php
use NetBull\MediaBundle\Provider\PoolInterface;
use NetBull\MediaBundle\Signature\SignatureHasherInterface;

public function __construct(
    private PoolInterface $pool,
    private SignatureHasherInterface $signatureHasher,
) {}
```

## Console commands

| Command | Description |
|---------|-------------|
| `netbull:media:create-thumbnail <id> [format]` | Generate a thumbnail for one media |
| `netbull:media:resize [context]` | Generate missing thumbnails |
| `netbull:media:sync-thumbnails` | Regenerate thumbnails |
| `netbull:media:clone <id>` | Clone a media (and its stored file) |

## Development

```console
composer test        # PHPUnit
composer phpstan     # static analysis
composer cs-check    # coding standards (php-cs-fixer, dry-run)
composer check       # all of the above
```

## License

[MIT](LICENSE)
