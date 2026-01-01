import { ACTION } from '../../constant/myfav-inquiry.constant';

const { Component } = Shopware;
//console.log("jjjjjjjjjjjjjjj123");
Component.override('sw-flow-sequence-action', {
    computed: {
        modalName() {
            console.log('[myfav-inquiry] selectedAction:', this.selectedAction);
            if (this.selectedAction === ACTION.SEND_INQUIRY) {
                console.log("jjjjjjjjjjjjjjj");
                console.log(this.selectedAction);
                return 'myfav-inquiry-modal';
            }

            return this.$super('modalName');
        },

        actionDescription() {
            const actionDescriptionList = this.$super('actionDescription');

            return {
                ...actionDescriptionList,
                [ACTION.SEND_INQUIRY] : (config) => this.getSendMailDescription(config),
            };
        },
    },

    methods: {
        getSendMailDescription(config) {
            //const tags = config.tags.join(', ');

           return this.$tc('myfav-inquiry.descriptionSendMail', 0, {
                //tags
            });
        },

        getActionTitle(actionName) {
            if (actionName === ACTION.SEND_INQUIRY) {
                return {
                    value: actionName,
                    icon: 'default-badge-help',
                    label: this.$tc('myfav-inquiry.titleSendMail'),
                }
            }

            return this.$super('getActionTitle', actionName);
        },
    },
});
