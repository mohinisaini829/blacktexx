<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Core\Content\Cms\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class UpdateScheduledSlidesTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'solid_ase.update_scheduled_slides';
    }

    public static function getDefaultInterval(): int
    {
        return 300; // 5 minutes
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
