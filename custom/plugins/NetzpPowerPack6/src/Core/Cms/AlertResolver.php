<?php declare(strict_types=1);

namespace NetzpPowerPack6\Core\Cms;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Struct\ArrayEntity;

class AlertResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return 'netzp-powerpack6-alert';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $data = new ArrayEntity();
        $slot->setData($data);

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
                }
                else {
                    $contents = $configContents->getStringValue();
                }
            }
        }

        $data->set('title', $title);
        $data->set('contents', $contents);
    }
}
