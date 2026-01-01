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

class Cta2Resolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'netzp-powerpack6-cta2';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $numberOfElements = $config->get('numberOfElements')->getValue();
        $elements = $config->get('elements')->getValue();

        $ids = [];
        for($n = 0; $n < $numberOfElements; $n++) {
            $type = $elements[$n]['type'];
            if($type === 'image') {
                $image = $elements[$n]['image'];
                if($image !== null && $image['source'] !== 'mapped') {
                    array_push($ids, $image['value']);
                }
            }
        }

        $backgroundImageConfig = $config->get('backgroundImage');
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
        $backgroundImageConfig = $config->get('backgroundImage');
        $backgroundImage = new ImageStruct();
        $data->setUniqueIdentifier(Uuid::randomHex());

        $numberOfElements = $config->get('numberOfElements')->getValue();
        $elements = $config->get('elements')->getValue();

        $tmpElements = [];
        for($n = 0; $n < $numberOfElements; $n++)
        {
            $element = $elements[$n];
            $type = $element['type'];

            if($type === 'image') {
                $elementImage = $element['image'];
                if($elementImage === null) {
                    continue;
                }

                $image = new ImageStruct();
                $imageConfig = new FieldConfig('image', $elementImage['source'], $elementImage['value']);
                $this->addMediaEntity($slot, $image, $result, $imageConfig, $resolverContext);

                $elements[$n]['image'] = $image;
            }

            elseif($type === 'text' || $type === 'button')
            {
                $elementConfig = new FieldConfig($type, $element['contents']['source'], $element['contents']['value']);

                $contents = null;
                if ($elementConfig !== null)
                {
                    if ($elementConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
                        $contents = $this->resolveEntityValueToString($resolverContext->getEntity(), $elementConfig->getStringValue(), $resolverContext);
                    }
                    if ($elementConfig->isStatic())
                    {
                        if ($resolverContext instanceof EntityResolverContext) {
                            $contents = (string)$this->resolveEntityValues($resolverContext, $elementConfig->getStringValue());
                        }
                        else {
                            $contents = $elementConfig->getStringValue();
                        }
                    }
                }
                $elements[$n]['contents'] = $contents;
            }

            $elements[$n]['style'] = $this->getElementStyle($elements[$n]);
            $elements[$n]['class'] = $this->getElementClass($elements[$n]);

            $tmpElements[$n] = $elements[$n];
        }

        if ($backgroundImageConfig && $backgroundImageConfig->getValue()) {
            $this->addMediaEntity($slot, $backgroundImage, $result, $backgroundImageConfig, $resolverContext);
        }

        $data->set('elements', $tmpElements);
        $data->set('backgroundImage', $backgroundImage);
        $data->set('containerStyle', $this->getContainerStyle($config, $backgroundImage));
    }

    private function getContainerStyle($config, $backgroundImage)
    {
        $s = '';

        $height = $config->get('height')->getValue();
        $gap = $config->get('gap')->getValue();
        $direction = $config->get('direction')->getValue();
        $justifyContent = $config->get('justifyContent')->getValue();
        $alignItems = $config->get('alignItems')->getValue();

        $backgroundImageMode = $config->get('backgroundImageMode')->getValue();
        $backgroundImageAlign = $config->get('backgroundImageAlign')->getValue();
        $backgroundColor = $config->get('backgroundColor')->getValue();

        if($height !== '') {
            $s .= 'height: ' . $height . ';';
        }
        if($gap !== '') {
            $s .= 'gap: ' . $gap . ';';
            $s .= 'padding: ' . $gap . ';';
        }
        $s .= 'flex-direction: ' . $direction . ';';
        $s .= 'justify-content: ' . $justifyContent . ';';
        $s .= 'align-items: ' . $alignItems . ';';

        if($backgroundImage !== null && $backgroundImage->getMedia() !== null) {
            $s .= 'background-image: url("' . $backgroundImage->getMedia()->getUrl() . '");';
            $s .= 'background-size: ' . $backgroundImageMode . ';';
            $s .= 'background-position: ' . $backgroundImageAlign . ';';
            $s .= 'background-repeat: no-repeat;';
        }
        if($backgroundColor !== '') {
            $s .= 'background-color: ' . $backgroundColor . ';';
        }

        return $s;
    }

    private function getElementStyle($element)
    {
        $s = '';

        $s .= 'align-self: ' . $element['alignSelf'] . ';';
        $s .= 'display: inline-block;'; // prevent flex effects (e.g. "A <b>B</b> C" suppresses white space for flex reasons

        if ($element['width'] !== '') {
            $s .= 'width: ' . $element['width'] . ';';
        }
        if ($element['height'] !== '') {
            $s .= 'height: ' . $element['height'] . ';';
        }
        if($element['padding'] !== '') {
            $s .= 'padding: ' . $element['padding'] . ';';
        }
        if($element['fontSize'] !== '') {
            $s .= 'font-size: ' . $element['fontSize'] . ';';
        }

        if($this->isCustomButton($element) || ! $this->isButton($element)) {
            if ($element['color'] !== '') {
                $s .= 'color: ' . $element['color'] . ';';
            }
            if ($element['backgroundColor'] !== '') {
                $s .= 'background-color: ' . $element['backgroundColor'] . ';';
            }
            if ($element['borderWidth'] !== '0' && $element['borderColor'] !== '') {
                $s .= 'border: ' . $element['borderWidth'] . 'px solid ' . $element['borderColor'] . ';';
            }
        }

        return $s;
    }

    private function getElementClass($element)
    {
        $s = '';

        if($this->isButton($element)) {
            $buttonMode = ($element['mode'] == 'auto') ? 'primary' : $element['mode'];
            $s .= 'btn btn-' . $buttonMode;
        }

        return $s;
    }

    private function isButton($element)
    {
        return $element['type'] === 'button';
    }

    private function isCustomButton($element)
    {
        return $this->isButton($element) && $element['mode'] === 'custom';
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
