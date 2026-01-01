import template from './zen-alert-info.html.twig';

const { Component } = Shopware;
const registry = Component.getComponentRegistry();

if (!registry.has('zen-alert-info')) {
    Component.register('zen-alert-info', {
        template,
    });
}
