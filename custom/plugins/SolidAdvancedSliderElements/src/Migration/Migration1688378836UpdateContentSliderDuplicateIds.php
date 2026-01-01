<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1688378836UpdateContentSliderDuplicateIds extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1688378836;
    }

    public function update(Connection $connection): void
    {
        // Get slider configs translations from db
        $sliderConfigTranslations = $connection->executeQuery('
            SELECT cms_slot_id, language_id, config FROM cms_slot_translation AS translation
            INNER JOIN cms_slot AS slot
            ON translation.cms_slot_id=slot.id
            WHERE type=\'solid-ase-content-slider\';
        ')->fetchAllAssociative();

        // Group config translations by slot
        $sliderConfigTranslationsBySlot = [];

        foreach ($sliderConfigTranslations as $sliderConfigTranslation) {
            $slotId = Uuid::fromBytesToHex($sliderConfigTranslation['cms_slot_id']);

            if (!array_key_exists($slotId, $sliderConfigTranslationsBySlot)) {
                $sliderConfigTranslationsBySlot[$slotId] = [];
            }

            array_push($sliderConfigTranslationsBySlot[$slotId], $sliderConfigTranslation);
        }

        // Look for configs with duplicate slide ids
        $sliderConfigTranslationsWithDuplicateIdsBySlot = [];

        foreach ($sliderConfigTranslationsBySlot as $slotSliderConfigTranslation) {
            foreach ($slotSliderConfigTranslation as $sliderConfigTranslation) {
                $parsedConfig = json_decode($sliderConfigTranslation['config']);
                $slideIds = array_map(function($slide) {
                    return $slide->id;
                }, $parsedConfig->slides->value);

                if ($slideIds === array_unique($slideIds)) {
                    continue;
                }

                array_push($sliderConfigTranslationsWithDuplicateIdsBySlot, $slotSliderConfigTranslation);
            }
        }

        // Replace duplicate slide ids
        foreach ($sliderConfigTranslationsWithDuplicateIdsBySlot as $slotSliderConfigTranslation) {
            // Get translation with highest slide count
            $sliderConfigTranslationWithHighestSlideCount = null;

            foreach ($slotSliderConfigTranslation as $sliderConfigTranslation) {
                $parsedConfig = json_decode($sliderConfigTranslation['config']);
                $slideCount = count($parsedConfig->slides->value);

                if ($sliderConfigTranslationWithHighestSlideCount === null) {
                    $sliderConfigTranslationWithHighestSlideCount = $sliderConfigTranslation;
                    continue;
                }

                $parsedSliderConfigTranslationWithHighestSlideCount = json_decode($sliderConfigTranslationWithHighestSlideCount['config']);
                $currentHighestSlideCount = count($parsedSliderConfigTranslationWithHighestSlideCount->slides->value);

                if ($slideCount > $currentHighestSlideCount) {
                    $sliderConfigTranslationWithHighestSlideCount = $sliderConfigTranslation;
                }
            }

            // Get unique slide ids of that translation
            $parsedConfig = json_decode($sliderConfigTranslationWithHighestSlideCount['config']);
            $slideIds = array_map(function($slide) {
                return $slide->id;
            }, $parsedConfig->slides->value);
            $uniqueSlideIds = array_unique($slideIds);

            // Generate new slide ids for duplicates
            $newSlideIds = [];

            foreach ($slideIds as $id) {
                $newId = $id;

                if (in_array($newId, $uniqueSlideIds)) {
                    $uniqueSlideIds = array_diff($uniqueSlideIds, [$id]);
                } else {
                    $newId = Uuid::randomHex();
                }

                array_push($newSlideIds, $newId);
            }

            // Write new ids to all translations
            foreach ($slotSliderConfigTranslation as $sliderConfigTranslation) {
                $parsedConfig = json_decode($sliderConfigTranslation['config']);
                $slidesConfig = $parsedConfig->slides->value;

                foreach ($slidesConfig as $index => $slideConfig) {
                    $slideConfig->id = $newSlideIds[$index];
                }

                $parsedConfig->slides->value = $slidesConfig;
                $newSliderConfigTranslation = json_encode($parsedConfig);

                $connection->executeStatement(
                    '
                    UPDATE cms_slot_translation AS translation
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
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
