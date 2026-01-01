import slugify from 'slugify';
import template from './sas-blog-list.twig';
import './sas-blog-list.scss';

const { Component, Mixin } = Shopware;
const Criteria = Shopware.Data.Criteria;

Component.register('sas-blog-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('salutation'), Mixin.getByName('listing')],

    data() {
        return {
            categoryId: null,
            blogEntries: null,
            total: 0,
            localeLanguage: null,
            isLoading: true,
            currentLanguageId: Shopware.Context.api.languageId,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    created() {
        this.getList();
    },

    computed: {
        blogEntriesRepository() {
            return this.repositoryFactory.create('sas_blog_entries');
        },

        blogCategoryRepository() {
            return this.repositoryFactory.create('sas_blog_category');
        },

        cmsPageRepository() {
            return this.repositoryFactory.create('cms_page');
        },

        localeRepository() {
            return this.repositoryFactory.create('locale');
        },

        columns() {
            return [
                {
                    property: 'title',
                    dataIndex: 'title',
                    label: this.$tc('sas-blog.list.table.title'),
                    routerLink: 'blog.module.detail',
                    primary: true,
                    inlineEdit: 'string',
                },
                {
                    property: 'author',
                    label: this.$tc('sas-blog.list.table.author'),
                    inlineEdit: false,
                },
                {
                    property: 'active',
                    label: this.$tc('sas-blog.list.table.active'),
                    inlineEdit: 'boolean',
                },
            ];
        },
        allowCreate() {
            return (
                this.currentLanguageId === Shopware.Context.api.systemLanguageId
            );
        },

        allowEdit() {
            return true;
        },
    },

    methods: {
        changeLanguage(newLanguageId) {
            this.currentLanguageId = newLanguageId;
            this.getList();
        },

        changeCategoryId(categoryId) {
            if (categoryId && categoryId !== this.categoryId) {
                this.categoryId = categoryId;
                this.getList();
            }
        },

        async getList() {
            this.isLoading = true;
            let criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addAssociation('blogAuthor');
            criteria.addAssociation('blogCategories');

            criteria.addSorting(Criteria.sort('publishedAt', 'DESC', false));

            if (this.categoryId) {
                criteria.addFilter(
                    Criteria.equals('blogCategories.id', this.categoryId),
                );
            }

            criteria = await this.addQueryScores(this.term, criteria);

            return this.blogEntriesRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.total = result.total;
                    this.blogEntries = result;
                    this.isLoading = false;
                    this.getLocaleLanguage();
                });
        },

        getLocaleLanguage() {
            return this.localeRepository
                .get(
                    Shopware.Context.api.language.localeId,
                    Shopware.Context.api,
                )
                .then((result) => {
                    this.localeLanguage = result.code
                        .substr(0, result.code.length - 3)
                        .toLowerCase();
                    return Promise.resolve(this.localeLanguage);
                });
        },

        async onDuplicate(item) {
            const cloneBlog = item;
            this.isLoading = true;
            const title = `${item.title} ${this.$tc('global.default.copy')} ${Date.now()}`;
            const slug = slugify(title ?? '', {
                locale: this.localeLanguage,
                lower: true,
            });

            const cmsPageClone = await this.cmsPageRepository.clone(
                cloneBlog.cmsPageId,
                {},
                Shopware.Context.api,
            );

            const behavior = {
                overwrites: {
                    title: title,
                    slug: slug,
                    active: false,
                    cmsPageId: cmsPageClone.id,
                },
            };

            try {
                const clone = await this.blogEntriesRepository.clone(
                    cloneBlog.id,
                    behavior,
                    Shopware.Context.api,
                );

                this.$nextTick(() => {
                    this.$router.push({
                        name: 'blog.module.detail',
                        params: { id: clone.id },
                    });
                });
            } catch {
                this.cmsPageRepository.delete(
                    cmsPageClone.id,
                    Shopware.Context.api,
                );
                this.isLoading = false;
            }
        },
    },
});
