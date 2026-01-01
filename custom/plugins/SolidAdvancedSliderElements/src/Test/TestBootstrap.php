<?php declare(strict_types=1);

namespace Shopware\Core;

(new TestBootstrapper())
    ->setPlatformEmbedded(false)
    ->setEnableCommercial()
    ->addActivePlugins('SolidAdvancedSliderElements')
    ->bootstrap();
