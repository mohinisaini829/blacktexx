import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'netzp-powerpack6-infobar',
    label: 'sw-cms.netzp-powerpack6.elements.infobar.label',
    component: 'sw-cms-el-netzp-powerpack6-infobar',
    configComponent: 'sw-cms-el-config-netzp-powerpack6-infobar',
    previewComponent: 'sw-cms-el-preview-netzp-powerpack6-infobar',

    defaultConfig: {
        numberOfItems: { source: 'static', value: 3 },

        layout: { source: 'static', value: 'horizontal' },
        textSize: { source: 'static', value: 2 },
        iconSize: { source: 'static', value: 2 },
        textColor: { source: 'static', value: '#000000' },
        iconColor: { source: 'static', value: '#cccccc' },
        circleColor: { source: 'static', value: '#eeeeee' },

        item1: { source: 'static', value: 'Info 1' },
        item2: { source: 'static', value: 'Info 2' },
        item3: { source: 'static', value: 'Info 3' },
        item4: { source: 'static', value: 'Info 4' },
        item5: { source: 'static', value: 'Info 5' },
        item6: { source: 'static', value: 'Info 6' },
        item7: { source: 'static', value: 'Info 7' },
        item8: { source: 'static', value: 'Info 8' },
        item9: { source: 'static', value: 'Info 9' },
        item10:{ source: 'static', value: 'Info 10' },

        icon1: { source: 'static', value: 'fa-check-circle' },
        icon2: { source: 'static', value: 'fa-check-circle' },
        icon3: { source: 'static', value: 'fa-check-circle' },
        icon4: { source: 'static', value: 'fa-check-circle' },
        icon5: { source: 'static', value: 'fa-check-circle' },
        icon6: { source: 'static', value: 'fa-check-circle' },
        icon7: { source: 'static', value: 'fa-check-circle' },
        icon8: { source: 'static', value: 'fa-check-circle' },
        icon9: { source: 'static', value: 'fa-check-circle' },
        icon10:{ source: 'static', value: 'fa-check-circle' },

        link1: { source: 'static', value: '' },
        link2: { source: 'static', value: '' },
        link3: { source: 'static', value: '' },
        link4: { source: 'static', value: '' },
        link5: { source: 'static', value: '' },
        link6: { source: 'static', value: '' },
        link7: { source: 'static', value: '' },
        link8: { source: 'static', value: '' },
        link9: { source: 'static', value: '' },
        link10: { source: 'static', value: '' },

        linkNewWindow1: { source: 'static', value: false },
        linkNewWindow2: { source: 'static', value: false },
        linkNewWindow3: { source: 'static', value: false },
        linkNewWindow4: { source: 'static', value: false },
        linkNewWindow5: { source: 'static', value: false },
        linkNewWindow6: { source: 'static', value: false },
        linkNewWindow7: { source: 'static', value: false },
        linkNewWindow8: { source: 'static', value: false },
        linkNewWindow9: { source: 'static', value: false },
        linkNewWindow10: { source: 'static', value: false }
    }
});
