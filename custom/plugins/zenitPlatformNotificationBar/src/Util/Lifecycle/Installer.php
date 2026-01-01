<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar\Util\Lifecycle;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class Installer
{
    public const MEDIA_FOLDER_NAME = 'Notification Banner Media';
    public const MEDIA_FOLDER_ENTITY = 'zenit_notification_banner_media';

    public function __construct(private readonly EntityRepository $mediaDefaultFolderRepository, private readonly Connection $connection)
    {
    }

    public function install(Context $context): void
    {
        $this->createMediaFolder($context);
    }

    public function createMediaFolder(Context $context): void
    {
        try {
            $defaultFolderId = Uuid::randomHex();
            $thumbnailIds = $this->connection->fetchAllAssociative('SELECT LOWER(HEX(id)) AS id from `media_thumbnail_size` WHERE width in (400, 800, 1920)');

            $this->mediaDefaultFolderRepository->upsert([
                [
                    'id' => $defaultFolderId,
                    'associationFields' => ['media'],
                    'entity' => self::MEDIA_FOLDER_ENTITY,
                    'folder' => [
                        'name' => self::MEDIA_FOLDER_NAME,
                        'useParentConfiguration' => false,
                        'configuration' => [
                            'createThumbnails' => true,
                            'thumbnailQuality' => 80,
                            'keepAspectRatio' => true,
                            'mediaThumbnailSizes' => $thumbnailIds,
                        ],
                    ],
                ],
            ], $context);
        } catch (\Exception) {
        }
    }
}
