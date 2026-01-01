export default {
    methods: {
        createdComponent() {
            this.indexers['sas.blog.category.indexer'] = [];
            this.indexers['sas.blog.entities.indexer'] = [];
            this.$super('createdComponent');
        },
    },
};
