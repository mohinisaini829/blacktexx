<?php declare(strict_types=1);

namespace Acris\CookieConsent\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1600768693 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600768693;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            UPDATE acris_cookie
                SET cookie_id = '_ga|_gid|_gat_.+|_dc_gtm_UA-.+|ga-disable-UA-.+|__utm(a|b|c|d|t|v|x|z)|_gat|_swag_ga_.*|_gac.*'
                WHERE cookie_id = '_ga|_gid|_gat_.+|_dc_gtm_UA-.+|ga-disable-UA-.+|__utm(a|b|c|d|t|v|x|z)|_gat|_swag_ga_.*';
SQL;
        try {
            $connection->executeStatement($query);
        } catch (\Exception $e) { }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
