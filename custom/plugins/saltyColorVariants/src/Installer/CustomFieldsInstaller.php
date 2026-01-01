<?php

declare(strict_types=1);

namespace salty\ColorVariants\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;

class CustomFieldsInstaller implements InstallerInterface
{
    private const CUSTOM_FIELDSETS = [
        [
            'id' => 'bb2fce309c364d1b9795fa4d43025942',
            'name' => 'salty_list_color_variants',
            'config' => [
                'label' => [
                    'en-GB' => 'Color Variants',
                    'de-DE' => 'Farbvariantenvorschau',
                ],
            ],
            'relations' => [
                [
                    'id' => 'c576f2cc82dc42d79b4a550fd01b156c',
                    'entityName' => 'product',
                ],
            ],
            'customFields' => [
                [
                    'id' => '23d122f082c94a51b5b5e6c26d38f8be',
                    'name' => 'custom_field_list_color_variants',
                    'type' => 'CustomFieldTypes::JSON',
                    'config' => [
                        'label' => [
                            'en-GB' => 'Color preview option',
                            'de-DE' => 'Gruppe für Farbvorschau',
                        ],
                        'componentName' => 'sw-entity-single-select',
                        'entity' => 'property_group',
                        'resultLimit' => 100,
                    ],
                ],
            ],
        ],
    ];

    /**
     * @phpstan-param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     */
    public function __construct(private readonly EntityRepository $customFieldSetRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDSETS, $context);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        $context->getContext()->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $this->customFieldSetRepository->upsert(self::CUSTOM_FIELDSETS, $context);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
    }
}
