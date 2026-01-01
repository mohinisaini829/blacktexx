<?php

declare(strict_types=1);

namespace salty\ColorVariants;

use salty\ColorVariants\Installer\CustomFieldsInstaller;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;

class saltyColorVariants extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        if (null === $this->container) {
            return;
        }

        /** @var EntityRepository<CustomFieldSetCollection> $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new CustomFieldsInstaller($customFieldSetRepository))->install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        if (null === $this->container) {
            return;
        }

        /** @var EntityRepository<CustomFieldSetCollection> $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        (new CustomFieldsInstaller($customFieldSetRepository))->update($updateContext);
    }
}
