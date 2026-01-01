<?php declare(strict_types=1);

namespace Myfav\Inquiry\Migration;

use DateTime;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\Traits\ImportTranslationsTrait;
use Shopware\Core\Migration\Traits\Translations;
use Myfav\Inquiry\MyfavInquiry;

class Migration1688319937 extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1688319937;
    }

    public function update(Connection $connection): void
    {
        $numberRangeId = Uuid::randomBytes();
        $numberRangeTypeId = Uuid::randomBytes();

        $this->insertNumberRange($connection, $numberRangeId, $numberRangeTypeId);
        $this->insertTranslations($connection, $numberRangeId, $numberRangeTypeId);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function insertNumberRange(Connection $connection, string $numberRangeId, string $numberRangeTypeId): void
    {
        $connection->insert('number_range_type', [
            'id' => $numberRangeTypeId,
            'global' => 1,
            'technical_name' => MyfavInquiry::OFFER_NUMBER_RANGE,
            'created_at' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        $connection->insert('number_range', [
            'id' => $numberRangeId,
            'type_id' => $numberRangeTypeId,
            'global' => 1,
            'pattern' => '{n}',
            'start' => 10000,
            'created_at' => (new DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);
    }

    private function insertTranslations(Connection $connection, string $numberRangeId, string $numberRangeTypeId): void
    {
        $numberRangeTranslations = new Translations(
            [
                'number_range_id' => $numberRangeId,
                'name' => 'Angebot',
            ],
            [
                'number_range_id' => $numberRangeId,
                'name' => 'Offer',
            ]
        );

        $numberRangeTypeTranslations = new Translations(
            [
                'number_range_type_id' => $numberRangeTypeId,
                'type_name' => 'Angebot',
            ],
            [
                'number_range_type_id' => $numberRangeTypeId,
                'type_name' => 'Offer',
            ]
        );

        $this->importTranslation(
            'number_range_translation',
            $numberRangeTranslations,
            $connection
        );

        $this->importTranslation(
            'number_range_type_translation',
            $numberRangeTypeTranslations,
            $connection
        );
    }
}
