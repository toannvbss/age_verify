define(['jquery', 'moment', 'jquery/validate'], function ($, moment) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'age_verify',
            function (value) {
                if (value === '') {
                    return true;
                }
                let birthday = moment().subtract(18, "year");
                return moment(value).isBefore(birthday);
            },
            $.mage.__('Only people whose age > 18 years old can register with the account!')
        )
    }
});

