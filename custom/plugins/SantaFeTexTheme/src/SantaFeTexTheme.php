<?php declare(strict_types=1);

namespace SantaFeTexTheme;

use RuntimeException;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Storefront\Framework\ThemeInterface;

class SantaFeTexTheme extends Plugin implements ThemeInterface
{
    public const CUSTOM_FIELD_SET_NAME = "vio_theme_extensions";
    public const CUSTOM_FIELD_MATERIAL = "vio_product_material";
    public const CUSTOM_FIELD_CATALOG = "vio_product_catalog";
    public const CUSTOM_FIELD_MODELNAME = "vio_product_modelname";
    public const CUSTOM_FIELD_IMPORTANT_PROPERTY = "vio_important_property";
    public const CUSTOM_FIELD_SIZETABLE = "vio_size_table";

    public function getThemeConfigPath(): string
    {
        return 'theme.json';
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->updateCustomFields($updateContext->getContext());
        parent::update($updateContext);
    }

    private function updateCustomFields(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        if (!$customFieldSetRepository) {
            throw new RuntimeException("Couldn't resolve service 'custom_field_set.repository'");
        }
        $fieldSetName = static::CUSTOM_FIELD_SET_NAME;

        // product field set
        $fieldSetId = $customFieldSetRepository->searchIds(
            (new Criteria())
                ->addFilter(
                    new EqualsFilter('name', $fieldSetName),
                    new EqualsFilter('relations.entityName', ProductDefinition::ENTITY_NAME)
                )
            , $context)
            ->firstId();
        if ($fieldSetId === null) {
            $fieldSetData = [
                'name' => $fieldSetName,
                'config' => [
                    'label' => [
                        'de-DE' => 'Theme Erweiterungen',
                        'en-GB' => 'Theme extensions',
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => ProductDefinition::ENTITY_NAME
                    ]
                ]
            ];
            $primaryKeys = $customFieldSetRepository
                ->create([$fieldSetData], $context)
                ->getPrimaryKeys(CustomFieldSetDefinition::ENTITY_NAME);
            $fieldSetId = current($primaryKeys);
        }

        // manufacture field set
        $fieldSetIdManufacturer = $customFieldSetRepository->searchIds(
            (new Criteria())
                ->addFilter(
                    new EqualsFilter('name', $fieldSetName),
                    new EqualsFilter('relations.entityName', ProductManufacturerDefinition::ENTITY_NAME)
                )
            , $context)
            ->firstId();
        if($fieldSetIdManufacturer === null) {
            $fieldSetDataManufacturer = [
                'name' => $fieldSetName,
                'config' => [
                    'label' => [
                        'de-DE' => 'Theme Erweiterungen',
                        'en-GB' => 'Theme extensions',
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => ProductManufacturerDefinition::ENTITY_NAME
                    ]
                ]
            ];
            $primaryKeys = $customFieldSetRepository
                ->upsert([$fieldSetDataManufacturer], $context)
                ->getPrimaryKeys(CustomFieldSetDefinition::ENTITY_NAME);
            $fieldSetIdManufacturer = current($primaryKeys);
        }

        // property field set
        $fieldSetIdProperties = $customFieldSetRepository->searchIds(
            (new Criteria())
                ->addFilter(
                    new EqualsFilter('name', $fieldSetName),
                    new EqualsFilter('relations.entityName', PropertyGroupDefinition::ENTITY_NAME)
                )
            , $context)
            ->firstId();
        if($fieldSetIdProperties === null) {
            $fieldSetDataProperties = [
                'name' => $fieldSetName,
                'config' => [
                    'label' => [
                        'de-DE' => 'Theme Erweiterungen',
                        'en-GB' => 'Theme extensions',
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => PropertyGroupDefinition::ENTITY_NAME
                    ]
                ]
            ];
            $primaryKeys = $customFieldSetRepository
                ->upsert([$fieldSetDataProperties], $context)
                ->getPrimaryKeys(CustomFieldSetDefinition::ENTITY_NAME);
            $fieldSetIdProperties = current($primaryKeys);
        }

        $this->updateCustomField(
            $fieldSetId,
            static::CUSTOM_FIELD_MATERIAL,
            [
                'name' => static::CUSTOM_FIELD_MATERIAL,
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Material',
                        'en-GB' => 'Material'
                    ]
                ],
            ],
            $context
        );

        $this->updateCustomField(
            $fieldSetId,
            static::CUSTOM_FIELD_MODELNAME,
            [
                'name' => static::CUSTOM_FIELD_MODELNAME,
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Modelname',
                        'en-GB' => 'Model name'
                    ]
                ],
            ],
            $context
        );

        $this->updateCustomField(
            $fieldSetIdManufacturer,
            static::CUSTOM_FIELD_CATALOG,
            [
                'name' => static::CUSTOM_FIELD_CATALOG,
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Blätterkatalog-URL',
                        'en-GB' => 'Browse catalogue url'
                    ]
                ],
            ],
            $context
        );

        $this->updateCustomField(
            $fieldSetIdManufacturer,
            static::CUSTOM_FIELD_SIZETABLE,
            [
                'name' => static::CUSTOM_FIELD_SIZETABLE,
                'type' => CustomFieldTypes::MEDIA,
                'config' => [
                    'label' => [
                        'de-DE' => 'Größentabelle',
                        'en-GB' => 'Size table'
                    ],
                    'componentName' => "sw-media-field",
                    'entity' => 'media',
                    'customFieldType' => CustomFieldTypes::JSON
                ],
            ],
            $context
        );

        $this->updateCustomField(
            $fieldSetIdProperties,
            static::CUSTOM_FIELD_IMPORTANT_PROPERTY,
            [
                'name' => static::CUSTOM_FIELD_IMPORTANT_PROPERTY,
                'type' => CustomFieldTypes::SWITCH,
                'config' => [
                    'label' => [
                        'de-DE' => 'Wichtige Eigenschaft',
                        'en-GB' => 'Important property'
                    ],
                    'help' => [
                        'de-DE' => 'Eigenschaft, die unter Produktnamen angezeigt wird',
                        'en-GB' => 'Property displayed under product name'
                    ]
                ]
            ],
            $context
        );
    }

    /**
     * @noinspection DuplicatedCode
     */
    private function updateCustomField(string $fieldSetId, string $fieldName, array $fieldData, Context $context): void
    {
        $customFieldRepository = $this->container->get('custom_field.repository');
        if (!$customFieldRepository) {
            throw new RuntimeException("Couldn't resolve service 'custom_field.repository'");
        }
        if (!array_key_exists('name', $fieldData)) {
            $fieldData['name'] = $fieldName;
        }
        if (!array_key_exists('customFieldSetId', $fieldData)) {
            $fieldData['customFieldSetId'] = $fieldSetId;
        }
        $fieldId = $customFieldRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $fieldName)), $context)->firstId();
        if ($fieldId !== null) {
            $fieldData['id'] = $fieldId;
        }
        $customFieldRepository->upsert([$fieldData], $context);
    }
}
