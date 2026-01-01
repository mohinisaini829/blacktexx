<?php

declare(strict_types=1);

namespace Acris\CookieConsent\DependencyInjection\CompilerPass;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CookieConsentCompilerPass implements CompilerPassInterface
{
    const DEFAULT_SANITIZER_SET = 'shopware.html_sanitizer.sets';

    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::DEFAULT_SANITIZER_SET)) {
            $set = $container->getParameter(self::DEFAULT_SANITIZER_SET);
            $additionalAttributes = ['onclick', 'data-ajax-modal', 'data-modal-class', 'data-url'];
            $attributes = null;
            $customAttributes = null;

            if (isset($set['bootstrap']['attributes'])) {
                $attributes = array_merge($set['bootstrap']['attributes'], $additionalAttributes);
            }

            if (isset($set['bootstrap']['custom_attributes'][0]['attributes'])) {
                $customAttributes = array_merge($set['bootstrap']['custom_attributes'][0]['attributes'], $additionalAttributes);
            }

            if (!empty($attributes) && !empty($customAttributes)) {
                $set['bootstrap']['attributes'] = $attributes;
                $set['bootstrap']['custom_attributes'][0]['attributes'] = $customAttributes;
            }

            $container->setParameter(self::DEFAULT_SANITIZER_SET, $set);
        }
    }


}
