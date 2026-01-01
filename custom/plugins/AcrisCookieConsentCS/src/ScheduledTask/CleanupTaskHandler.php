<?php declare(strict_types=1);

namespace Acris\CookieConsent\ScheduledTask;

use Acris\CookieConsent\Components\CookiesCleanupService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

#[AsMessageHandler(handles: CleanupTask::class)]
class CleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(EntityRepository $scheduledTaskRepository, LoggerInterface $logger, private readonly CookiesCleanupService $cookiesCleanupService) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->cookiesCleanupService->deleteUnconfirmedCookies();
    }
}
