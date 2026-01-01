<?php

declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Theme\Twig;

use Shopware\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;

class ThemeInheritanceBuilder implements ThemeInheritanceBuilderInterface
{
    private ThemeInheritanceBuilderInterface $coreThemeInheritanceBuilder;

    public function __construct(
        ThemeInheritanceBuilderInterface $coreThemeInheritanceBuilder
    )
    {

        $this->coreThemeInheritanceBuilder = $coreThemeInheritanceBuilder;
    }

    public function build(array $bundles, array $themes): array
{
    $plugins = $this->coreThemeInheritanceBuilder->build($bundles, $themes);

    $storefrontIndex = array_search('Storefront', array_keys($plugins), true);

    $newPlugins = [];
    foreach ($plugins as $pluginName => $value) {
        if ($pluginName === 'BilobaArticleVariantOrderMatrix') {
            continue;
        }
        if ($pluginName === 'MyfavInquiry') {
            continue;
        }
        if ($pluginName === 'Storefront') {
            continue;
        }
        $newPlugins[$pluginName] = $value;
    }

    // Add only if they exist
    if (isset($plugins['MyfavInquiry'])) {
        $newPlugins['MyfavInquiry'] = $plugins['MyfavInquiry'];
    }

    if (isset($plugins['BilobaArticleVariantOrderMatrix'])) {
        $newPlugins['BilobaArticleVariantOrderMatrix'] = $plugins['BilobaArticleVariantOrderMatrix'];
    }

    return array_merge(
        $newPlugins,
        array_slice($plugins, $storefrontIndex)
    );
}

}
