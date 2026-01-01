<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar\Util\Lifecycle;

use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class Uninstaller
{
    public function __construct(private readonly EntityRepository $mediaFolderRepository, private readonly EntityRepository $mediaRepository, private readonly EntityRepository $mediaDefaultFolderRepository, private readonly EntityRepository $mediaFolderConfigRepository)
    {
    }

    public function uninstall(Context $context): void
    {
        $mediaFolders = $this->getMediaFolders($context);
        if ($mediaFolders->count() <= 0) {
            return;
        }

        foreach ($mediaFolders as $mediaFolder) {
            $this->removeTemplateMedia($mediaFolder, $context);
            $this->removeDefaultMediaFolder($mediaFolder, $context);
            $this->removeMediaFolder($mediaFolder, $context);
            $this->removeMediaFolderConfig($mediaFolder, $context);
        }
    }

    private function getMediaFolders(Context $context): MediaFolderCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('defaultFolder');
        $criteria->addAssociation('media');
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter(
                        'media_folder.defaultFolder.entity',
                        'zenit_notification_banner_media'
                    ),
                ]
            )
        );

        /** @var MediaFolderCollection $mediaFolderCollection */
        $mediaFolderCollection = $this->mediaFolderRepository->search($criteria, $context)->getEntities();

        return $mediaFolderCollection;
    }

    private function removeTemplateMedia(MediaFolderEntity $mediaFolder, Context $context): void
    {
        $mediaIds = [];
        foreach ($mediaFolder->getMedia() as $media) {
            $mediaIds[] = ['id' => $media->getId()];
        }

        if (!empty($mediaIds)) {
            $this->mediaRepository->delete($mediaIds, $context);
        }
    }

    private function removeDefaultMediaFolder(MediaFolderEntity $mediaFolder, Context $context): void
    {
        $this->mediaDefaultFolderRepository->delete([['id' => $mediaFolder->getDefaultFolderId()]], $context);
    }

    private function removeMediaFolder(MediaFolderEntity $mediaFolder, Context $context): void
    {
        $this->mediaFolderRepository->delete([['id' => $mediaFolder->getId()]], $context);
    }

    private function removeMediaFolderConfig(MediaFolderEntity $mediaFolder, Context $context): void
    {
        $this->mediaFolderConfigRepository->delete([['id' => $mediaFolder->getConfigurationId()]], $context);
    }
}
