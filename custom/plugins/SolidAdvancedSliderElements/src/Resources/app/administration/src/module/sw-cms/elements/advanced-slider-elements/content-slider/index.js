import './component';
import './config';
import './preview';

const { Service } = Shopware;

Service('cmsService').registerCmsElement({
    name: 'solid-ase-content-slider',
    label: 'sw-cms.elements.solid-ase.content-slider.label',
    component: 'sw-cms-el-solid-ase-content-slider',
    configComponent: 'sw-cms-el-config-solid-ase-content-slider',
    previewComponent: 'sw-cms-el-preview-solid-ase-content-slider',
    defaultConfig: {
        slides: {
            source: 'static',
            value: [
                {
                    active: true,
                    name: 'Slide 1',
                    contentType: 'default',

                    // Content
                    smallHeadline: 'Lorem ipsum',
                    headline: 'Lorem ipsum dolor sit amet',
                    text: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                    buttonLabel: 'Lorem ipsum',
                    buttonLinkType: 'external',
                    buttonLinkEntity: 'category',
                    buttonLink: null,
                    buttonLinkInternalQuery: '',
                    buttonTargetBlank: false,
                    buttonTitle: '',
                    customContent: '',

                    // Background
                    backgroundColor: '',
                    backgroundMedia: null,
                    backgroundSizingMode: 'cover',
                    backgroundPosition: 'center',
                    backgroundAnimation: 'move',

                    // Link
                    linkType: 'external',
                    linkEntity: 'category',
                    link: null,
                    linkInternalQuery: '',
                    linkTargetBlank: false,
                    linkTitle: '',

                    // Publishing
                    publishingType: 'instant',
                    scheduledPublishingDateTime: null,
                    scheduledUnpublishingDateTime: null
                },
            ],
        },
        sliderSettings: {
            source: 'static',
            value: {
                // General
                animation: 'slider-horizontal-ease-in-out-sine',
                itemsMode: 'responsive-automatic',
                items: '1',
                itemsMobile: '1',
                itemsTablet: '1',
                itemsDesktop: '1',
                startIndex: '0',
                speed: '500',
                slideBy: '1',
                slideByMobile: '1',
                slideByTablet: '1',
                slideByDesktop: '1',
                gutter: '0',
                loop: true,
                rewind: false,

                // Navigation
                controls: true,
                mouseDrag: true,
                nav: true,

                // Autoplay
                autoplay: true,
                autoplayDirection: 'forward',
                autoplayHoverPause: false,
                autoplayTimeout: '4000',
            },
        },
        settings: {
            source: 'static',
            value: {
                // General
                sizingMode: 'responsive-min-height',
                minHeightMobile: '300px',
                minHeightTablet: '500px',
                minHeightDesktop: '800px',
                minAspectRatioWidth: '2',
                minAspectRatioHeight: '1',

                // Content
                layoutVariant: 'overlay-center',
                contentAnimation: 'none',
                smallHeadlineSizeMobile: '',
                smallHeadlineSizeTablet: '',
                smallHeadlineSizeDesktop: '',
                smallHeadlineWeight: '',
                headlineSizeMobile: '',
                headlineSizeTablet: '',
                headlineSizeDesktop: '',
                headlineWeight: '',
                textSizeMobile: '',
                textSizeTablet: '',
                textSizeDesktop: '',
                textWeight: '',
                buttonLabelSizeMobile: '',
                buttonLabelSizeTablet: '',
                buttonLabelSizeDesktop: '',
                buttonLabelWeight: '',
                buttonVariant: 'primary',
                smallHeadlineMarginBottomMobile: '',
                smallHeadlineMarginBottomTablet: '',
                smallHeadlineMarginBottomDesktop: '',
                headlineMarginBottomMobile: '',
                headlineMarginBottomTablet: '',
                headlineMarginBottomDesktop: '',
                textMarginBottomMobile: '',
                textMarginBottomTablet: '',
                textMarginBottomDesktop: '',
                smallHeadlineColor: '',
                headlineColor: '',
                textColor: '',
                buttonColor: '',
                contentBackgroundColor: '#00000080',

                // Navigation
                controlsVariant: 'round',
                controlsIconVariant: 'arrow',
                controlsPosition: 'horizontal-inside-center-edges',
                controlsColor: '',
                controlsCustomImagePrevious: null,
                controlsCustomImageNext: null,
                navVariant: 'dots-fill',
                navSize: 'medium',
                navPosition: 'horizontal-bottom-center',
                navColor: '#00000099',

                // Custom
                customCss: '',
            },
        },
    },
});
