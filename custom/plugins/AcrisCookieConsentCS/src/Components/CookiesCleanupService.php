<?php declare(strict_types=1);

namespace Acris\CookieConsent\Components;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CookiesCleanupService
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Connection $connection
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     */
    public function __construct(Connection $connection, SystemConfigService $systemConfigService, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function deleteUnconfirmedCookies():void
    {

        $cleanupDays = $this->systemConfigService->get('AcrisCookieConsentCS.config.daysToDeleteUnconfirmedCookies');

        if( $cleanupDays <= 0 )
            return;

        $sql = <<<SQL
DELETE FROM `acris_cookie` WHERE `created_at` <= NOW()-INTERVAL ? DAY AND `unknown` = ?;
SQL;
        try {
            $this->connection->executeStatement($sql, [$cleanupDays, 1]);
        }
        catch (\Exception $exception )
        {
            $this->logger->error("AcrisCookieConsent::CookiesCleanupService::deleteUnconfirmedCookies: " . $exception->getMessage() );
        }
    }
}
