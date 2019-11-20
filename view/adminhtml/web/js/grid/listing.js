/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_AdminNotification/js/grid/listing',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'underscore',
    'jquery'
], function (Listing, uiAlert, $t, _, $) {
    'use strict';

    return Listing.extend({
        defaults: {
            isAllowed: true,
            ajaxSettings: {
                method: 'POST',
                data: {},
                url: '${ $.clearMessageUrl }'
            }
        },

        /** @inheritdoc */
        initialize: function () {
            _.bindAll(this, 'reload', 'onError');

            return this._super();
        },

        clearMessage: function () {
            var config = _.extend({}, this.ajaxSettings);
            config.data['form_key'] = FORM_KEY;
            $.ajax(config)
                .done(this.reload)
                .fail(this.onError);
        },

        /**
         * Success callback for dismiss request.
         */
        reload: function () {
            location.reload();
        },

        /**
         * Error callback for dismiss request.
         *
         * @param {Object} xhr
         */
        onError: function (xhr) {
            this.hideLoader();

            if (xhr.statusText === 'abort') {
                return;
            }

            uiAlert({
                content: $t('Something went wrong.')
            });
        }
    });
});
