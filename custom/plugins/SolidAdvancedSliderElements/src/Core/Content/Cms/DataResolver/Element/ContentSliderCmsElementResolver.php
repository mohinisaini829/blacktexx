<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Core\Content\Cms\DataResolver\Element;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Util\HtmlSanitizer;

class ContentSliderCmsElementResolver extends AbstractCmsElementResolver
{
    const SLIDE_CONFIG_FIELDS_TO_BE_RESOLVED = [
        'link',
        'text',
        'headline',
        'linkTitle',
        'buttonLink',
        'buttonLabel',
        'buttonTitle',
        'customContent',
        'smallHeadline'
    ];

    /**
     * @var HtmlSanitizer $sanitizer
     */
    private $sanitizer;

    public function __construct(HtmlSanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    public function getType(): string
    {
        return 'solid-ase-content-slider';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $resolvedSlidesConfigData = new ArrayStruct();
        $slot->setData($resolvedSlidesConfigData);

        $slidesConfig = $slot->getFieldConfig()->get('slides')->getValue();
        $resolvedSlidesConfig = [];

        foreach($slidesConfig as $config) {
            foreach($config as $key => $value) {
                if (in_array($key, self::SLIDE_CONFIG_FIELDS_TO_BE_RESOLVED)) {
                    $resolvedValue = '';

                    if ($resolverContext instanceof EntityResolverContext && is_string($value)) {
                        $resolvedValue = $this->resolveEntityValues($resolverContext, $value);
                    } else {
                        $resolvedValue = $value;
                    }

                    if ($resolvedValue !== null) {
                        $resolvedValue = $this->sanitizer->sanitize($resolvedValue);
                    }

                    $config[$key] = $resolvedValue;
                }
            }

            array_push($resolvedSlidesConfig, $config);
        }

        $resolvedSlidesConfigData->set('slides', $resolvedSlidesConfig);
    }
}
