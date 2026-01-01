import template from './zen-alert-text-only.html.twig';
import './zen-alert-text-only.scss';

const { Component } = Shopware;
const registry = Component.getComponentRegistry();

if (!registry.has('zen-alert-text-only')) {
    Component.register('zen-alert-text-only', {
        template,
    });
}
