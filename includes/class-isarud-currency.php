<?php
if (!defined('ABSPATH')) exit;

class Isarud_Currency {
    private static $inst = null;
    private $option_key = 'isarud_currency_settings';
    private $rates_key = 'isarud_tcmb_rates';
    private $tcmb_url = 'https://www.tcmb.gov.tr/kurlar/today.xml';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        add_action('isarud_currency_update', [$this, 'cron_update_prices']);
        add_action('wp_ajax_isarud_fetch_rates', [$this, 'ajax_fetch_rates']);
        add_action('wp_ajax_isarud_apply_rates', [$this, 'ajax_apply_rates']);

        $settings = $this->get_settings();
        if ($settings['enabled'] && $settings['auto_update'] && !wp_next_scheduled('isarud_currency_update')) {
            wp_schedule_event(time(), $settings['update_interval'], 'isarud_currency_update');
        }
    }

    public function get_settings() {
        return wp_parse_args(get_option($this->option_key, []), [
            'enabled' => false,
            'base_currency' => 'USD',
            'target_currency' => 'TRY',
            'rate_type' => 'forex_selling',
            'margin_type' => 'percent',
            'margin_value' => 0,
            'auto_update' => false,
            'update_interval' => 'daily',
            'round_to' => 2,
            'last_update' => '',
            'last_rate' => '',
        ]);
    }

    public function save_settings($data) {
        update_option($this->option_key, $data);
    }

    public function fetch_rates() {
        $response = wp_remote_get($this->tcmb_url, ['timeout' => 15, 'sslverify' => false]);
        if (is_wp_error($response)) return ['error' => $response->get_error_message()];

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) return ['error' => __('TCMB yanit vermedi', 'api-isarud')];

        $xml = @simplexml_load_string($body);
        if (!$xml) return ['error' => __('XML ayristirma hatasi', 'api-isarud')];

        $rates = [];
        $date = (string)$xml['Tarih'];

        foreach ($xml->Currency as $currency) {
            $code = (string)$currency['CurrencyCode'];
            $unit = (int)$currency->Unit;
            if (empty($code) || $unit < 1) continue;

            $rates[$code] = [
                'code' => $code,
                'name_tr' => (string)$currency->Isim,
                'name_en' => (string)$currency->CurrencyName,
                'unit' => $unit,
                'forex_buying' => (float)$currency->ForexBuying / $unit,
                'forex_selling' => (float)$currency->ForexSelling / $unit,
                'banknote_buying' => (float)$currency->BanknoteBuying / $unit,
                'banknote_selling' => (float)$currency->BanknoteSelling / $unit,
            ];
        }

        $rates['TRY'] = [
            'code' => 'TRY', 'name_tr' => 'TURK LIRASI', 'name_en' => 'TURKISH LIRA',
            'unit' => 1, 'forex_buying' => 1, 'forex_selling' => 1, 'banknote_buying' => 1, 'banknote_selling' => 1,
        ];

        $cached = ['date' => $date, 'fetched_at' => current_time('mysql'), 'rates' => $rates];
        update_option($this->rates_key, $cached);
        return $cached;
    }

    public function get_cached_rates() {
        return get_option($this->rates_key, []);
    }

    public function calculate_rate($from, $to, $rate_type = 'forex_selling') {
        $cached = $this->get_cached_rates();
        if (empty($cached['rates'])) return false;

        $rates = $cached['rates'];
        if (!isset($rates[$from]) || !isset($rates[$to])) return false;

        $from_rate = $rates[$from][$rate_type] ?: $rates[$from]['forex_selling'];
        $to_rate = $rates[$to][$rate_type] ?: $rates[$to]['forex_selling'];

        if ($to_rate == 0) return false;
        return $from_rate / $to_rate;
    }

    public function apply_to_products() {
        if (!class_exists('WooCommerce')) return ['error' => __('WooCommerce gerekli', 'api-isarud')];

        $settings = $this->get_settings();
        $rate = $this->calculate_rate($settings['base_currency'], $settings['target_currency'], $settings['rate_type']);
        if (!$rate) return ['error' => __('Kur hesaplanamadi', 'api-isarud')];

        if ($settings['margin_type'] === 'percent') {
            $rate *= (1 + $settings['margin_value'] / 100);
        } else {
            $rate += $settings['margin_value'];
        }

        $products = wc_get_products(['limit' => -1, 'status' => 'publish', 'type' => ['simple', 'variable']]);
        $updated = 0;

        foreach ($products as $product) {
            $base_price = $product->get_meta('_isarud_base_price');
            if (empty($base_price)) {
                $base_price = $product->get_regular_price();
                if (empty($base_price)) continue;
                $product->update_meta_data('_isarud_base_price', $base_price);
            }

            $new_price = round((float)$base_price * $rate, (int)$settings['round_to']);
            $product->set_regular_price($new_price);

            $sale_base = $product->get_meta('_isarud_base_sale_price');
            if (!empty($sale_base)) {
                $new_sale = round((float)$sale_base * $rate, (int)$settings['round_to']);
                $product->set_sale_price($new_sale);
            }

            $product->save();
            $updated++;

            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $var_id) {
                    $variation = wc_get_product($var_id);
                    if (!$variation) continue;

                    $var_base = $variation->get_meta('_isarud_base_price');
                    if (empty($var_base)) {
                        $var_base = $variation->get_regular_price();
                        if (empty($var_base)) continue;
                        $variation->update_meta_data('_isarud_base_price', $var_base);
                    }

                    $var_new = round((float)$var_base * $rate, (int)$settings['round_to']);
                    $variation->set_regular_price($var_new);
                    $variation->save();
                }
            }
        }

        $settings['last_update'] = current_time('mysql');
        $settings['last_rate'] = $rate;
        $this->save_settings($settings);

        return ['updated' => $updated, 'rate' => $rate];
    }

    public function cron_update_prices() {
        $this->fetch_rates();
        $this->apply_to_products();
    }

    public function ajax_fetch_rates() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $result = $this->fetch_rates();
        if (isset($result['error'])) wp_send_json_error($result['error']);
        wp_send_json_success($result);
    }

    public function ajax_apply_rates() {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $this->fetch_rates();
        $result = $this->apply_to_products();
        if (isset($result['error'])) wp_send_json_error($result['error']);
        wp_send_json_success($result);
    }
}

Isarud_Currency::instance();
