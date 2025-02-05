<?php
if ( !defined( 'ABSPATH' ) ) exit;

function fastpaynow_by_fave_wc_settings_form_fields() {
    if ( FASTPAYNOW_BY_FAVE_WC_STAGING == "yes" ){
        return array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable FastPayNow', 'wc-fastpaynow-by-fave' ),
                'default'     => 'no',
                'id'          => 'fastpay_status'
            ),
            'title' => array(
                'title'       => __( 'Title', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'wc-fastpaynow-by-fave' ),
                'default'     => __( 'Fastpay', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'wc-fastpaynow-by-fave' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'placeholder' => wc_get_description_fpn_by_fave(),
            ),
            'api_credentials' => array(
                'title'       => __( 'FastPay Account Details', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
            ),
            'sandbox' => array(
                'title'       => __( 'Sandbox', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => sprintf( __('Use sandbox. Register as a FastPay merchant through <a href="%s" target="_blank">FastPaynow.com</a>.', 'wc-fastpaynow-by-fave' ), 'https://www.fastpaynow.com' ),
                'description' => __( 'If checked, it will send request to FastPay on sandbox mode.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'no',
            ),
            'staging_url' => array(
                'title'       => __( 'Staging URL', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Staging environment to connect', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'app_id' => array(
                'title'       => __( 'App ID', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Vendor ID issued by FastPay', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'prefix' => array(
                'title'       => __( 'Prefix', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Unique reference string provided by the vendor for the transaction.  The vendor should prepend the prefix issued by FastPay in front of the vendor\'s reference number', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'FWMY',
            ),
            'api_key' => array(
                'title'       => __( 'API Key', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Access FastPay dashboard to obtain your API key.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'outlet_id' => array(
                'title'       => __( 'Outlet ID', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Access FastPay dashboard to obtain your outlet ID.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'cashback_rate' => array(
                'title'       => __( 'Cashback Rate', 'wc-fastpaynow-by-fave' ),
                'type'        => 'number',
                'description' => __( 'This controls the cashback rate in the promo widget which the user sees in product, cart and checkout pages.', 'wc-fastpaynow-by-fave' ),
                'default'     => 15,
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'min' => 0,
                    'max' => 100,
                ),
            ),
            'promotional_messaging' => array(
                'title'       => __( 'Promotional Messaging', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
            ),
            'promotional_messaging_product' => array(
                'title'       => __( 'Product Level Pages', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable product promotional messaging', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Show promotional messaging at product level pages.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            'promotional_messaging_product_grid' => array(
                'title'       => __( 'Product Grid Level Pages', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable product grid promotional messaging', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Show promotional messaging at product grid level pages.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            'promotional_messaging_cart' => array(
                'title'       => __( 'Cart Level Pages', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable cart promotional messaging', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Show promotional messaging at cart level pages.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            //Fastpay Button style for checkout page.
            'checkout_button_design' => array(
                'title'       => __( 'Checkout Page', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
                'description'     => __( 'Options set will be applied to the FastPay button on your checkout page.', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
            ),
            'checkout_button_color' => array(
                'title'    => __( 'Button color', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button color', 'wc-fastpaynow-by-fave' ),
                'id'       => 'checkout_button_color',
                'default'  => 'Violet blue',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'Violet blue'        => __( 'Violet Blue', 'wc-fastpaynow-by-fave' ),
                    'Black'              => __( 'Black', 'wc-fastpaynow-by-fave' ),
                    'White'              => __( 'White', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'checkout_button_shape' => array(
                'title'    => __( 'Button Shape', 'wc-fastpaynow-by-fave' ),
                'id'       => 'checkout_button_shape',
                'default'  => 'Rectangle',
                'type'     => 'select',
                'description'     => __( 'Rectangle(Sharp) or Pill(Rounded)', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
                'options'  => array(
                    'Pill'               => __( 'Pill', 'wc-fastpaynow-by-fave' ),
                    'Rectangle'        => __( 'Rectangle (Recommended)', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'checkout_button_label' => array(
                'title'    => __( 'Button label', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button label', 'wc-fastpaynow-by-fave' ),
                'id'       => 'checkout_button_label',
                'default'  => 'FastPay',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'FastPay'              => __( 'FastPay', 'wc-fastpaynow-by-fave' ),
                    'Checkout'             => __( 'Checkout', 'wc-fastpaynow-by-fave' ),
                    'Buy Now'              => __( 'Buy Now', 'wc-fastpaynow-by-fave' ),
                    'Pay'                  => __( 'Pay', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            //Fastpay Button style for product page.
            'product_page_button_design' => array(
                'title'       => __( 'Product Page', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
                'description'     => __( 'Options set will be applied to the FastPay button on your product page.', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
            ),
            'product_page_button' => array(
                'title'       => __( 'Enable/Disable', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'description'     => __( 'Display FastPay button on product page', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
                'default'     => 'no',
                'id'          => 'product_page_status'
            ),
            'product_page_button_color' => array(
                'title'    => __( 'Button color', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button color', 'wc-fastpaynow-by-fave' ),
                'id'       => 'product_page_button_color',
                'default'  => 'Violet blue',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'Violet blue'        => __( 'Violet Blue', 'wc-fastpaynow-by-fave' ),
                    'Black'              => __( 'Black', 'wc-fastpaynow-by-fave' ),
                    'White'              => __( 'White', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'product_page_button_shape' => array(
                'title'    => __( 'Button Shape', 'wc-fastpaynow-by-fave' ),
                'id'       => 'product_pagebutton_shape',
                'default'  => 'Rectangle',
                'type'     => 'select',
                'description'     => __( 'Rectangle(Sharp) or Pill(Rounded)', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
                'options'  => array(
                    'Pill'               => __( 'Pill', 'wc-fastpaynow-by-fave' ),
                    'Rectangle'        => __( 'Rectangle (Recommended)', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'product_page_button_label' => array(
                'title'    => __( 'Button label', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button label', 'wc-fastpaynow-by-fave' ),
                'id'       => 'product_page_button_label',
                'default'  => 'FastPay',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'FastPay'              => __( 'FastPay', 'wc-fastpaynow-by-fave' ),
                    'Checkout'             => __( 'Checkout', 'wc-fastpaynow-by-fave' ),
                    'Buy Now'              => __( 'Buy Now', 'wc-fastpaynow-by-fave' ),
                    'Pay'                  => __( 'Pay', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            //Fastpay Button style for Cart page.
            // 'cart_page_button_design' => array(
            //     'title'       => __( 'Cart Page', 'wc-fastpaynow-by-fave' ),
            //     'type'        => 'title',
            //     'description'     => __( 'Options set will be applied to the FastPay button on your cart page.', 'wc-fastpaynow-by-fave' ),
            //     'desc_tip' => true,
            // ),
            // 'cart_page_button' => array(
            //     'title'       => __( 'Enable/Disable', 'wc-fastpaynow-by-fave' ),
            //     'type'        => 'checkbox',
            //     'description'     => __( 'Display FastPay button on product page', 'wc-fastpaynow-by-fave' ),
            //     'desc_tip' => true,
            //     'default'     => 'no',
            //     'id'          => 'cart_page_status'
            // ),
            // 'cart_page_button_color' => array(
            //     'title'    => __( 'Button color', 'wc-fastpaynow-by-fave' ),
            //     'description'     => __( 'Choose button color', 'wc-fastpaynow-by-fave' ),
            //     'id'       => 'cart_page_button_color',
            //     'default'  => 'Violet blue',
            //     'type'     => 'select',
            //     'desc_tip' => true,
            //     'options'  => array(
            //         'Violet blue'        => __( 'Violet Blue', 'wc-fastpaynow-by-fave' ),
            //         'Black'              => __( 'Black', 'wc-fastpaynow-by-fave' ),
            //         'White'              => __( 'White', 'wc-fastpaynow-by-fave' ),
            //     ),
            // ),
            // 'cart_page_button_shape' => array(
            //     'title'    => __( 'Button Shape', 'wc-fastpaynow-by-fave' ),
            //     'id'       => 'cart_page_button_shape',
            //     'default'  => 'Rectangle',
            //     'type'     => 'select',
            //     'description'     => __( 'Rectangle(Sharp) or Pill(Rounded)', 'wc-fastpaynow-by-fave' ),
            //     'desc_tip' => true,
            //     'options'  => array(
            //         'Pill'               => __( 'Pill', 'wc-fastpaynow-by-fave' ),
            //         'Rectangle'        => __( 'Rectangle (Recommended)', 'wc-fastpaynow-by-fave' ),
            //     ),
            // ),
            // 'cart_page_button_label' => array(
            //     'title'    => __( 'Button label', 'wc-fastpaynow-by-fave' ),
            //     'description'     => __( 'Choose button label', 'wc-fastpaynow-by-fave' ),
            //     'id'       => 'cart_page_button_label',
            //     'default'  => 'FastPay',
            //     'type'     => 'select',
            //     'desc_tip' => true,
            //     'options'  => array(
            //         'FastPay'              => __( 'FastPay', 'wc-fastpaynow-by-fave' ),
            //         'Checkout'             => __( 'Checkout', 'wc-fastpaynow-by-fave' ),
            //         'Buy Now'              => __( 'Buy Now', 'wc-fastpaynow-by-fave' ),
            //         'Pay'                  => __( 'Pay', 'wc-fastpaynow-by-fave' ),
            //     ),
            // ),
            'debugging' => array(
                'title'       => __( 'Debugging', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
            ),
            'debug' => array(
                'title'       => __( 'Debug Log', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable debug log', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Log FastPay events, eg: IPN requests. Logs can be viewed on WooCommerce > Status > Logs.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'no',
            ),
        );
    }else{
        return array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable FastPayNow', 'wc-fastpaynow-by-fave' ),
                'default'     => 'no',
                'id'          => 'fastpay_status'
            ),
            'title' => array(
                'title'       => __( 'Title', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'wc-fastpaynow-by-fave' ),
                'default'     => __( 'Fastpay', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'wc-fastpaynow-by-fave' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'placeholder' => wc_get_description_fpn_by_fave(),
            ),
            'api_credentials' => array(
                'title'       => __( 'FastPay Account Details', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
            ),
            'sandbox' => array(
                'title'       => __( 'Sandbox', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => sprintf( __('Use sandbox. Register as a FastPay merchant through <a href="%s" target="_blank">FastPaynow.com</a>.', 'wc-fastpaynow-by-fave' ), 'https://www.fastpaynow.com' ),
                'description' => __( 'If checked, it will send request to FastPay on sandbox mode.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'no',
            ),
            'app_id' => array(
                'title'       => __( 'App ID', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Vendor ID issued by FastPay', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'prefix' => array(
                'title'       => __( 'Prefix', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Unique reference string provided by the vendor for the transaction.  The vendor should prepend the prefix issued by FastPay in front of the vendor\'s reference number', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'FWMY',
            ),
            'api_key' => array(
                'title'       => __( 'API Key', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Access FastPay dashboard to obtain your API key.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'outlet_id' => array(
                'title'       => __( 'Outlet ID', 'wc-fastpaynow-by-fave' ),
                'type'        => 'text',
                'description' => __( 'Access FastPay dashboard to obtain your outlet ID.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
            ),
            'cashback_rate' => array(
                'title'       => __( 'Cashback Rate', 'wc-fastpaynow-by-fave' ),
                'type'        => 'number',
                'description' => __( 'This controls the cashback rate in the promo widget which the user sees in product, cart and checkout pages.', 'wc-fastpaynow-by-fave' ),
                'default'     => 15,
                'desc_tip'    => true,
                'custom_attributes' => array(
                    'min' => 0,
                    'max' => 100,
                ),
            ),
            'promotional_messaging' => array(
                'title'       => __( 'Promotional Messaging', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
            ),
            'promotional_messaging_product' => array(
                'title'       => __( 'Product Level Pages', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable product promotional messaging', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Show promotional messaging at product level pages.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            'promotional_messaging_product_grid' => array(
                'title'       => __( 'Product Grid Level Pages', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable product grid promotional messaging', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Show promotional messaging at product grid level pages.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            'promotional_messaging_cart' => array(
                'title'       => __( 'Cart Level Pages', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable cart promotional messaging', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Show promotional messaging at cart level pages.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'yes',
            ),
            //Fastpay Button style for checkout page.
            'checkout_button_design' => array(
                'title'       => __( 'Checkout Page', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
                'description'     => __( 'Options set will be applied to the FastPay button on your checkout page.', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
            ),
            'checkout_button_color' => array(
                'title'    => __( 'Button color', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button color', 'wc-fastpaynow-by-fave' ),
                'id'       => 'checkout_button_color',
                'default'  => 'Violet blue',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'Violet blue'        => __( 'Violet Blue', 'wc-fastpaynow-by-fave' ),
                    'Black'              => __( 'Black', 'wc-fastpaynow-by-fave' ),
                    'White'              => __( 'White', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'checkout_button_shape' => array(
                'title'    => __( 'Button Shape', 'wc-fastpaynow-by-fave' ),
                'id'       => 'checkout_button_shape',
                'default'  => 'Rectangle',
                'type'     => 'select',
                'description'     => __( 'Rectangle(Sharp) or Pill(Rounded)', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
                'options'  => array(
                    'Pill'               => __( 'Pill', 'wc-fastpaynow-by-fave' ),
                    'Rectangle'        => __( 'Rectangle (Recommended)', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'checkout_button_label' => array(
                'title'    => __( 'Button label', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button label', 'wc-fastpaynow-by-fave' ),
                'id'       => 'checkout_button_label',
                'default'  => 'FastPay',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'FastPay'              => __( 'FastPay', 'wc-fastpaynow-by-fave' ),
                    'Checkout'             => __( 'Checkout', 'wc-fastpaynow-by-fave' ),
                    'Buy Now'              => __( 'Buy Now', 'wc-fastpaynow-by-fave' ),
                    'Pay'                  => __( 'Pay', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            //Fastpay Button style for product page.
            'product_page_button_design' => array(
                'title'       => __( 'Product Page', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
                'description'     => __( 'Options set will be applied to the FastPay button on your product page.', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
            ),
            'product_page_button' => array(
                'title'       => __( 'Enable/Disable', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'description'     => __( 'Display FastPay button on product page', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
                'default'     => 'no',
                'id'          => 'product_page_status'
            ),
            'product_page_button_color' => array(
                'title'    => __( 'Button color', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button color', 'wc-fastpaynow-by-fave' ),
                'id'       => 'product_page_button_color',
                'default'  => 'Violet blue',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'Violet blue'        => __( 'Violet Blue', 'wc-fastpaynow-by-fave' ),
                    'Black'              => __( 'Black', 'wc-fastpaynow-by-fave' ),
                    'White'              => __( 'White', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'product_page_button_shape' => array(
                'title'    => __( 'Button Shape', 'wc-fastpaynow-by-fave' ),
                'id'       => 'product_pagebutton_shape',
                'default'  => 'Rectangle',
                'type'     => 'select',
                'description'     => __( 'Rectangle(Sharp) or Pill(Rounded)', 'wc-fastpaynow-by-fave' ),
                'desc_tip' => true,
                'options'  => array(
                    'Pill'               => __( 'Pill', 'wc-fastpaynow-by-fave' ),
                    'Rectangle'        => __( 'Rectangle (Recommended)', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            'product_page_button_label' => array(
                'title'    => __( 'Button label', 'wc-fastpaynow-by-fave' ),
                'description'     => __( 'Choose button label', 'wc-fastpaynow-by-fave' ),
                'id'       => 'product_page_button_label',
                'default'  => 'FastPay',
                'type'     => 'select',
                'desc_tip' => true,
                'options'  => array(
                    'FastPay'              => __( 'FastPay', 'wc-fastpaynow-by-fave' ),
                    'Checkout'             => __( 'Checkout', 'wc-fastpaynow-by-fave' ),
                    'Buy Now'              => __( 'Buy Now', 'wc-fastpaynow-by-fave' ),
                    'Pay'                  => __( 'Pay', 'wc-fastpaynow-by-fave' ),
                ),
            ),
            //Fastpay Button style for Cart page.
            // 'cart_page_button_design' => array(
            //     'title'       => __( 'Cart Page', 'wc-fastpaynow-by-fave' ),
            //     'type'        => 'title',
            //     'description'     => __( 'Options set will be applied to the FastPay button on your cart page.', 'wc-fastpaynow-by-fave' ),
            //     'desc_tip' => true,
            // ),
            // 'cart_page_button' => array(
            //     'title'       => __( 'Enable/Disable', 'wc-fastpaynow-by-fave' ),
            //     'type'        => 'checkbox',
            //     'description'     => __( 'Display FastPay button on product page', 'wc-fastpaynow-by-fave' ),
            //     'desc_tip' => true,
            //     'default'     => 'no',
            //     'id'          => 'cart_page_status'
            // ),
            // 'cart_page_button_color' => array(
            //     'title'    => __( 'Button color', 'wc-fastpaynow-by-fave' ),
            //     'description'     => __( 'Choose button color', 'wc-fastpaynow-by-fave' ),
            //     'id'       => 'cart_page_button_color',
            //     'default'  => 'Violet blue',
            //     'type'     => 'select',
            //     'desc_tip' => true,
            //     'options'  => array(
            //         'Violet blue'        => __( 'Violet Blue', 'wc-fastpaynow-by-fave' ),
            //         'Black'              => __( 'Black', 'wc-fastpaynow-by-fave' ),
            //         'White'              => __( 'White', 'wc-fastpaynow-by-fave' ),
            //     ),
            // ),
            // 'cart_page_button_shape' => array(
            //     'title'    => __( 'Button Shape', 'wc-fastpaynow-by-fave' ),
            //     'id'       => 'cart_page_button_shape',
            //     'default'  => 'Rectangle',
            //     'type'     => 'select',
            //     'description'     => __( 'Rectangle(Sharp) or Pill(Rounded)', 'wc-fastpaynow-by-fave' ),
            //     'desc_tip' => true,
            //     'options'  => array(
            //         'Pill'               => __( 'Pill', 'wc-fastpaynow-by-fave' ),
            //         'Rectangle'        => __( 'Rectangle (Recommended)', 'wc-fastpaynow-by-fave' ),
            //     ),
            // ),
            // 'cart_page_button_label' => array(
            //     'title'    => __( 'Button label', 'wc-fastpaynow-by-fave' ),
            //     'description'     => __( 'Choose button label', 'wc-fastpaynow-by-fave' ),
            //     'id'       => 'cart_page_button_label',
            //     'default'  => 'FastPay',
            //     'type'     => 'select',
            //     'desc_tip' => true,
            //     'options'  => array(
            //         'FastPay'              => __( 'FastPay', 'wc-fastpaynow-by-fave' ),
            //         'Checkout'             => __( 'Checkout', 'wc-fastpaynow-by-fave' ),
            //         'Buy Now'              => __( 'Buy Now', 'wc-fastpaynow-by-fave' ),
            //         'Pay'                  => __( 'Pay', 'wc-fastpaynow-by-fave' ),
            //     ),
            // ),
            'debugging' => array(
                'title'       => __( 'Debugging', 'wc-fastpaynow-by-fave' ),
                'type'        => 'title',
            ),
            'debug' => array(
                'title'       => __( 'Debug Log', 'wc-fastpaynow-by-fave' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable debug log', 'wc-fastpaynow-by-fave' ),
                'description' => __( 'Log FastPay events, eg: IPN requests. Logs can be viewed on WooCommerce > Status > Logs.', 'wc-fastpaynow-by-fave' ),
                'desc_tip'    => true,
                'default'     => 'no',
            ),
        );
    }
}
