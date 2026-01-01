<?php declare(strict_types=1);

namespace NetzpPowerPack6\Core\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ImageStruct;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class CtaResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'netzp-powerpack6-cta';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $imageConfig = $config->get('image');
        $backgroundImageConfig = $config->get('backgroundImage');

        $ids = [];
        if(!$imageConfig || $imageConfig->isMapped() || $imageConfig->getValue() === null) {
            //
        }
        else {
            array_push($ids, $imageConfig->getValue());
        }

        if(!$backgroundImageConfig || $backgroundImageConfig->isMapped() || $backgroundImageConfig->getValue() === null) {
            //
        }
        else {
            array_push($ids, $backgroundImageConfig->getValue());
        }

        if(count($ids) == 0) {
            return null;
        }

        $criteria = new Criteria($ids);

        $criteriaCollection = new CriteriaCollection();
        $criteriaCollection->add('media_' . $slot->getUniqueIdentifier(), MediaDefinition::class, $criteria);

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $data = new ArrayEntity();
        $slot->setData($data);

        $config = $slot->getFieldConfig();
        $data->setUniqueIdentifier(Uuid::randomHex());

        $image = new ImageStruct();
        $backgroundImage = new ImageStruct();

        $imageConfig = $config->get('image');
        $backgroundImageConfig = $config->get('backgroundImage');

        if ($imageConfig && $imageConfig->getValue()) {
            $this->addMediaEntity($slot, $image, $result, $imageConfig, $resolverContext);
        }
        $data->set('image', $image);

        if ($backgroundImageConfig && $backgroundImageConfig->getValue()) {
            $this->addMediaEntity($slot, $backgroundImage, $result, $backgroundImageConfig, $resolverContext);
        }
        $data->set('backgroundImage', $backgroundImage);

        $configTitle = $slot->getFieldConfig()->get('title');
        $title = null;
        if ($configTitle !== null)
        {
            if ($configTitle->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $title = $this->resolveEntityValueToString($resolverContext->getEntity(), $configTitle->getStringValue(), $resolverContext);
            }
            if ($configTitle->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $title = (string)$this->resolveEntityValues($resolverContext, $configTitle->getStringValue());
                }
                else {
                    $title = $configTitle->getStringValue();
                }
            }
        }

        $configText = $slot->getFieldConfig()->get('text');
        $text = null;
        if ($configText !== null)
        {
            if ($configText->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $text = $this->resolveEntityValueToString($resolverContext->getEntity(), $configText->getStringValue(), $resolverContext);
            }
            if ($configText->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $text = (string)$this->resolveEntityValues($resolverContext, $configText->getStringValue());
                }
                else {
                    $text = $configText->getStringValue();
                }
            }
        }

        $configButton = $slot->getFieldConfig()->get('button');
        $button = null;
        if ($configButton !== null)
        {
            if ($configButton->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $button = $this->resolveEntityValueToString($resolverContext->getEntity(), $configButton->getStringValue(), $resolverContext);
            }
            if ($configButton->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $button = (string)$this->resolveEntityValues($resolverContext, $configButton->getStringValue());
                }
                else {
                    $button = $configButton->getStringValue();
                }
            }
        }

        $data->set('title', $title);
        $data->set('text', $text);
        $data->set('button', $button);
    }

    private function addMediaEntity(CmsSlotEntity $slot, ImageStruct $image, ElementDataCollection $result,
                                    FieldConfig $config, ResolverContext $resolverContext): void
    {
        if ($config->isMapped() && $resolverContext instanceof EntityResolverContext)
        {
            /** @var MediaEntity|null $media */
            $media = $this->resolveEntityValue($resolverContext->getEntity(), $config->getValue());

            if ($media !== null) {
                $image->setMediaId($media->getUniqueIdentifier());
                $image->setMedia($media);
            }
        }

        if ($config->isStatic())
        {
            $image->setMediaId($config->getValue());

            $searchResult = $result->get('media_' . $slot->getUniqueIdentifier());
            if (!$searchResult) {
                return;
            }

            /** @var MediaEntity|null $media */
            $media = $searchResult->get($config->getValue());
            if (!$media) {
                return;
            }

            $image->setMedia($media);
        }
    }
}
