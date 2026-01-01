import template from './zen-alert-translation.html.twig';

const { Component } = Shopware;
const registry = Component.getComponentRegistry();

if (!registry.has('zen-alert-translation')) {
    Component.register('zen-alert-translation', {
        template,
    });
}
