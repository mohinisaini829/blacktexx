<?php declare(strict_types=1);

namespace Vio\ManufacturerVideoSlider;

use RuntimeException;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class VioManufacturerVideoSlider extends Plugin
{
    public const VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD_SET = 'vio_manufacturer_video_slider';
    public const VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD = 'vio_manufacturer_video_slider';

    public function install(InstallContext $installContext): void
    {
        $this->updateCustomFields($installContext->getContext());
        parent::install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->updateCustomFields($updateContext->getContext());
        parent::update($updateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->removeCustomFields($uninstallContext->getContext());
        parent::uninstall($uninstallContext);
    }

    private function removeCustomFields(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        if(!$customFieldSetRepository) {
            throw new RuntimeException("Couldn't resolve service 'custom_field_set.repository'");
        }
        $fieldSetName = static::VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD_SET;
        $fieldSetId = $customFieldSetRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $fieldSetName)), $context)->firstId();
        if($fieldSetId !== null) {
            $customFieldSetRepository->delete([['id' => $fieldSetId]], $context);
        }
    }

    private function updateCustomFields(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        if(!$customFieldSetRepository) {
            throw new RuntimeException("Couldn't resolve service 'custom_field_set.repository'");
        }
        $fieldSetName = static::VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD_SET;
        $fieldSetId = $customFieldSetRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $fieldSetName)), $context)->firstId();
        if($fieldSetId === null) {
            $fieldSetData = [
                'name' => $fieldSetName,
                'config' => [
                    'label' => [
                        'de-DE' => 'Videoslider',
                        'en-GB' => 'Video slider',
                    ]
                ],
                'relations' => [
                    [
                        'entityName' => ProductManufacturerDefinition::ENTITY_NAME
                    ]
                ]
            ];
            $fieldSetData['id'] = $fieldSetId;

            $primaryKeys = $customFieldSetRepository
                ->upsert([$fieldSetData], $context)
                ->getPrimaryKeys(CustomFieldSetDefinition::ENTITY_NAME);
            $fieldSetId = current($primaryKeys);
        }
        $this->updateCustomField(
            $fieldSetId,
            static::VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD,
            [
                'name' => static::VIO_MANUFACTURER_VIDEO_SLIDER_CUSTOM_FIELD,
                'type' => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'de-DE' => 'Video-URLs',
                        'en-GB' => 'Video-URLs',
                    ],
                    'customFieldType' => 'textEditor',
                    'componentName' => 'sw-textarea-field',
                    'helpText' => [
                        'de-DE' => 'getrennt durch Zeilenumbruch',
                        'en-GB' => 'separated by line breaks'
                    ]
                ],
            ],
            $context
        );
    }

    /**
     * @param string $fieldSetId
     * @param string $fieldName
     * @param array $fieldData
     * @param Context $context
     * @noinspection DuplicatedCode
     */
    private function updateCustomField(string $fieldSetId, string $fieldName, array $fieldData, Context $context): void
    {
        $customFieldRepository = $this->container->get('custom_field.repository');
        if(!$customFieldRepository) {
            throw new RuntimeException("Couldn't resolve service 'custom_field.repository'");
        }
        if(!array_key_exists('name', $fieldData)) {
            $fieldData['name'] = $fieldName;
        }
        if(!array_key_exists('customFieldSetId', $fieldData)) {
            $fieldData['customFieldSetId'] = $fieldSetId;
        }
        $fieldId = $customFieldRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('name', $fieldName)), $context)->firstId();
        if($fieldId !== null) {
            $fieldData['id'] = $fieldId;
        }
        $customFieldRepository->upsert([$fieldData], $context);
    }
}
