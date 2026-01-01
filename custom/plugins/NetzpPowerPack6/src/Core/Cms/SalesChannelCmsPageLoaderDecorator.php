<?php declare(strict_types=1);

namespace NetzpPowerPack6\Core\Cms;

use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;

class SalesChannelCmsPageLoaderDecorator implements SalesChannelCmsPageLoaderInterface
{
    final public const DEFAULT_TIMEZONE = 'Europe/Berlin';

    public function __construct(private readonly SalesChannelCmsPageLoaderInterface $inner)
    {
    }

    public function load(Request $request, Criteria $criteria, SalesChannelContext $context,
                         ?array  $config = null, ?ResolverContext $resolverContext = null): EntitySearchResult
    {
        $timeZone = $request->cookies->get('timezone', self::DEFAULT_TIMEZONE);

        $pages = $this->inner->load($request, $criteria, $context, $config, $resolverContext);
        foreach ($pages as $page)
        {
            $sections = $page->getSections();
            if ($sections === null || (is_countable($sections) ? count($sections) : 0) === 0) {
                continue;
            }

            foreach ($sections as $section)
            {
                $blocks = $section->getBlocks();
                if ($blocks === null || (is_countable($blocks) ? count($blocks) : 0) === 0) {
                    continue;
                }

                $filteredBlocks = $blocks->filter(function (CmsBlockEntity $thisBlock) use ($context, $timeZone)
                {
                    $showThisBlock = true;
                    if (!$this->checkVisibilityDates($thisBlock, $timeZone))
                    {
                        $showThisBlock = false;
                    }

                    if (!$this->checkVisibilityRule($thisBlock, $context))
                    {
                        $showThisBlock = false;
                    }

                    return $showThisBlock;
                });

                $section->setBlocks($filteredBlocks);
            }

            $filteredSections = $sections->filter(function (CmsSectionEntity $thisSection) use ($context, $timeZone)
            {
                $blocks = $thisSection->getBlocks();

                $showThisSection = true;
                if (!$this->checkVisibilityDates($thisSection, $timeZone))
                {
                    $showThisSection = false;
                }

                if (!$this->checkVisibilityRule($thisSection, $context))
                {
                    $showThisSection = false;
                }

                return $blocks !== null && count($blocks) > 0 && $showThisSection;
            });

            $page->setSections($filteredSections);
        }

        return $pages;
    }

    private function checkVisibilityDates($blockOrSection, string $timeZone)
    {
        if(empty(trim($timeZone)))
        {
            $timeZone = self::DEFAULT_TIMEZONE;
        }

        if ($blockOrSection->getCustomFields() &&
            array_key_exists('netzp_pp', $blockOrSection->getCustomFields()))
        {
            $customFields = $blockOrSection->getCustomFields()['netzp_pp'];

            $showFrom = null;
            $showUntil = null;
            $now = new \DateTime();

            // ************************************
            // ACHTUNG: Wenn im UserProfil eine andere Zeitzone als UTC eingetragen ist, klappen die Zeiten hier mal wieder nicht
            // ************************************

            if (array_key_exists('showFrom', $customFields) && $customFields['showFrom'] !== null)
            {
                $showFrom = new \DateTime($customFields['showFrom']);
                $showFrom->setTimezone(new \DateTimeZone($timeZone));
                $showFrom->setTime(0, 0, 0);
            }

            if (array_key_exists('showUntil', $customFields) && $customFields['showUntil'] !== null)
            {
                $showUntil = new \DateTime($customFields['showUntil']);
                $showUntil->setTimezone(new \DateTimeZone($timeZone));
                $showUntil->setTime(23, 59, 59);
            }

            if ($showFrom !== null && $showUntil !== null)
            {
                return $showFrom <= $now && $now <= $showUntil;
            }
            elseif ($showFrom !== null)
            {
                return $showFrom <= $now;
            }
            elseif ($showUntil !== null)
            {
                return $now <= $showUntil;
            }
        }

        return true;
    }

    private function checkVisibilityRule($blockOrSection, SalesChannelContext $salesChannelContext)
    {
        if ($blockOrSection->getCustomFields() && array_key_exists('netzp_pp', $blockOrSection->getCustomFields())) {
            $customFields = $blockOrSection->getCustomFields()['netzp_pp'];

            $ruleId = null;

            if (array_key_exists('ruleId', $customFields)) {
                $ruleId = $customFields['ruleId'];
                if($ruleId == null) {
                    return true;
                }

                $activeRuleIds = $salesChannelContext->getRuleIds();
                return in_array($ruleId, $activeRuleIds, true);
            }
        }

        return true;
    }
}
