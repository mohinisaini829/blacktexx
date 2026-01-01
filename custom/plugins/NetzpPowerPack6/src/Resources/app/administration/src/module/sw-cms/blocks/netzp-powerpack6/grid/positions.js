// https://github.com/shopware/platform/blob/v6.4.7.0/UPGRADE-6.4.md#position-constants-for-cms-slots
Shopware.Component.override('sw-cms-page-form', {
    computed: {
        slotPositions()
        {
            const myPositions = {
                'column1': 9000,
                'column2': 9002,
                'column3': 9003,
                'column4': 9004,
                'column5': 9005,
                'column6': 9006,

                'image1':  9010,
                'text1':   9011,
                'image2':  9012,
                'text2':   9013,
                'image3':  9014,
                'text3':   9015,
                'image4':  9016,
                'text4':   9017
            };

            return {
                ...myPositions,
                ...this.$super('slotPositions'),
            };
        }
    },
});
