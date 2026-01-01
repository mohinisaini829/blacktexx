<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace salty\ColorVariants\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

interface InstallerInterface
{
    /**
     * Lifecycle function for the installation process.
     */
    public function install(InstallContext $context): void;

    /**
     * hLifecycle function for the update process.
     */
    public function update(UpdateContext $context): void;

    /**
     * Lifecycle function for the uninstallation process.
     */
    public function uninstall(UninstallContext $context): void;

    /**
     * Lifecycle function for the activation process.
     */
    public function activate(ActivateContext $context): void;

    /**
     * Lifecycle function for the deavtivation process.
     */
    public function deactivate(DeactivateContext $context): void;
}
