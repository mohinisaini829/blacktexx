import myfavinquiry from './form-myfav-inquiry/index';
//import contact from 'src/module/sw-cms/elements/form/component/templates/form-contact/index';
//import newsletter from 'src/module/sw-cms/elements/form/component/templates/form-newsletter/index';

const {Component} = Shopware;

Component.override('sw-cms-el-form', {
    components: {
        myfavinquiry,
        //contact,
        //newsletter,
    },
});