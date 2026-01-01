<?php declare(strict_types=1);

namespace NetzpPowerPack6;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class NetzpPowerPack6 extends Plugin
{
    final public const CUSTOM_FIELDSET_ID = '0A202BD62BDF4DABBE829B1BA499F73E';

    public function install(InstallContext $context) : void
    {
        parent::install($context);

        try {
            $this->addCustomFields($context->getContext());
        }
        catch (\Exception) {
            //
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
        if ($context->keepUserData()) {
            return;
        }

        try {
            $this->removeCustomFields($context->getContext());
        }
        catch (\Exception) {
            //
        }
    }

    public function addCustomFields(Context $context)
    {
        $customFieldsRepository = $this->container->get('custom_field_set.repository');

        $customFieldsRepository->upsert([[
            'id'     => strtolower(self::CUSTOM_FIELDSET_ID),
            'name'   => 'netzp_powerpack6',
            'config' => [
                'label' => [
                    'en-GB' => 'PowerPack',
                    'de-DE' => 'PowerPack'
                ]
            ],
            'customFields' => [
                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_header_cms_show',
                    'type'   => CustomFieldTypes::BOOL,
                    'config' => [
                        'customFieldPosition' => 1,
                        'label' => [
                            'en-GB' => 'Show CMS Element in header',
                            'de-DE' => 'Zeige CMS-Element im Header'
                        ]
                    ]
                ],
                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_header_cms_id',
                    'type'   => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName'       => 'sw-entity-single-select',
                        'entity'              => 'cms_page',
                        'customFieldType'     => 'text',
                        'customFieldPosition' => 2,
                        'label' => [
                            'en-GB' => 'Header CMS Page ID',
                            'de-DE' => 'Header CMS Seiten ID'
                        ]
                    ]
                ],
                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_header_cms_sticky',
                    'type'   => CustomFieldTypes::BOOL,
                    'config' => [
                        'customFieldPosition' => 3,
                        'label' => [
                            'en-GB' => 'Sticky header block',
                            'de-DE' => 'Fixiere das CMS-Element im Header (sticky)'
                        ]
                    ]
                ],
                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_footer_cms_show',
                    'type'   => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName'       => 'sw-single-select',
                        'options'             => [
                            ['value' => '0', 'label' => ['en-GB' => 'no', 'de-DE' => 'nein']],
                            ['value' => '1', 'label' => ['en-GB' => 'above standard footer', 'de-DE' => 'oberhalb des Standard-Footers']],
                            ['value' => '2', 'label' => ['en-GB' => 'below standard footer', 'de-DE' => 'unterhalb des Standard-Footers']]
                        ],
                        'customFieldType'     => 'text',
                        'customFieldPosition' => 4,
                        'label' => [
                            'en-GB' => 'Show CMS Element in footer',
                            'de-DE' => 'Zeige CMS-Element im Footer'
                        ]
                    ]
                ],
                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_footer_cms_id',
                    'type'   => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName'       => 'sw-entity-single-select',
                        'entity'              => 'cms_page',
                        'customFieldType'     => 'text',
                        'customFieldPosition' => 5,
                        'label' => [
                            'en-GB' => 'Footer CMS Page ID',
                            'de-DE' => 'Footer CMS Seiten ID'
                        ]
                    ]
                ],

                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_finish_cms_show',
                    'type'   => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName'       => 'sw-single-select',
                        'options'             => [
                            ['value' => '0', 'label' => ['en-GB' => 'no', 'de-DE' => 'nein']],
                            ['value' => '1', 'label' => ['en-GB' => 'above standard finish data', 'de-DE' => 'oberhalb der Standard-Bestellzusammenfassung']],
                            ['value' => '2', 'label' => ['en-GB' => 'below standard finish data', 'de-DE' => 'unterhalb der Standard-Bestellzusammenfassung']]
                        ],
                        'customFieldType'     => 'text',
                        'customFieldPosition' => 6,
                        'label' => [
                            'en-GB' => 'Show CMS Element in finish page',
                            'de-DE' => 'Zeige CMS-Element auf der Bestellzusammenfassung'
                        ]
                    ]
                ],
                [
                    'id'     => Uuid::randomHex(),
                    'name'   => 'netzp_powerpack6_finish_cms_id',
                    'type'   => CustomFieldTypes::TEXT,
                    'config' => [
                        'componentName'       => 'sw-entity-single-select',
                        'entity'              => 'cms_page',
                        'customFieldType'     => 'text',
                        'customFieldPosition' => 7,
                        'label' => [
                            'en-GB' => 'Finish CMS Page ID',
                            'de-DE' => 'Bestellzusammenfassung CMS Seiten ID'
                        ]
                    ]
                ]
            ],
            'relations' => [
                [
                    'id' => strtolower(self::CUSTOM_FIELDSET_ID),
                    'entityName' => $this->container->get(SalesChannelDefinition::class)->getEntityName()
                ]
            ]
        ]], $context);
    }

    public function removeCustomFields(Context $context)
    {
        $customFieldsRepository = $this->container->get('custom_field_set.repository');
        $customFieldsRepository->delete([['id' => self::CUSTOM_FIELDSET_ID]], $context);
    }
}
