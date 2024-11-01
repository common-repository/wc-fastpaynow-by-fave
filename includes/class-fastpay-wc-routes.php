<?php
if (!defined('ABSPATH')) exit;

class FastPayNow_By_Fave_WC_Routes {
    use FastPayNow_By_Fave_WC_Logger;

    public function __construct() {
        $routes = [
            [
                "path" => "get_shipping_options",
                'methods'  => 'GET',
                'callback' => 'get_shipping_options'
            ],
            [
                "path" => "update_order",
                'methods'  => 'POST',
                'callback' => 'update_order'
            ],
        ];
        foreach ($routes as $route) {
            add_action('rest_api_init', function () use ($route) {
                register_rest_route('wc/v3', $route["path"], array(
                    'methods'  => $route["methods"],
                    'callback' => $route["callback"]
                ));
            });
        }
    }

    public function get_state_code($country_code, $state_name)
    {
        $states = WC()->countries->get_states($country_code);
        foreach ($states as $key => $value) {
            if (strpos(strtolower($value), strtolower($state_name)) !== false) {
                $state_code = $key;
                break;
            }
        }

        return $state_code;
    }

    public function get_shipping_method($id, $method_id)
    {
        $method_rate_id = $method_id . ":" . $id; // eg. flat_rate:1
        if (empty($method_rate_id)) return false;

        $method_key_id = str_replace(':', '_', $method_rate_id); // Formating
        $option_name = 'woocommerce_' . $method_key_id . '_settings'; // Get the complete option slug
        $option = get_option($option_name, null); // Get the title and return it

        // get title and cost like this: $option['title'], $option['cost']
        return $option;
    }

    // check if an order contains any non-physical item.
    // return true only if the order contains no single physical item.
    public function is_virtual_order($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order == null) return false;

        foreach ($order->get_items() as $order_item) {
            $item = wc_get_product($order_item->get_product_id());
            if (!$item->is_virtual()) {
                return false;
            }
        }
        return true;
    }

    public function get_shipping_options($req)
    {
        $sign = $req->get_param('sign');
        if ($sign == null) {
            return new WP_Error('invalid_sign', 'Sign is required', array('status' => 401));
        }

        $api_key = favepay_wc_get_setting('api_key');
        $params = $req->get_query_params();
        unset($params["sign"]);
        $signature = FastPayNow_By_Fave_Utils::generate_sign($params, $api_key);
        if ($sign !== $signature) {
            return new WP_Error('invalid_sign', 'Invalid sign', array('status' => 401));
        }

        $country_code = strtoupper($req->get_param('country_code'));
        $state = strtoupper($req->get_param('state'));
        $state_code = get_state_code($country_code, $state);
        $postcode = $req->get_param('postcode');
        $order_id = $req->get_param('order_id');
        $methods = [];
        $locations = [];

        // do not return delivery methods to FE if it has no physical order items
        $is_virtual = is_virtual_order($order_id);
        if ($is_virtual) return [];

        $all_zones = WC_Shipping_Zones::get_zones();
        $previous_zone = null;
        $tmp_postcodes = [];
        foreach ($all_zones as $zone) {
            $zone_name = $zone["zone_name"];

            // sort the array to make sure type postcode always comes first
            usort($zone['zone_locations'], function ($a, $b) {
                return strcmp($a->code, $b->code);
            });
            foreach ($zone['zone_locations'] as $location) {
                if ($previous_zone != null && $location->type !== 'postcode') {
                    if ($previous_zone == $zone_name) {
                        foreach ($tmp_postcodes as $code) {
                            $locations["$location->code:$code"] = $zone;
                        }
                    } else {
                        $previous_zone = null;
                        $tmp_postcodes = [];
                    }
                }

                if ($location->type === 'postcode') {
                    $previous_zone = $zone_name;
                    $tmp_postcodes = array_merge($tmp_postcodes, explode(", ", $location->code));
                } else {
                    if ($previous_zone != $zone_name) {
                        $locations[$location->code] = $zone;
                    }
                }
            }
        }

        $wc_contries = new WC_Countries();
        $continent_code = $wc_contries->get_continent_code_for_country($country_code);
        $zone = $locations["$country_code:$state_code:$postcode"] ?? $locations["$country_code:$state_code"] ?? $locations[$country_code];

        if ($zone != null) {
            $shipping_method_ctrl = new WC_REST_Shipping_Zone_Methods_Controller();

            foreach ($zone['shipping_methods'] as $flat_rate) {
                $shipping_method = $shipping_method_ctrl->prepare_item_for_response($flat_rate, $req);
                if (!$shipping_method['enabled']) continue;

                $methods[] = (object) [
                    "id" => $shipping_method["id"],
                    "title" => FastPayNow_By_Fave_Utils::format_shipping_title($shipping_method["title"]),
                    "cost" => FastPayNow_By_Fave_Utils::get_formatted_amount($shipping_method["settings"]["cost"]["value"]),
                    "meta" => (object) [
                        "method_id" => $shipping_method["method_id"],
                    ],
                ];
            }
        }

        // default zone fallback if available
        if (empty($methods)) {
            $default_zone = new WC_Shipping_Zone(0);
            $shipping_methods = $default_zone->get_shipping_methods();
            $shipping_method_ctrl = new WC_REST_Shipping_Zone_Methods_Controller();

            foreach ($shipping_methods as $flat_rate) {
                $shipping_method = $shipping_method_ctrl->prepare_item_for_response($flat_rate, $req);
                if (!$shipping_method['enabled']) continue;

                $methods[] = (object) [
                    "id" => $shipping_method["id"],
                    "title" => FastPayNow_By_Fave_Utils::format_shipping_title($shipping_method["title"]),
                    "cost" => FastPayNow_By_Fave_Utils::get_formatted_amount($shipping_method["settings"]["cost"]["value"]),
                    "meta" => (object) [
                        "method_id" => $shipping_method["method_id"],
                    ],
                ];
            }
        }

        return $methods;
    }

    public function update_order($req)
    {
        $source = get_class($this) . '|' . __FUNCTION__;
        $body = json_decode($req->get_body());
        $this->log( $source, 'Update order' . $req->get_body() );
        $order_id = $body->id;
        $note = $body->note;
        $shipping_address = $body->shipping_address;
        $billing_address = $body->billing_address;
        $shipping_line = $body->shipping_line;
        $method_id = $shipping_line->meta->method_id;
        $sign = $body->sign;

        // sign security
        $api_key = favepay_wc_get_setting('api_key');
        unset($body->sign);
        $signature = FastPayNow_By_Fave_Utils::generate_json_sign($body, $api_key);
        if ($sign !== $signature) {
            return new WP_Error('invalid_sign', 'Invalid sign', array('status' => 401));
        }

        // get order
        $order = wc_update_order(array('order_id' => $order_id));
        if ($order instanceof WP_Error) {
            return new WP_Error('invalid_order', 'Invalid Order', array('status' => 404));
        }

        $order->set_address([
            'first_name' => $billing_address->first_name,
            'last_name'  => $billing_address->last_name,
            'email'      => $billing_address->email,
            'phone'      => $billing_address->phone,
            'address_1'  => $billing_address->address1,
            'address_2'  => $billing_address->address2,
            'city'       => $billing_address->city,
            'state'      => $billing_address->state,
            'postcode'   => $billing_address->postcode,
            'country'    => $billing_address->country,
        ], 'billing');
        $order->set_address([
            'first_name' => $shipping_address->first_name,
            'last_name'  => $shipping_address->last_name,
            'email'      => $shipping_address->email,
            'phone'      => $shipping_address->phone,
            'address_1'  => $shipping_address->address1,
            'address_2'  => $shipping_address->address2,
            'city'       => $shipping_address->city,
            'state'      => $shipping_address->state,
            'postcode'   => $shipping_address->postcode,
            'country'    => $shipping_address->country,
        ], 'shipping');

        // get shipping details by id and method_id (not available for virtual products)
        // local_pickup method will return null instead
        if ($shipping_line != null) {
            $shipping_item = get_shipping_method($shipping_line->id, $method_id);
            if ($shipping_item == null && $method_id != 'local_pickup' && $method_id != 'free_shipping') {
                return new WP_Error('invalid_shipping', 'Invalid Shipping', array('status' => 404));
            }

            // remove existing shipping methods first
            $order_shipping_items = (array) $order->get_shipping_methods();
            foreach ($order_shipping_items as $item_id => $item) {
                $order->remove_item($item_id);
            }

            // add shipping option
            $item = new WC_Order_Item_Shipping();
            $item->set_method_title($shipping_item['title']);
            $item->set_method_id($method_id); // set an existing Shipping method rate ID
            $item->set_total($shipping_item['cost']);
            $order->add_item($item);
        }

        // add customer's note
        $order->add_order_note($note);

        $total = $order->calculate_totals();

        return (object) [
            'total_amount_cents' => round($total * 100),
            'order' => [
                "id"             => $order->get_id(),
                "currency"       => $order->get_currency(),
                "discount_total" => $order->get_discount_total(),
                "shipping_total" => number_format((float)$order->get_shipping_total(), 2, '.', ''),
                "total"          => $order->get_total(),
                "subtotal"       => number_format((float)$order->get_subtotal(), 2, '.', ''),
                "tax_total"      => $order->get_total_tax(),
            ],
        ];
    }
}
new FastPayNow_By_Fave_WC_Routes();