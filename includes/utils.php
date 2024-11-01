<?php
if (!defined('ABSPATH')) exit;

class FastPayNow_By_Fave_Utils
{
    public static function generate_sign($params, $private_key)
    {
        ksort($params);
        $encoded_params = http_build_query($params);
        $hash = hash_hmac('sha256', $encoded_params, $private_key);
        return $hash;
    }

    public static function generate_json_sign($payload, $private_key)
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $hash = hash_hmac('sha256', $json, $private_key);
        return $hash;
    }

    public static function get_formatted_amount($amount)
    {
        if ($amount == null) return null;
        return sprintf('%0.2f', (float) $amount);
    }

    public static function format_shipping_title($title)
    {
        return str_replace("amp;", "", $title);
    }
}
