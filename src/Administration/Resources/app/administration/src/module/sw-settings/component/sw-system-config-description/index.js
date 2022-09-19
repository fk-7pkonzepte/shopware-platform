import template from './sw-system-config-description.html.twig';

const { Component } = Shopware;

Component.register('sw-system-config-description', {
    template,

    props: {
        content: {
            required: true,
            type: String,
        }
    }

});
