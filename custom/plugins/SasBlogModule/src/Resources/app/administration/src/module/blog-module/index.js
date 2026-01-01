/**
 * Extensions
 */
/**
 * Views
 */

import './view/sas-product-detail-blog-assignment';
/**
 * Components
 */

import './component/blog-extension-component-sections';

// import './extension/sw-cms/page/sw-cms-create';

import './extension/component/form/sas-text-field';
import './extension/component/form/sas-textarea-field';
import './extension/component/cms/sas-cms-section';
import './extension/component/cms/sas-cms-sidebar';
import './extension/component/cms/sas-cms-product-assignment-modal';

/**
 * privileges
 */
import './page/sas-blog-detail/acl';
import './page/sas-blog-author/acl';
import './page/sas-blog-list/acl';

/**
 * Pages
 */
import './page/sas-blog-list';
import './page/sas-blog-create';
import './page/sas-blog-detail';
import './page/sw-product-detail';

/**
 * CMS Blocks
 */
import './blocks/listing';
import './blocks/detail';
import './blocks/single-entry';
import './blocks/newest-listing';
import './blocks/assignment';

/**
 * CMS Elements
 */
import './elements/blog-detail';
import './elements/blog';
import './elements/blog-single-select';
import './elements/blog-newest-listing';
import './elements/blog-assignment';
import './elements/blog-box';

/**
 * Blog Category
 */
import './component/blog-tree';
import './component/blog-tree-item';
import './component/blog-category-tree';
import './component/blog-category-tree-field';
import './component/sw-cms-blog-box-preview';

/**
 * Blog author
 */
import './page/sas-blog-author/sas-blog-author-list';
import './page/sas-blog-author/sas-blog-author-detail';
import './page/sas-blog-author/sas-blog-author-create';

import './component/blog-vertical-tabs';

/**
 * Blog tag
 */
import './page/sas-tag/sas-tag-list';
import './page/sas-tag/sas-tag-detail';
import './page/sas-tag/sas-tag-create';

Shopware.Component.override(
    'sw-cms-sidebar',
    () => import('./extension/sw-cms/component/sw-cms-sidebar'),
);
Shopware.Component.override(
    'sw-sidebar-item',
    () => import('./extension/sw-cms/component/sw-sidebar-item'),
);
Shopware.Component.override(
    'sw-cms-list',
    () => import('./extension/sw-cms/page/sw-cms-list'),
);
Shopware.Component.override(
    'sw-cms-detail',
    () => import('./extension/sw-cms/page/sw-cms-detail'),
);
Shopware.Component.override(
    'sw-cms-create',
    () => import('./extension/sw-cms/page/sw-cms-create'),
);
Shopware.Component.override(
    'sw-settings-cache-index',
    () => import('./extension/sw-settings-cache/page/sw-settings-cache-index'),
);
Shopware.Component.override(
    'sw-search-bar-item',
    () => import('./component/structure/sw-search-bar-item'),
);

const { Module } = Shopware;

Module.register('blog-module', {
    entity: 'sas_blog_entries',
    type: 'plugin',
    name: 'Blog',
    title: 'sas-blog.general.mainMenuItemGeneral',
    description: 'sas-blog.general.descriptionTextModule',
    color: '#F965AF',
    icon: 'regular-content',

    routes: {
        index: {
            components: {
                default: 'sas-blog-list',
            },
            path: 'index',
        },
        create: {
            components: {
                default: 'sas-blog-create',
            },
            path: 'create',
        },
        detail: {
            component: 'sas-blog-detail',
            path: 'detail/:id',
        },
        author: {
            path: 'author',
            component: 'sas-blog-author-list',
            meta: {
                parentPath: 'blog.module.index',
            },
            redirect: {
                name: 'blog.module.author.index',
            },
        },
        'author.index': {
            path: 'author/index',
            component: 'sas-blog-author-list',
        },
        'author.create': {
            path: 'author/new',
            component: 'sas-blog-author-create',
            meta: {
                parentPath: 'blog.module.author.index',
            },
        },
        'author.detail': {
            path: 'author/detail/:id',
            component: 'sas-blog-author-detail',
            meta: {
                parentPath: 'blog.module.author.index',
            },
        },
        tag: {
            path: 'blog-tag',
            component: 'sas-tag-list',
            meta: {
                parentPath: 'blog.module.index',
            },
            redirect: {
                name: 'blog.module.tag.index',
            },
        },
        'tag.index': {
            path: 'blog-tag/index',
            component: 'sas-tag-list',
        },
        'tag.create': {
            path: 'blog-tag/new',
            component: 'sas-tag-create',
            meta: {
                parentPath: 'blog.module.tag.index',
            },
        },
        'tag.detail': {
            path: 'blog-tag/detail/:id',
            component: 'sas-tag-detail',
            meta: {
                parentPath: 'blog.module.tag.index',
            },
        },
    },

    navigation: [
        {
            id: 'sas-blog',
            label: 'sas-blog.general.mainMenuItemGeneral',
            path: 'blog.module.index',
            parent: 'sw-content',
            meta: {
                privilege: [
                    'sas-blog-category:read',
                    'sas_blog_author:read',
                    'sas_tag:read',
                    'sas_blog_entries:read',
                ],
            },
        },
    ],

    defaultSearchConfiguration: {
        _searchable: true,
        title: {
            _searchable: true,
            _score: 500,
        },
        slug: {
            name: {
                _searchable: true,
                _score: 500,
            },
        },
        teaser: {
            name: {
                _searchable: true,
                _score: 500,
            },
        },
        metaTitle: {
            name: {
                _searchable: true,
                _score: 250,
            },
        },
        metaKeywords: {
            name: {
                _searchable: true,
                _score: 250,
            },
        },
        metaDescription: {
            name: {
                _searchable: true,
                _score: 250,
            },
        },
        blogAuthor: {
            firstName: {
                _searchable: true,
                _score: 300,
            },
            lastName: {
                _searchable: true,
                _score: 300,
            },
            email: {
                _searchable: true,
                _score: 300,
            },
            displayName: {
                _searchable: true,
                _score: 300,
            },
        },
    },
});

Shopware.Application.addServiceProviderDecorator(
    'searchTypeService',
    (searchTypeService) => {
        searchTypeService.upsertType('sas_blog_entries', {
            entityName: 'sas_blog_entries',
            placeholderSnippet: 'sas-blog.general.placeholderSearchBar',
            listingRoute: 'blog.module.index',
            hideOnGlobalSearchBar: false,
        });

        return searchTypeService;
    },
);

Shopware.Application.addServiceProviderDecorator(
    'customFieldDataProviderService',
    (customFieldDataProviderService) => {
        customFieldDataProviderService.addEntityName('sas_blog_entries');

        return customFieldDataProviderService;
    },
);

Shopware.Module.register('sas-blog-assignment-tab', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sas.product.detail.blog-assignment',
                path: '/sw/product/detail/:id/blog-assignment',
                component: 'sas-product-detail-blog-assignment',
                meta: {
                    parentPath: 'sw.product.index',
                },
            });
        }

        next(currentRoute);
    },
});
