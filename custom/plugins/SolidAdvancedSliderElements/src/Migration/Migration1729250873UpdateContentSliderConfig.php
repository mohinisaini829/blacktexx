<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1729250873UpdateContentSliderConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1729250873;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            UPDATE cms_slot_translation AS translation
            INNER JOIN cms_slot AS slot
            ON translation.cms_slot_id=slot.id
            SET
            translation.config = JSON_SET(
                translation.config,
                \'$.settings.value.contentBackgroundColor\',
                \'#00000080\'
            )
            WHERE type=\'solid-ase-content-slider\'
            AND JSON_EXTRACT(translation.config, \'$.settings.value.contentBackgroundColor\')=\'\';
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
