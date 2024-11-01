jQuery(function ($) {
    'use strict';
    $(function () {
        if (typeof fastpay_button_manager === 'undefined') {
            console.log("FastPayNow button manager is undefined");
            return false;
        }

        var loading = false;
        var is_from_product = 'product' === fastpay_button_manager.page && fastpay_button_manager.show_fastpay_button_on_product_page === 'yes';
        var is_from_checkout = 'checkout' === fastpay_button_manager.page && fastpay_button_manager.show_fastpay_button_on_checkout_page === 'yes';
        var is_from_cart = 'cart' === fastpay_button_manager.page && fastpay_button_manager.show_fastpay_button_on_cart_page === 'yes';
        var is_sale = true; // 'capture' === fastpay_button_manager.paymentaction;
        var render_buttons = function () {
            // Create Fastpay Button at Product Page
            if (is_from_product === true) {
                var content = `
                    <div data-product_id="${fastpay_button_manager.product_page_button_color_code}" id="fastpay-product-button"
                        class="fastpay-button"
                        name="fp-submit"
                        style="background-color: ${fastpay_button_manager.product_page_button_color_code}; color: ${fastpay_button_manager.product_page_button_font_color_code}; border: 1px solid ${fastpay_button_manager.product_page_button_border_color_code}; border-radius: ${fastpay_button_manager.product_page_button_border_radius}px;">
                        <img style="height:16pt;width: auto; display:inline-block; padding-right: 6px; vertical-align: middle;" src="${fastpay_button_manager.product_page_lightning_svg_location}" />
                        ${fastpay_button_manager.product_page_button_label}
                    </div>
                `;
                $("#fastpay_product").html(content);

                $("#fastpay_product").on('click', function () {
                    if (loading === true)
                        return true;

                    loading = true;

                    var data = {
                        id: fastpay_button_manager.product_id,
                        quantity: jQuery("input[name*='quantity']").val(),
                        variation_id: jQuery("input[name*='variation_id']").val(),
                    };
                    return fetch(fastpay_button_manager.fastpay_link_from_product_page, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams(data).toString(),
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        loading = false;
                        if (typeof data.code !== 'undefined') {
                            location.href = data.code;
                            var messages = data.data.messages ? data.data.messages : data.data;
                            if ('string' === typeof messages) {
                                showError('<ul class="woocommerce-error" role="alert">' + messages + '</ul>', $('form'));
                            } else {
                                var messageItems = messages.map(function (message) {
                                    return '<li>' + message + '</li>';
                                }).join('');
                                showError('<ul class="woocommerce-error" role="alert">' + messageItems + '</ul>', $('form'));
                            }
                            return null;
                        } else {
                            location.href = data.code;
                        }
                    });
                });
            }

            if (is_from_checkout === true) {
                var content = `<br/>
                    <div data-product_id="" id="fastpay-product-button"
                        class="fastpay-button"
                        name="fp-submit"
                        style="background-color: ${fastpay_button_manager.checkout_button_color_code}; color: ${fastpay_button_manager.checkout_button_font_color_code}; border: 1px solid ${fastpay_button_manager.checkout_button_border_color_code}; border-radius: ${fastpay_button_manager.checkout_button_border_radius}px;">
                        <img style="height:16pt;width: auto; display:inline-block; padding-right: 6px; vertical-align: middle; float: none; margin-left: 0px;" src="${fastpay_button_manager.checkout_lightning_svg_location}" />
                        ${fastpay_button_manager.checkout_button_label}
                    </div>
                `;

                $("#fastpay_checkout").html(content);

                $("#fastpay_checkout").on('click', function () {
                    $('#place_order').trigger('click');
                });
            }

            // if (is_from_cart === true) {
            //     var content = `
            //         <div data-product_id="" id="fastpay-product-button"
            //             class="fastpay-button"
            //             name="fp-submit"
            //             style="background-color: ${fastpay_button_manager.cart_page_button_color_code}; color: ${fastpay_button_manager.cart_page_button_font_color_code}; border: 1px solid ${fastpay_button_manager.cart_page_button_border_color_code}; border-radius: ${fastpay_button_manager.cart_page_button_border_radius}px; margin-bottom: 10px">
            //             <img style="height:16pt;width: auto; display:inline-block; padding-right: 6px; vertical-align: middle; float: none; margin-left: 0px;" src="${fastpay_button_manager.cart_page_lightning_svg_location}" />
            //             ${fastpay_button_manager.cart_page_button_label}
            //         </div>
            //     `;

            //     $("#fastpay_cart").html(content);

            //     $(".fastpay-proceed-to-checkout-button-separator").html(`&mdash; OR &mdash;`);

            //     $("#fastpay_cart").on('click', function () {
            //         if (loading === true)
            //             return true;

            //         loading = true;

            //         var data = {};
            //         return fetch(fastpay_button_manager.fastpay_link_from_checkout_page, {
            //             method: 'POST',
            //             headers: {
            //                 'Content-Type': 'application/x-www-form-urlencoded'
            //             },
            //             body: new URLSearchParams(data).toString(),
            //         }).then(function (res) {
            //             return res.json();
            //         }).then(function (data) {
            //             loading = false;
            //             if (typeof data.code !== 'undefined') {
            //                 location.href = data.code;
            //                 var messages = data.data.messages ? data.data.messages : data.data;
            //                 if ('string' === typeof messages) {
            //                     showError('<ul class="woocommerce-error" role="alert">' + messages + '</ul>', $('form'));
            //                 } else {
            //                     var messageItems = messages.map(function (message) {
            //                         return '<li>' + message + '</li>';
            //                     }).join('');
            //                     showError('<ul class="woocommerce-error" role="alert">' + messageItems + '</ul>', $('form'));
            //                 }
            //                 return null;
            //             } else {
            //                 location.href = data.code;
            //             }
            //         });
            //     });
            // }
        }

        $.fastpay_scroll_to_notices = function () {
            var scrollElement = $('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout');
            if (!scrollElement.length) {
                scrollElement = $('form.checkout');
            }
            if(!scrollElement.length) {
                scrollElement = $('form#order_review');
            }
            if ( scrollElement.length ) {
                $( 'html, body' ).animate( {
                    scrollTop: ( scrollElement.offset().top - 100 )
                }, 1000 );
            }
		};

        var showError = function (error_message) {
            $('.woocommerce').prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>');
            $('.woocommerce').removeClass('processing').unblock();
            $('.woocommerce').find('.input-text, select, input:checkbox').trigger('validate').trigger('blur');
            $.fastpay_scroll_to_notices();
        };

        var hide_show_place_order_button = function () {
            // append payment methods here to prevent hide/show clash with other plugin
            if (is_fastpay_selected() || is_ppcp_selected()) {
                $('#place_order').hide();
            } else {
                $('#place_order').show();
            }
        };

        function is_fastpay_selected() {
            if ($('#payment_method_fastpaynow_by_fave').is(':checked')) {
                return true;
            } else {
                return false;
            }
        }

        function is_ppcp_selected() {
            if ($('#payment_method_wpg_paypal_checkout').is(':checked')) {
                return true;
            } else {
                return false;
            }
        }

        $(document.body).on('updated_checkout updated_cart_totals', function () {
            render_buttons();
            setTimeout(function () {
                hide_show_place_order_button();
            }, 150);
        });

        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            setTimeout(function () {
                hide_show_place_order_button();
            }, 150);
        });

        render_buttons();
    });
});