<?php declare(strict_types=1);

namespace NetzpPowerPack6\Core\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;

class TestimonialResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'netzp-powerpack6-testimonial';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $mediaConfig = $config->get('media');

        if ( ! $mediaConfig || $mediaConfig->isMapped() || $mediaConfig->getValue() === null) {
            return null;
        }

        $criteria = new Criteria([$mediaConfig->getValue()]);

        $criteriaCollection = new CriteriaCollection();
        $criteriaCollection->add('media_' . $slot->getUniqueIdentifier(), MediaDefinition::class, $criteria);

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();

        $data = new ArrayEntity();
        $slot->setData($data);

        $mediaConfig = $config->get('media');
        if ($mediaConfig && $mediaConfig->getValue()) {
            $this->addMediaEntity($slot, $data, $result, $mediaConfig, $resolverContext);
        }

        $configTitle = $config->get('title');
        $title = null;
        if ($configTitle !== null) {
            if ($configTitle->isMapped() && $resolverContext instanceof EntityResolverContext) {
                $title = $this->resolveEntityValueToString($resolverContext->getEntity(), $configTitle->getStringValue(), $resolverContext);
            }

            if ($configTitle->isStatic()) {
                if ($resolverContext instanceof EntityResolverContext) {
                    $title = (string)$this->resolveEntityValues($resolverContext, $configTitle->getStringValue());
                } else {
                    $title = $configTitle->getStringValue();
                }
            }
        }

        $configContents = $slot->getFieldConfig()->get('contents');
        $contents = null;
        if ($configContents !== null)
        {
            if ($configContents->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $contents = $this->resolveEntityValueToString($resolverContext->getEntity(), $configContents->getStringValue(), $resolverContext);
            }

            if ($configContents->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $contents = (string)$this->resolveEntityValues($resolverContext, $configContents->getStringValue());
                } else {
                    $contents = $configContents->getStringValue();
                }
            }
        }

        $configName = $slot->getFieldConfig()->get('name');
        $name = null;
        if ($configName !== null)
        {
            if ($configName->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $name = $this->resolveEntityValueToString($resolverContext->getEntity(), $configName->getStringValue(), $resolverContext);
            }

            if ($configName->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $name = (string)$this->resolveEntityValues($resolverContext, $configName->getStringValue());
                }
                else {
                    $name = $configName->getStringValue();
                }
            }
        }

        $configName2 = $slot->getFieldConfig()->get('name2');
        $name2 = null;

        if ($configName2 !== null)
        {
            if ($configName2->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $name2 = $this->resolveEntityValueToString($resolverContext->getEntity(), $configName2->getStringValue(), $resolverContext);
            }

            if ($configName2->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $name2 = (string)$this->resolveEntityValues($resolverContext, $configName2->getStringValue());
                } else {
                    $name2 = $configName2->getStringValue();
                }
            }
        }

        $data->set('title', $title);
        $data->set('contents', $contents);
        $data->set('name', $name);
        $data->set('name2', $name2);
    }

    private function addMediaEntity(CmsSlotEntity $slot, ArrayEntity $data, ElementDataCollection $result,
                                    FieldConfig $config, ResolverContext $resolverContext): void
    {
        if ($config->isMapped() && $resolverContext instanceof EntityResolverContext)
        {
            /** @var MediaEntity|null $media */
            $media = $this->resolveEntityValue($resolverContext->getEntity(), $config->getValue());

            if ($media !== null) {
                $data->set('mediaId', $media->getUniqueIdentifier());
                $data->set('media', $media);
            }
        }

        if ($config->isStatic())
        {
            $data->set('mediaId', $config->getValue());

            $searchResult = $result->get('media_' . $slot->getUniqueIdentifier());
            if (!$searchResult) {
                return;
            }

            $media = $searchResult->get($config->getValue());
            if ( ! $media) {
                return;
            }

            $data->set('media', $media);
        }
    }
}
