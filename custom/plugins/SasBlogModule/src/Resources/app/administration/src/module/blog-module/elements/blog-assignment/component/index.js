import template from './sw-cms-el-blog-assignment.html.twig';
import './sw-cms-el-blog-assignment.scss';

const { Mixin } = Shopware;
const { isEmpty } = Shopware.Utils.types;

/**
 * @private
 * @package buyers-experience
 */
export default {
    template,

    mixins: [Mixin.getByName('cms-element'), Mixin.getByName('placeholder')],

    data() {
        return {
            sliderBoxLimit: 3,
        };
    },

    computed: {
        demoBlogElement() {
            return {
                config: {
                    boxLayout: {
                        source: 'static',
                        value: this.element.config.boxLayout.value,
                    },
                    displayMode: {
                        source: 'static',
                        value: this.element.config.displayMode.value,
                    },
                    elMinWidth: {
                        source: 'static',
                        value: this.element.config.elMinWidth.value,
                    },
                    showRandom: {
                        source: 'static',
                        value: this.element.config.showRandom.value,
                    },
                },
            };
        },

        sliderBoxMinWidth() {
            if (
                this.element.config.elMinWidth.value &&
                this.element.config.elMinWidth.value.indexOf('px') > -1
            ) {
                return `repeat(auto-fit, minmax(${this.element.config.elMinWidth.value}, 1fr))`;
            }

            return null;
        },

        currentDeviceView() {
            return this.cmsPageState.currentCmsDeviceView;
        },

        assignment() {
            if (this.element.data.product) {
                return {
                    name:
                        this.element.data.product.translated?.name
                            ? `Articles about ${this.element.data.product.translated.name}`
                            : this.element.data.product.name,
                };
            }

            return {
                name: 'Articles title',
            };
        },

        assignmentBlogs() {
            return this.element.data.product?.extensions?.assignedBlogs
                ? this.element.data.product.extensions.assignedBlogs
                : [];
        },

        currentDemoEntity() {
            if (this.cmsPageState.currentMappingEntity === 'product') {
                return this.cmsPageState.currentDemoEntity;
            }

            return null;
        },
    },

    watch: {
        'element.config.elMinWidth.value': {
            handler() {
                this.setSliderRowLimit();
            },
        },

        currentDeviceView() {
            setTimeout(() => {
                this.setSliderRowLimit();
            }, 400);
        },
    },

    created() {
        this.createdComponent();
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('blog-assignment');
            this.initElementData('blog-assignment');
        },

        mountedComponent() {
            this.setSliderRowLimit();
        },

        setSliderRowLimit() {
            if (isEmpty(this.element.config)) {
                this.createdComponent();
            }

            if (
                this.currentDeviceView === 'mobile' ||
                (this.$refs.productHolder &&
                    this.$refs.productHolder.offsetWidth < 500)
            ) {
                this.sliderBoxLimit = 1;
                return;
            }

            if (
                !this.element.config.elMinWidth.value ||
                this.element.config.elMinWidth.value === 'px' ||
                this.element.config.elMinWidth.value.indexOf('px') === -1
            ) {
                this.sliderBoxLimit = 3;
                return;
            }

            if (
                parseInt(
                    this.element.config.elMinWidth.value.replace('px', ''),
                    10,
                ) <= 0
            ) {
                return;
            }

            if (this.$refs.productHolder) {
                // Subtract to fake look in storefront which has more width
                const fakeLookWidth = 100;
                const boxWidth = this.$refs.productHolder.offsetWidth;
                const elGap = 32;
                let elWidth = parseInt(
                    this.element.config.elMinWidth.value.replace('px', ''),
                    10,
                );

                if (elWidth >= 300) {
                    elWidth -= fakeLookWidth;
                }

                this.sliderBoxLimit = Math.floor(boxWidth / (elWidth + elGap));
            }
        },

        getBlogEl(blog) {
            return {
                config: {
                    boxLayout: {
                        source: 'static',
                        value: this.element.config.boxLayout.value,
                    },
                    displayMode: {
                        source: 'static',
                        value: this.element.config.displayMode.value,
                    },
                },
                data: {
                    blog,
                },
            };
        },
    },
};
