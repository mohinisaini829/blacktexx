<?php declare(strict_types=1);

namespace NetzpPowerPack6\Subscriber;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\CategoryEvents;
use Shopware\Core\Content\Category\Event\CategoryRouteCacheKeyEvent;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\ThemeCompilerConcatenatedStylesEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Shopware\Storefront\Pagelet\Header\HeaderPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class FrontendSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly KernelInterface $kernel,
                                private readonly Environment $twig,
                                private readonly SalesChannelCmsPageLoaderInterface $cmsPageLoader,
                                private readonly SystemConfigService $config,
                                private readonly EntityRepository $categoryRepository,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HeaderPageletLoadedEvent::class             => 'loadCmsHeader',
            FooterPageletLoadedEvent::class             => 'loadCmsFooter',
            CheckoutFinishPageLoadedEvent::class        => 'loadFinishPage',

            ProductEvents::PRODUCT_LOADED_EVENT         => 'onProductLoaded',
            CategoryEvents::CATEGORY_LOADED_EVENT       => 'onCategoryLoaded',

            CategoryRouteCacheKeyEvent::class           => 'onCategoryRouteCacheKey',
            ThemeCompilerConcatenatedStylesEvent::class => 'onThemeCompiler'
        ];
    }

    public function onCategoryRouteCacheKey(CategoryRouteCacheKeyEvent $event): void
    {
        // In 6.4 all active rules were used for the cache key,
        // with 6.5 only rule ids matching to the "requested entity" will be used for caching
        // so disable caching when custom rule ids are set in sections and/or blocks

        $criteria = (new Criteria([$event->getNavigationId()]));
        $criteria->addAssociation('cmsPage.sections.blocks.slots.config');
        $category = $this->categoryRepository->search(
            $criteria,
            $event->getContext()->getContext()
        )->getEntities()->first();

        if( ! $category)
        {
            return;
        }

        $page = $category->getCmsPage();
        if ( ! $page)
        {
            return;
        }

        foreach($page->getSections()->getElements() as $element)
        {
            $customFields = $element->getCustomFields()['netzp_pp'] ?? '';
            $ruleId = $customFields['ruleId'] ?? null;
            if($ruleId) {
                $event->disableCaching();
                return;
            }
        }

        foreach($page->getSections()->getBlocks()->getElements() as $element)
        {
            $customFields = $element->getCustomFields()['netzp_pp'] ?? '';
            $ruleId = $customFields['ruleId'] ?? null;
            if($ruleId) {
                $event->disableCaching();
                return;
            }
        }
    }

    public function onThemeCompiler(ThemeCompilerConcatenatedStylesEvent $event)
    {
        $config = $this->config->get('NetzpPowerPack6.config', $event->getSalesChannelId());
        if (array_key_exists('excludefontawesome', $config) && $config['excludefontawesome']) {
            return;
        }

        $bundle = $this->kernel->getBundles()['NetzpPowerPack6'] ?? null;
        if($bundle === null) {
            return;
        }

        $projectDir = $bundle->getPath() ?? null;
        if($projectDir === null) {
            return;
        }

        $styles = $event->getConcatenatedStyles();
        $includeFile = $projectDir . "/Resources/app/storefront/src/csslibs/fontawesome/fontawesome";
        $styles .= "@import '" . $includeFile . "';" . PHP_EOL;

        $event->setConcatenatedStyles($styles);
    }

    public function onCategoryLoaded(EntityLoadedEvent $event)
    {
        if($event->getContext()->getScope() != 'user') {
            return;
        }
        if ( ! property_exists($event->getContext()->getSource(), 'salesChannelId')) {
            return;
        }

        $config = $this->config->get('NetzpPowerPack6.config', $event->getContext()
                               ->getSource()
                               ->getSalesChannelId());

        if ( ! $config['snippetsproduct']) {
            return;
        }

        $twig = clone $this->twig;
        foreach($event->getEntities() as $entity)
        {
            if($entity::class !== CategoryEntity::class) {
                continue;
            }
            $translated = $entity->getTranslated();

            $tplDescription = $twig->createTemplate($translated['description'] ?? '');
            $description = $tplDescription->render();
            $translated['description'] = $description;
            $entity->setDescription($description);

            // Name führt hier zu Problemen, u.a. <title> wird nicht ersetzt
            /*
            $tplName = $twig->createTemplate($translated['name'] ?? '');
            $translated['name'] = $tplName->render();

            $breadcrumbs = $translated['breadcrumb'];
            foreach($breadcrumbs as &$bc) {
                $tplBreadcrumb = $twig->createTemplate($bc ?? '');
                $bc = $tplBreadcrumb->render();
            }
            $translated['breadcrumb'] = $breadcrumbs;
            */

            $entity->assign(['translated' => $translated]);
        }
    }

    public function onProductLoaded(EntityLoadedEvent $event)
    {
        if($event->getContext()->getScope() != 'user') {
            return;
        }

        if (property_exists($event->getContext()->getSource(), 'salesChannelId'))
        {
            $config = $this->config->get(
                'NetzpPowerPack6.config',
                $event->getContext()->getSource()->getSalesChannelId()
            );
        }
        else {
            $config = $this->config->get('NetzpPowerPack6.config');
        }
        if( ! $config['snippetsproduct']) {
            return;
        }

        $twig = clone $this->twig;
        foreach($event->getEntities() as $entity)
        {
            if($entity::class !== SalesChannelProductEntity::class &&
                $entity::class !== ProductEntity::class) {
                continue;
            }
            $translated = $entity->getTranslated();

            $tplName = $twig->createTemplate($translated['name'] ?? '');
            $translated['name'] = $tplName->render();
            $entity->setName($tplName->render());

            $tplDescription = $twig->createTemplate($translated['description'] ?? '');
            $translated['description'] = $tplDescription->render();

            $entity->assign(['translated' => $translated]);
        }
    }

    public function loadCmsheader(HeaderPageletLoadedEvent $event): void
    {
        $pages = null;
        $request = $event->getRequest();
        $context = $event->getSalesChannelContext();
        $customFields = $context->getSalesChannel()->getTranslated()['customFields'];

        if($customFields && array_key_exists('netzp_powerpack6_header_cms_show', $customFields) &&
                            array_key_exists('netzp_powerpack6_header_cms_id', $customFields) &&
                            $customFields['netzp_powerpack6_header_cms_id'] != null) {
            $id = strtolower(trim((string) $customFields['netzp_powerpack6_header_cms_id']));

            if ($id != '') {
                $criteria = new Criteria([$id]);
                $pages = $this->cmsPageLoader->load($request, $criteria, $context);

                if (!$pages->has($id)) {
                    return;
                }
            }

            $event->getPagelet()->assign([
                'netzp_header_cms_page'     => $pages->get($id),
                'netzp_header_cms_position' => $customFields['netzp_powerpack6_header_cms_show'] ?? 0,
                'netzp_header_cms_sticky'   => $customFields['netzp_powerpack6_header_cms_sticky'] ?? false
            ]);
        }
    }

    public function loadCmsFooter(FooterPageletLoadedEvent $event): void
    {
        $pages = null;
        $request = $event->getRequest();
        $context = $event->getSalesChannelContext();
        $customFields = $context->getSalesChannel()->getTranslated()['customFields'];

        if($customFields && array_key_exists('netzp_powerpack6_footer_cms_show', $customFields)
                         && array_key_exists('netzp_powerpack6_footer_cms_id', $customFields)
                         && $customFields['netzp_powerpack6_footer_cms_id'] != null) {
            $id = strtolower(trim((string) $customFields['netzp_powerpack6_footer_cms_id']));
            $position = $customFields['netzp_powerpack6_footer_cms_show'];

            if ($id != '') {
                $criteria = new Criteria([$id]);
                $pages = $this->cmsPageLoader->load($request, $criteria, $context);

                if (!$pages->has($id)) {
                    return;
                }
            }

            $event->getPagelet()->assign([
                'netzp_footer_cms_page'     => $pages->get($id),
                'netzp_footer_cms_position' => $position
            ]);
        }
    }

    public function loadFinishPage(CheckoutFinishPageLoadedEvent $event): void
    {
        $pages = null;
        $request = $event->getRequest();
        $context = $event->getSalesChannelContext();
        $customFields = $context->getSalesChannel()->getTranslated()['customFields'];

        if($customFields && array_key_exists('netzp_powerpack6_finish_cms_show', $customFields)
            && array_key_exists('netzp_powerpack6_finish_cms_id', $customFields)
            && $customFields['netzp_powerpack6_finish_cms_id'] != null) {

            $id = strtolower(trim((string) $customFields['netzp_powerpack6_finish_cms_id']));
            $position = $customFields['netzp_powerpack6_finish_cms_show'];

            if ($id != '') {
                $criteria = new Criteria([$id]);
                $pages = $this->cmsPageLoader->load($request, $criteria, $context);

                if (!$pages->has($id)) {
                    return;
                }
            }

            $event->getPage()->assign([
                'netzp_finish_cms_page'     => $pages->get($id),
                'netzp_finish_cms_position' => $position
            ]);
        }
    }
}
