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

class CardResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'netzp-powerpack6-card';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $image1Config = $config->get('image1');
        $image2Config = $config->get('image2');

        $ids = [];
        if(!$image1Config || $image1Config->isMapped() || $image1Config->getValue() === null) {
            //
        }
        else {
            array_push($ids, $image1Config->getValue());
        }

        if(!$image2Config || $image2Config->isMapped() || $image2Config->getValue() === null) {
            //
        }
        else {
            array_push($ids, $image2Config->getValue());
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

        $image1 = new ImageStruct();
        $image2 = new ImageStruct();

        $image1Config = $config->get('image1');
        $image2Config = $config->get('image2');

        if ($image1Config && $image1Config->getValue()) {
            $this->addMediaEntity($slot, $image1, $result, $image1Config, $resolverContext);
        }
        $data->set('image1', $image1);

        if ($image2Config && $image2Config->getValue()) {
            $this->addMediaEntity($slot, $image2, $result, $image2Config, $resolverContext);
        }
        $data->set('image2', $image2);

        $configText1 = $slot->getFieldConfig()->get('text1');
        $text1 = null;
        if ($configText1 !== null)
        {
            if ($configText1->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $text1 = $this->resolveEntityValueToString($resolverContext->getEntity(), $configText1->getStringValue(), $resolverContext);
            }
            if ($configText1->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $text1 = (string)$this->resolveEntityValues($resolverContext, $configText1->getStringValue());
                }
                else {
                    $text1 = $configText1->getStringValue();
                }
            }
        }
        $data->set('text1', $text1);

        $configText2 = $slot->getFieldConfig()->get('text2');
        $text2 = null;
        if ($configText2 !== null)
        {
            if ($configText2->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $text2 = $this->resolveEntityValueToString($resolverContext->getEntity(), $configText2->getStringValue(), $resolverContext);
            }
            if ($configText2->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $text2 = (string)$this->resolveEntityValues($resolverContext, $configText2->getStringValue());
                }
                else {
                    $text2 = $configText2->getStringValue();
                }
            }
        }
        $data->set('text2', $text2);

        $configUrlText = $slot->getFieldConfig()->get('urlText');
        $urlText = null;

        if ($configUrlText !== null)
        {
            if ($configUrlText->isMapped() && $resolverContext instanceof EntityResolverContext)
            {
                $urlText = $this->resolveEntityValueToString($resolverContext->getEntity(), $configUrlText->getStringValue(), $resolverContext);
            }
            if ($configUrlText->isStatic())
            {
                if ($resolverContext instanceof EntityResolverContext) {
                    $urlText = (string)$this->resolveEntityValues($resolverContext, $configUrlText->getStringValue());
                }
                else {
                    $urlText = $configUrlText->getStringValue();
                }
            }
        }
        $data->set('urlText', $urlText);
    }

    private function addMediaEntity(CmsSlotEntity $slot, ImageStruct $image, ElementDataCollection $result,
                                    FieldConfig $config, ResolverContext $resolverContext): void
    {
        if ($config->isMapped() && $resolverContext instanceof EntityResolverContext) {
            /** @var MediaEntity|null $media */
            $media = $this->resolveEntityValue($resolverContext->getEntity(), $config->getValue());

            if ($media !== null) {
                $image->setMediaId($media->getUniqueIdentifier());
                $image->setMedia($media);
            }
        }

        if ($config->isStatic()) {
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
