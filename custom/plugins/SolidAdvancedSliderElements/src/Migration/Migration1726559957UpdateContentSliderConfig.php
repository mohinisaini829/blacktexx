<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1726559957UpdateContentSliderConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1726559957;
    }

    public function update(Connection $connection): void
    {
        // Get slider config translations from db
        $sliderConfigTranslations = $connection->executeQuery(
            'SELECT cms_slot_id, language_id, config FROM cms_slot_translation AS translation
            INNER JOIN cms_slot AS slot
            ON translation.cms_slot_id=slot.id
            WHERE type=\'solid-ase-content-slider\';'
        )->fetchAllAssociative();

        // Add new properties
        foreach ($sliderConfigTranslations as $sliderConfigTranslation) {
            $parsedConfig = json_decode($sliderConfigTranslation['config'], true);
            $slidesConfig = $parsedConfig['slides']['value'];

            foreach ($slidesConfig as &$slideConfig) {
                $slideConfig['publishingType'] = 'instant';
                $slideConfig['scheduledPublishingDateTime'] = null;
                $slideConfig['scheduledUnpublishingDateTime'] = null;
            }

            // Unset reference to avoid stuck references
            unset($slideConfig);

            $parsedConfig['slides']['value'] = $slidesConfig;
            $newSliderConfigTranslation = json_encode($parsedConfig);

            $connection->executeStatement(
                'UPDATE cms_slot_translation AS translation
                SET config=:config
                WHERE cms_slot_id=:slotId AND language_id=:languageId;',
                [
                    'config' => $newSliderConfigTranslation,
                    'slotId' => $sliderConfigTranslation['cms_slot_id'],
                    'languageId' => $sliderConfigTranslation['language_id']
                ]
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
