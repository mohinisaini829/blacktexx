<?php declare(strict_types=1);

namespace Acris\CookieConsent\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CleanupTask extends ScheduledTask
{
    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'acris_cookie_consent.cleanup';
    }

    /**
     * @return int
     */
    public static function getDefaultInterval(): int
    {
        return 86400; // 24 hours
    }
}

