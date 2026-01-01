<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1628172267UpdateContentSliderConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1628172267;
    }

    public function update(Connection $connection): void
    {
        // Add new properties
        $connection->executeStatement('
            UPDATE cms_slot_translation AS translation
            INNER JOIN cms_slot AS slot
            ON translation.cms_slot_id=slot.id
            SET
            translation.config = JSON_SET(
                translation.config,
                \'$.sliderSettings.value.autoplayHoverPause\',
                JSON_EXTRACT(translation.config,
                    \'$.sliderSettings.value.autoplayHoverpause\'),
                \'$.sliderSettings.value.itemsMode\', \'responsive-automatic\',
                \'$.sliderSettings.value.itemsMobile\', \'1\',
                \'$.sliderSettings.value.itemsTablet\', \'1\',
                \'$.sliderSettings.value.itemsDesktop\', \'1\',
                \'$.sliderSettings.value.slideByMobile\', \'1\',
                \'$.sliderSettings.value.slideByTablet\', \'1\',
                \'$.sliderSettings.value.slideByDesktop\', \'1\',
                \'$.settings.value.sizingMode\', \'responsive-min-height\',
                \'$.settings.value.minAspectRatioWidth\', \'2\',
                \'$.settings.value.minAspectRatioHeight\', \'1\'
            )
            WHERE type=\'solid-ase-content-slider\';
        ');

        // Remove old properties
        $connection->executeStatement('
            UPDATE cms_slot_translation AS translation
            INNER JOIN cms_slot AS slot
            ON translation.cms_slot_id=slot.id
            SET
            translation.config = JSON_REMOVE(translation.config,
                \'$.sliderSettings.value.autoplayHoverpause\')
            WHERE type=\'solid-ase-content-slider\';
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
