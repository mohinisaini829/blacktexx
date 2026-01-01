import template from './brand-data.html.twig';

const { Component } = Shopware;

Component.register('brand-data-page', {

    template,
    data() {

        return {
            dropdownValue: '',
            text1: '',
            text2: '',
            mediaId: null,
            isLoading: false,
            success: false,
            error: null
        };
    },
    methods: {
        onMediaSelected(media) {
            if (Array.isArray(media) && media.length > 0) {
                this.mediaId = media[0].id;
            } else if (media && media.id) {
                this.mediaId = media.id;
            } else {
                this.mediaId = null;
            }
            console.log('Selected mediaId:', this.mediaId);
        },
        async saveForm() {
            // Do NOT use 'media' here!
            if (!this.mediaId) {
                this.error = 'Please select or upload a media file.';
                return;
            }
            console.log('Selected mediaId:', this.mediaId);
            console.log('hhhhhhhhhhhhhhhssss '+this.mediaId);
            this.isLoading = true;
            this.success = false;
            this.error = null;
            try {
                await this.$http.post(
                    '/api/_action/custombrand/branddata/save',
                    {
                        dropdown_value: this.dropdownValue,
                        text1: this.text1,
                        text2: this.text2,
                        media_id: this.mediaId
                    }
                );
                console.log('success');
                this.success = true;
            } catch (e) {
                console.log('error');
                this.error = e;
            }
            this.isLoading = false;
        }
    }
});