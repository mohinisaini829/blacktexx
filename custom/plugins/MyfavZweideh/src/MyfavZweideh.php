<?php declare(strict_types=1);

namespace Myfav\Zweideh;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;


class MyfavZweideh extends Plugin
{
    public const PLUGIN_CONFIG = 'MyfavZweideh.config.';
    
    /**
     * Installation des Plugins
     */
    public function install(InstallContext $installContext): void
	{
        // CustomField "Checkbox - Designer aktiviert"
        $check = $this->customFieldsExist($installContext->getContext(), 'myfav_zweideh_enabled', 'myfav_zweideh');
        
        if(null === $check) {
            new \Exception('Custom Field Repository should not be null');
        }

        if($check->getTotal() === 0) {
            $this->installZweidehEnabledFieldForProduct($installContext->getContext());
            
        }

        $this->installCustomFieldsForCustomer($installContext->getContext());
    }

    /**
     * @param UninstallContext $uninstallContext
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'myfav_zweideh'));
        $result = $customFieldSetRepository->searchIds($criteria, $uninstallContext->getContext());

        if ($result->getTotal() > 0 && !$uninstallContext->keepUserData()) {
            $data = $result->getDataOfId($result->firstId());
            $customFieldSetRepository->delete([$data], $uninstallContext->getContext());
        }
    }

    /**
     * Prüfen, ob custom Fields bereits bestehen.
     */
    private function customFieldsExist(Context $context, $fieldName, $fieldSetName): ?IdSearchResult
    {
        return $this->container->get('custom_field_set.repository')->searchIds(
            (new Criteria())->addFilter(new EqualsFilter(
                'name', "myfav_zweideh")), $context);
    }

    /**
     * Custom-Field "Checkbox - Designer aktiviert" installieren.
     */
    private function installZweidehEnabledFieldForProduct(Context $context)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->create([
            [
                'name' => 'myfav_zweideh',
                'config' => [
                    'label' => [
                        'de-DE' => 'Myfav Zweideh Designer'
                    ]
                ],
                'customFields' => [
                    [
                        'name' => 'myfav_zweideh_enabled',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'label' => [
                                'de-DE' => 'Designer für diesen Artikel aktivieren'
                            ],
                            'type' => 'checkbox',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'checkbox',
                            'customFieldPosition' => 10,
                        ]
                    ],
                    [
                        'name' => 'lumis_designer_article_id',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Lumis-Designer-ID',
                                'de-DE' => 'Designer-Setup-ID'
                            ],
                            'type' => 'text',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 20
                        ]
                    ],
                    [
                        'name' => 'lumis_designer_article_name',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Lumis Artikel Name',
                                'de-DE' => 'Lumis Artikel Name'
                            ],
                            'type' => 'text',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 30
                        ]
                    ],
                    [
                        'name' => 'lumis_designer_available_request_sizes',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Available request sizes',
                                'de-DE' => 'Verfügbare Anfrage-Größen'
                            ],
                            'type' => 'text',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'text',
                            'customFieldPosition' => 40
                        ]
                    ]
                ],
                'relations' => [
                    [
                        'id' => Uuid::randomHex(),
                        'entityName' => 'product'
                    ]
                ]
            ]
        ], $context);
    }

    /**
     * Custom-Field "Checkbox - Designer aktiviert" installieren.
     */
    private function installCustomFieldsForCustomer(Context $context)
    {
        $customFieldRepo = $this->container->get('custom_field.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'myfav_zweideh_is_admin'));
        $existing = $customFieldRepo->search($criteria, $context);

        if ($existing->getTotal() > 0) {
            // Already exists — skip creation
            return;
        }

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->create([
            [
                'name' => 'myfav_zweideh_customer',
                'config' => [
                    'label' => [
                        'de-DE' => 'Myfav Zweideh Customer'
                    ]
                ],
                'customFields' => [
                    [
                        'name' => 'myfav_zweideh_is_admin',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'label' => [
                                'de-DE' => 'User hat Zugriff auf die Admin-Komponenten des Designers.'
                            ],
                            'type' => 'checkbox',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'checkbox',
                            'customFieldPosition' => 10,
                        ]
                    ]
                ],
                'relations' => [
                    [
                        'id' => Uuid::randomHex(),
                        'entityName' => 'customer'
                    ]
                ]
            ]
        ], $context);
    }

}