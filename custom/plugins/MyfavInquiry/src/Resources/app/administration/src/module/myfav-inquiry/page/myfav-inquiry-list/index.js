import template from './myfav-inquiry-list.html.twig';
import InquiryStatusChangeApiService from '../../service/api/inquiry-media.api.service';
const {Criteria} = Shopware.Data;
const { Component, Application, Service, Mixin } = Shopware;

Component.register('myfav-inquiry-list', {
    template,

    inject: [
        'repositoryFactory',
        'acl'
    ],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],

    data() {
        return {
            repository: null,
            inquiries: null,
            total: 0,
            isLoading: false,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            statusOptions: [
                { value: 'new', label: 'New' },
                { value: 'processing', label: 'In Process' },
                { value: 'done', label: 'Done' }
            ]
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [{
                dataIndex: 'company,firstName,lastName',
                property: 'lastName',
                label: this.$t('myfav-inquiry.list.columnName'),
                allowResize: true,
            },{
                property: 'lineItems',
                label: this.$t('myfav-inquiry.list.columnCount'),
                allowResize: true,
                sortable: false
            },{
                dataIndex: 'createdAt',
                property: 'createdAt',
                label: this.$t('myfav-inquiry.list.columnCreatedAt'),
                allowResize: true,
            },{
                dataIndex: 'deliveryDate',
                property: 'deliveryDate',
                label: this.$t('myfav-inquiry.list.columnDeliveryDateAt'),
                allowResize: true,
            },
            {
                dataIndex: 'adminUser',
                property: 'adminUser',
                label: this.$t('myfav-inquiry.list.columnAdminUser'),
                allowResize: true,
            },
            {
                dataIndex: 'status',
                property: 'processStatus',
                label: this.$t('myfav-inquiry.list.columnProcessStatus'),
                allowResize: true,
            }];
        }
    },
    /*mounted() {
        const selectElement = this.$el.querySelector('sw-single-select');
        if (selectElement) {
            selectElement.addEventListener('change', (event) => {
                console.log('Event Triggered:', event);
                this.handleStatusChange(event.target.value);
            });
        }
    },*/

    methods: {
        getList() {
            this.isLoading = true;
            //let criteria = new Criteria();
            let criteria = new Criteria(this.page, this.limit);

            criteria
                .addAssociation('medias')
                .addAssociation('lineItems')
                .addAssociation('salutation');

            this.sortBy.split(',').forEach(sortBy => {
                criteria.addSorting(Criteria.sort(sortBy, this.sortDirection));
            });

            if (this.repository !== null) {
                this.repository
                    .search(criteria, Shopware.Context.api)
                    .then((result) => {
                        this.total = result.total;
                        this.selection = {};
                        this.inquiries = result;
                        this.isLoading = false;
                    });
            }
        },

        // This method is invoked when status is changed in the select box
        handleStatusChange(item, newStatus) {
            this.isLoading = true;

            this.inquiryStatusChangeApiService.changeStatus(item, newStatus)
                .then((response) => {
                    const data = response?.data;
                    console.log(data);

                    if (response.data && response.data.success) {
                        // ✅ Backend confirmed update
                        //item.status = newStatus;
                        const inquiry = this.inquiries.find(i => i.id === item);
                        if (inquiry) {
                            inquiry.status = newStatus;  // Update local state
                        }
                        this.createNotificationSuccess({
                            title: 'Success',
                            message: 'Status updated successfully.'
                        });

                        // Optional: Navigate to listing page
                        //this.$router.push({ name: 'myfav-inquiry.list' });
                    } else {
                        // ❌ Backend returned success: false
                        this.createNotificationError({
                            title: 'Error',
                            message: data?.message || 'Failed to update status.'
                        });
                    }

                    this.isLoading = false;
                })
                .catch((error) => {
                    // ❌ Network/server error or 4xx/5xx status
                    console.error('API Error:', error?.response || error);

                    this.createNotificationError({
                        title: 'Error',
                        message: error?.response?.data?.message || 'Failed to update status.'
                    });

                    this.isLoading = false;
                });
        },

        updateTotal({total}) {
            this.total = total;
        }
    },
   /* mounted() {
    console.log('Component Mounted!');
    // Manually trigger change event for testing
    this.handleStatusChange('01998131D22471F89BC72061CF29574F', 'done');
    },*/
    created() {
        const httpClient = Application.getContainer('init')['httpClient'];
        const loginService = Service('loginService');

        if (!httpClient) {
            console.error('httpClient is undefined!');
            return;
        }
        this.inquiryStatusChangeApiService = new InquiryStatusChangeApiService(httpClient, loginService);
        this.repository = this.repositoryFactory.create('myfav_inquiry');
        this.getList();
    }
});
