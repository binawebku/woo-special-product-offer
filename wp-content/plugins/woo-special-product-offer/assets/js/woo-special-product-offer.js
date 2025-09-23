(function ($) {
    'use strict';

    var formatPercentage = function (value) {
        var number = parseFloat(value);

        if (isNaN(number)) {
            return '';
        }

        if (Math.abs(number % 1) < 0.0001) {
            return number.toFixed(0);
        }

        return number.toFixed(2).replace(/\.0+$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
    };

    var ready = function () {
        $('.wspo-purchase-options').each(function () {
            var $container = $(this);
            var $options = $container.find("input[name='wspo_purchase_type']");
            var $frequency = $container.find('.wspo-frequency');
            var $note = $container.find('.wspo-price-note');
            var discount = parseFloat($container.data('discount')) || 0;
            var strings = window.wspoPurchaseOptions && window.wspoPurchaseOptions.strings ? window.wspoPurchaseOptions.strings : {};

            var getString = function (key) {
                return strings && strings[key] ? strings[key] : '';
            };

            var updateNote = function (type) {
                if (!$note.length) {
                    return;
                }

                var message = '';

                if (type === 'subscription') {
                    if (discount > 0 && getString('subscriptionTemplate')) {
                        message = getString('subscriptionTemplate').replace('%s', formatPercentage(discount));
                    } else {
                        message = getString('subscriptionNoDiscount');
                    }
                } else {
                    message = getString('oneTime');
                }

                if (message) {
                    $note.text(message).addClass('is-visible');
                } else {
                    $note.text('').removeClass('is-visible');
                }
            };

            var toggleOptions = function () {
                var $selected = $options.filter(':checked');
                var type = $selected.length ? $selected.val() : 'one_time';
                var showSubscription = type === 'subscription';

                if ($frequency.length) {
                    $frequency.toggleClass('wspo-hidden', !showSubscription);
                }

                $container.toggleClass('is-subscription', showSubscription);
                $container.find('.wspo-option-card').removeClass('is-active');

                if ($selected.length) {
                    $selected.closest('.wspo-option-card').addClass('is-active');
                }

                updateNote(type);
            };

            $options.on('change', toggleOptions);

            toggleOptions();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
})(jQuery);
