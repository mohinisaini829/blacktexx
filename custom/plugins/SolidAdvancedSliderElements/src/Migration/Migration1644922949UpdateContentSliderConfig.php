<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1644922949UpdateContentSliderConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1644922949;
    }

    public function update(Connection $connection): void
    {
        // Get slider configs from db
        $sliderConfigTranslations = $connection->executeQuery('
            SELECT cms_slot_id, language_id, config FROM cms_slot_translation AS translation
            INNER JOIN cms_slot AS slot
            ON translation.cms_slot_id=slot.id
            WHERE type=\'solid-ase-content-slider\';
        ')->fetchAllNumeric();

        // Group configs by slot
        $sliderConfigTranslationsBySlot = [];

        foreach ($sliderConfigTranslations as $sliderConfigTranslation) {
            $slotId = Uuid::fromBytesToHex($sliderConfigTranslation[0]);

            if (!array_key_exists($slotId, $sliderConfigTranslationsBySlot)) {
                $sliderConfigTranslationsBySlot[$slotId] = [];
            }

            array_push($sliderConfigTranslationsBySlot[$slotId], $sliderConfigTranslation);
        }

        // Add random ids to slides with same slot
        foreach ($sliderConfigTranslationsBySlot as $slotSliderConfigTranslation) {
            // Get highest slide count
            $maxTranslationSlideCount = 0;

            foreach ($slotSliderConfigTranslation as $sliderConfigTranslation) {
                $parsedConfig = json_decode($sliderConfigTranslation[2]);
                $slideCount = count($parsedConfig->slides->value);

                if ($maxTranslationSlideCount < $slideCount) {
                    $maxTranslationSlideCount = $slideCount;
                }
            }

            // Generate ids
            $slideIds = [];

            for ($i = 0; $i < $maxTranslationSlideCount; $i++) {
                array_push($slideIds, Uuid::randomHex());
            }

            foreach ($slotSliderConfigTranslation as $sliderConfigTranslation) {
                $parsedConfig = json_decode($sliderConfigTranslation[2]);
                $slidesConfig = $parsedConfig->slides->value;

                foreach ($slidesConfig as $index => $slideConfig) {
                    $slideConfig->id = $slideIds[$index];
                    $slideConfig->buttonTitle = "";
                    $slideConfig->linkTitle = "";
                }

                $parsedConfig->slides->value = $slidesConfig;
                $newSliderConfigTranslation = addslashes(json_encode($parsedConfig));

                // Write changes to db
                $slotId = Uuid::fromBytesToHex($sliderConfigTranslation[0]);
                $languageId = Uuid::fromBytesToHex($sliderConfigTranslation[1]);

                $connection->executeStatement('
                    UPDATE cms_slot_translation AS translation
                    SET config=\'' . $newSliderConfigTranslation . '\'
                    WHERE cms_slot_id=0x' . $slotId . ' AND language_id=0x' . $languageId . ';
                ');
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
