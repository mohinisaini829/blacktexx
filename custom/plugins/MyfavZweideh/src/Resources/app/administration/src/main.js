import './page/sw-product-detail';
import './view/sw-product-detail-zweideh-designer';

// Here you create your new route, refer to the mentioned guide for more information
Shopware.Module.register('sw-new-tab-zweideh-designer', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sw.product.detail.zweideh.designer',
                path: '/sw/product/detail/:id/zweideh/designer',
                component: 'sw-product-detail-zweideh-designer',
                meta: {
                    parentPath: "sw.product.index"
                }
            });
        }
        next(currentRoute);
    }
});