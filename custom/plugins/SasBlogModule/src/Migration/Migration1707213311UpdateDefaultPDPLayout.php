<?php
declare(strict_types=1);

namespace Sas\BlogModule\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\Traits\ImportTranslationsTrait;
use Shopware\Core\Migration\Traits\Translations;

class Migration1707213311UpdateDefaultPDPLayout extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1707213311;
    }

    public function update(Connection $connection): void
    {
        $section = $connection->fetchAssociative(
            '
            SELECT id, version_id
            FROM cms_section
            WHERE cms_page_id = :cmsPageId
        ',
            ['cmsPageId' => Uuid::fromHexToBytes(Defaults::CMS_PRODUCT_DETAIL_PAGE)]
        );

        if ($section === false) {
            return;
        }

        $sectionId = $section['id'];
        $versionId = $section['version_id'];

        $blockId = Uuid::randomBytes();
        $block = [
            'id' => $blockId,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'cms_section_id' => $sectionId,
            'version_id' => $versionId,
            'cms_section_version_id' => $versionId,
            'locked' => 0,
            'position' => 4,
            'type' => 'blog-assignment',
            'name' => 'Blog Assignment',
            'margin_top' => '20px',
            'margin_bottom' => '20px',
            'margin_left' => '20px',
            'margin_right' => '20px',
        ];

        $connection->insert('cms_block', $block);

        $slotId = Uuid::randomBytes();
        $slot = [
            'id' => $slotId,
            'locked' => 0,
            'cms_block_id' => $blockId,
            'cms_block_version_id' => $versionId,
            'version_id' => $versionId,
            'type' => 'blog-assignment',
            'slot' => 'content',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ];

        $connection->insert('cms_slot', $slot);

        $slotTranslation = [
            'cms_slot_id' => $slotId,
            'cms_slot_version_id' => $versionId,
            'config' => json_encode([
                'boxLayout' => ['source' => 'static', 'value' => 'standard'],
                'displayMode' => ['source' => 'static', 'value' => 'standard'],
                'elMinWidth' => ['value' => '200px', 'source' => 'static'],
                'product' => ['value' => null, 'source' => 'static'],
            ]),
        ];

        $slotTranslations = new Translations($slotTranslation, $slotTranslation);

        $this->importTranslation('cms_slot_translation', $slotTranslations, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
