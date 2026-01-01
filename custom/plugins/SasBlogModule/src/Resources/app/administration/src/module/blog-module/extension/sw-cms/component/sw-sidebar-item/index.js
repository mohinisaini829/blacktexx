export default {
    mounted() {
        this.$nextTick(() => {
            this.$emit('component-mounted', true);
        });
    },
};
