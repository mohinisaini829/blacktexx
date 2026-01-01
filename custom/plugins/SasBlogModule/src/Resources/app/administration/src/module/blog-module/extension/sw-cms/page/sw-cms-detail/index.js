export default {
    methods: {
        createdComponent() {
            this.productDetailBlocks = [
                {
                    type: 'blog-assignment',
                    elements: [
                        {
                            slot: 'content',
                            type: 'blog-assignment',
                            config: {},
                        },
                    ],
                },

                ...this.productDetailBlocks,
            ];

            this.$super('createdComponent');
        },
    },
};
