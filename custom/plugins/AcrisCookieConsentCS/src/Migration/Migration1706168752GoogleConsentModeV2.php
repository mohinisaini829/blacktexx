<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1706168752GoogleConsentModeV2 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1706168752;
    }

    public function update(Connection $connection): void
    {
        $query = <<< SQL
             ALTER TABLE `acris_cookie`
                ADD `google_cookie_consent_mode` JSON NULL;
        SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
