<?php
/**
 * Isarud E-Fatura / E-Arşiv Modülü
 * GİB e-Arşiv Portal API entegrasyonu
 * mlevent/fatura kütüphanesi mantığıyla WordPress-native HTTP ile çalışır
 *
 * @package Isarud
 * @since 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Isarud_EInvoice {

    /**
     * GİB API endpoints
     */
    const PROD_URL = 'https://earsivportal.efatura.gov.tr';
    const TEST_URL = 'https://earsivportaltest.efatura.gov.tr';
    const DISPATCH_PATH = '/earsiv-services/dispatch';
    const TOKEN_PATH = '/earsiv-services/assos-login';
    const REFERRER_PATH = '/intragiris.html';

    /**
     * Option keys
     */
    const OPTION_KEY = 'isarud_einvoice_settings';
    const INVOICES_TABLE = 'isarud_einvoices';

    /**
     * Token cache
     */
    private $token = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Sipariş tamamlandığında otomatik fatura kes
        add_action('woocommerce_order_status_completed', [$this, 'auto_create_invoice'], 20, 1);

        // Sipariş detay sayfasına metabox ekle
        add_action('add_meta_boxes', [$this, 'add_invoice_metabox']);

        // AJAX handlers
        add_action('wp_ajax_isarud_create_invoice', [$this, 'ajax_create_invoice']);
        add_action('wp_ajax_isarud_sign_invoice', [$this, 'ajax_sign_invoice']);
        add_action('wp_ajax_isarud_download_invoice', [$this, 'ajax_download_invoice']);
        add_action('wp_ajax_isarud_send_invoice_email', [$this, 'ajax_send_invoice_email']);
        add_action('wp_ajax_isarud_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_isarud_cancel_invoice', [$this, 'ajax_cancel_invoice']);

        // DB tablo oluştur
        add_action('admin_init', [$this, 'maybe_create_table']);
    }

    /**
     * Veritabanı tablosu oluştur
     */
    public function maybe_create_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;
        $charset = $wpdb->get_charset_collate();

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            return;
        }

        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            uuid varchar(40) NOT NULL,
            belge_numarasi varchar(50) DEFAULT '',
            fatura_tipi varchar(20) DEFAULT 'SATIS',
            durum varchar(20) DEFAULT 'TASLAK',
            toplam_tutar decimal(12,2) DEFAULT 0,
            kdv_tutar decimal(12,2) DEFAULT 0,
            alici_unvan varchar(255) DEFAULT '',
            alici_vkn varchar(20) DEFAULT '',
            gib_tarih varchar(20) DEFAULT '',
            pdf_url text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY order_id (order_id),
            KEY durum (durum)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Ayarları al
     */
    public function get_settings() {
        $defaults = [
            'enabled'           => 'no',
            'test_mode'         => 'yes',
            'gib_user'          => '',
            'gib_pass'          => '',
            'auto_create'       => 'yes',
            'auto_sign'         => 'no',
            'auto_email'        => 'no',
            'company_name'      => '',
            'company_vkn'       => '',
            'company_tax_office'=> '',
            'company_address'   => '',
            'company_city'      => '',
            'company_district'  => '',
            'company_phone'     => '',
            'company_email'     => '',
            'default_kdv_rate'  => '20',
            'invoice_note'      => '',
            'invoice_prefix'    => 'ISR',
        ];
        $settings = get_option(self::OPTION_KEY, []);
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Ayarları kaydet
     */
    public function save_settings($data) {
        $settings = $this->get_settings();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = sanitize_text_field($value);
            }
        }
        update_option(self::OPTION_KEY, $settings);
        return $settings;
    }

    /**
     * Test modu aktif mi?
     */
    private function is_test_mode() {
        $settings = $this->get_settings();
        return $settings['test_mode'] === 'yes';
    }

    /**
     * GİB API base URL
     */
    private function get_base_url() {
        return $this->is_test_mode() ? self::TEST_URL : self::PROD_URL;
    }

    /**
     * GİB'e giriş yap ve token al
     */
    public function login() {
        $settings = $this->get_settings();

        if ($this->is_test_mode()) {
            $userid = '33333310';
            $password = '1';
        } else {
            $userid = $settings['gib_user'];
            $password = $settings['gib_pass'];
        }

        if (empty($userid) || empty($password)) {
            return new WP_Error('missing_credentials', __('GİB kullanıcı bilgileri eksik.', 'api-isarud'));
        }

        $url = $this->get_base_url() . self::TOKEN_PATH;

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer'      => $this->get_base_url() . self::REFERRER_PATH,
                'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'body' => [
                'assession' => 'an498intdsolmustahsilsinloloikiaboranloloaboranikiikiloloaliloloikaboranlikiaboranlodikiaboranlo',
                'ression'   => 'kulaboraborlanaborikiaboranaboranloaboranlodaborikiaboranlo',
                'userid'    => $userid,
                'session'   => 'fsaborikiaboranababoranabikiaboranikiloaboranikiikaboranhaboranababoranlodikiaboranlo',
                'password'  => $password,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['token'])) {
            return new WP_Error('login_failed', __('GİB giriş başarısız. Kullanıcı bilgilerini kontrol edin.', 'api-isarud'));
        }

        $this->token = $body['token'];
        return $this->token;
    }

    /**
     * GİB API'ye dispatch isteği gönder
     */
    private function dispatch($command, $page_name, $params = []) {
        if (!$this->token) {
            $login = $this->login();
            if (is_wp_error($login)) {
                return $login;
            }
        }

        $url = $this->get_base_url() . self::DISPATCH_PATH;

        $body = [
            'callid'  => wp_generate_uuid4(),
            'token'   => $this->token,
            'cmd'     => $command,
            'pageName'=> $page_name,
            'jp'      => wp_json_encode($params, JSON_UNESCAPED_UNICODE),
        ];

        $response = wp_remote_post($url, [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer'      => $this->get_base_url() . self::REFERRER_PATH,
                'User-Agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['error'])) {
            return new WP_Error('gib_error', $result['error']);
        }

        return isset($result['data']) ? $result['data'] : $result;
    }

    /**
     * Oturumu kapat
     */
    public function logout() {
        if (!$this->token) return;

        $url = $this->get_base_url() . '/earsiv-services/assos-login';
        wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer'      => $this->get_base_url() . self::REFERRER_PATH,
            ],
            'body' => [
                'assession' => 'anaborikiaboranlaboranlo',
                'token'     => $this->token,
            ],
        ]);

        $this->token = null;
    }

    /**
     * WooCommerce siparişinden fatura modeli oluştur
     */
    public function build_invoice_data($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('invalid_order', __('Sipariş bulunamadı.', 'api-isarud'));
        }

        $settings = $this->get_settings();
        $uuid = wp_generate_uuid4();
        $now = current_time('d/m/Y');
        $time = current_time('H:i:s');

        // Alıcı bilgileri
        $billing_company = $order->get_billing_company();
        $billing_first = $order->get_billing_first_name();
        $billing_last = $order->get_billing_last_name();
        $alici_unvan = !empty($billing_company) ? $billing_company : $billing_first . ' ' . $billing_last;

        // Vergi/TC bilgisi (meta'dan veya billing company'den)
        $vkn = $order->get_meta('_billing_vkn');
        if (empty($vkn)) {
            $vkn = $order->get_meta('_billing_tc_no');
        }
        if (empty($vkn)) {
            $vkn = '11111111111'; // Varsayılan (bireysel müşteri)
        }

        $vergi_dairesi = $order->get_meta('_billing_vergi_dairesi');
        if (empty($vergi_dairesi)) {
            $vergi_dairesi = '';
        }

        // Sipariş kalemleri
        $items = [];
        $toplam_kdv = 0;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $qty = $item->get_quantity();
            $line_total = $item->get_total();
            $line_tax = $item->get_total_tax();
            $unit_price = $qty > 0 ? $line_total / $qty : 0;

            // KDV oranı hesapla
            $kdv_rate = $line_total > 0 ? round(($line_tax / $line_total) * 100) : intval($settings['default_kdv_rate']);

            $items[] = [
                'malHizmet'    => $product ? $product->get_name() : $item->get_name(),
                'miktar'       => $qty,
                'birim'        => 'C62', // Adet
                'birimFiyat'   => number_format($unit_price, 2, '.', ''),
                'fiyat'        => number_format($line_total, 2, '.', ''),
                'iskontoOrani' => 0,
                'iskontoTutari' => '0',
                'iskontoNedeni' => '',
                'malHizmetTutari' => number_format($line_total, 2, '.', ''),
                'kdvOrani'     => $kdv_rate,
                'kdvTutari'    => number_format($line_tax, 2, '.', ''),
                'vergininKdvTutari' => '0',
                'ozelMatrahNedeni' => '',
                'ozelMatrahTutari' => 0,
            ];

            $toplam_kdv += $line_tax;
        }

        // Kargo (varsa)
        $shipping_total = floatval($order->get_shipping_total());
        $shipping_tax = floatval($order->get_shipping_tax());
        if ($shipping_total > 0) {
            $shipping_kdv_rate = $shipping_total > 0 ? round(($shipping_tax / $shipping_total) * 100) : intval($settings['default_kdv_rate']);
            $items[] = [
                'malHizmet'    => __('Kargo', 'api-isarud'),
                'miktar'       => 1,
                'birim'        => 'C62',
                'birimFiyat'   => number_format($shipping_total, 2, '.', ''),
                'fiyat'        => number_format($shipping_total, 2, '.', ''),
                'iskontoOrani' => 0,
                'iskontoTutari' => '0',
                'iskontoNedeni' => '',
                'malHizmetTutari' => number_format($shipping_total, 2, '.', ''),
                'kdvOrani'     => $shipping_kdv_rate,
                'kdvTutari'    => number_format($shipping_tax, 2, '.', ''),
                'vergininKdvTutari' => '0',
                'ozelMatrahNedeni' => '',
                'ozelMatrahTutari' => 0,
            ];
            $toplam_kdv += $shipping_tax;
        }

        $toplam = floatval($order->get_total());

        // GİB fatura modeli
        $invoice_data = [
            'faturaUuid'          => $uuid,
            'belgeNumarasi'       => '',
            'faturaTarihi'        => $now,
            'saat'                => $time,
            'paraBirimi'          => $order->get_currency(),
            'dovizKuru'           => '0',
            'faturaTipi'          => 'SATIS',
            'hangiTip'            => '5000/30000',
            'vpiKdvOrani'         => '0',
            'iadeTable'           => [],
            'ozelMatrahTutari'    => 0,
            'ozelMatrahOrani'     => 0,
            'ozelMatrahVergiTutari'=> 0,
            'vergiCesidi'         => ' ',
            'malHizmetTable'      => $items,
            'tip'                 => 'İsk662',
            'matrah'              => number_format($toplam - $toplam_kdv, 2, '.', ''),
            'mpiTutari'           => number_format($toplam, 2, '.', ''),
            'hesapilaganKdv'      => number_format($toplam_kdv, 2, '.', ''),
            'vergilerToplami'     => number_format($toplam_kdv, 2, '.', ''),
            'vergilerDahilToplamTutar' => number_format($toplam, 2, '.', ''),
            'toppiamOdenecekTutar'=> number_format($toplam, 2, '.', ''),
            'not'                 => !empty($settings['invoice_note'])
                                     ? $settings['invoice_note']
                                     : sprintf(__('Sipariş No: #%s', 'api-isarud'), $order->get_order_number()),
            'siparisNumarasi'     => $order->get_order_number(),
            'siparisTarihi'       => '',
            'irsaliyeNumarasi'    => '',
            'irsaliyeTarihi'      => '',
            'fisNo'               => '',
            'fisTarihi'           => '',
            'fisSaati'            => '',
            'fisTipi'             => ' ',
            'zRaporNo'            => '',
            'okcSeriNo'           => '',

            // Alıcı bilgileri
            'aliciAdi'            => $billing_first,
            'aliciSoyadi'         => $billing_last,
            'aliciUnvan'          => $billing_company,
            'vknTckn'             => $vkn,
            'aliciVergiDairesi'   => $vergi_dairesi,
            'bulvarcaddesokak'    => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
            'mahpialleMevkii'     => '',
            'binaAdi'             => '',
            'binaNo'              => '',
            'kapiNo'              => '',
            'kasabaKoy'           => '',
            'ilce'                => $order->get_billing_state(),
            'sehir'               => $order->get_billing_city(),
            'postaKodu'           => $order->get_billing_postcode(),
            'ulke'                => $order->get_billing_country(),
            'tel'                 => $order->get_billing_phone(),
            'fax'                 => '',
            'eppiosta'            => $order->get_billing_email(),
            'websitesi'           => '',
        ];

        return [
            'uuid'         => $uuid,
            'invoice_data' => $invoice_data,
            'toplam'       => $toplam,
            'toplam_kdv'   => $toplam_kdv,
            'alici_unvan'  => $alici_unvan,
            'vkn'          => $vkn,
            'tarih'        => $now,
        ];
    }

    /**
     * Fatura oluştur (taslak olarak GİB'e gönder)
     */
    public function create_invoice($order_id) {
        $build = $this->build_invoice_data($order_id);
        if (is_wp_error($build)) {
            return $build;
        }

        $result = $this->dispatch(
            'EARSIV_PORTAL_FATURA_OLUSTUR',
            'RG_BASITFATURA',
            $build['invoice_data']
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Veritabanına kaydet
        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;

        $wpdb->insert($table, [
            'order_id'        => $order_id,
            'uuid'            => $build['uuid'],
            'belge_numarasi'  => '',
            'fatura_tipi'     => 'SATIS',
            'durum'           => 'TASLAK',
            'toplam_tutar'    => $build['toplam'],
            'kdv_tutar'       => $build['toplam_kdv'],
            'alici_unvan'     => $build['alici_unvan'],
            'alici_vkn'       => $build['vkn'],
            'gib_tarih'       => $build['tarih'],
        ]);

        // Sipariş meta'sına kaydet
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_meta_data('_isarud_einvoice_uuid', $build['uuid']);
            $order->update_meta_data('_isarud_einvoice_status', 'TASLAK');
            $order->save();
        }

        $this->add_order_note($order_id, sprintf(
            __('E-Arşiv fatura taslağı oluşturuldu. UUID: %s', 'api-isarud'),
            $build['uuid']
        ));

        return [
            'success' => true,
            'uuid'    => $build['uuid'],
            'message' => __('Fatura taslağı GİB portalda oluşturuldu.', 'api-isarud'),
        ];
    }

    /**
     * Faturayı imzala
     */
    public function sign_invoice($uuid) {
        $result = $this->dispatch(
            'EARSIV_PORTAL_FATURA_HSM_CIHAZI_ILE_IMZALA',
            'RG_IMZALA',
            ['impipiozlpipipiananEntegreEt662' => [
                ['belgeNumarasi' => '', 'faturaUuid' => $uuid]
            ]]
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Durumu güncelle
        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;
        $wpdb->update($table, ['durum' => 'IMZALANDI'], ['uuid' => $uuid]);

        // Sipariş meta
        $row = $wpdb->get_row($wpdb->prepare("SELECT order_id FROM $table WHERE uuid = %s", $uuid));
        if ($row) {
            $order = wc_get_order($row->order_id);
            if ($order) {
                $order->update_meta_data('_isarud_einvoice_status', 'IMZALANDI');
                $order->save();
            }
            $this->add_order_note($row->order_id, sprintf(
                __('E-Arşiv fatura imzalandı. UUID: %s', 'api-isarud'),
                $uuid
            ));
        }

        return [
            'success' => true,
            'message' => __('Fatura başarıyla imzalandı.', 'api-isarud'),
        ];
    }

    /**
     * PDF indirme URL'si al
     */
    public function get_download_url($uuid) {
        $token = $this->token;
        if (!$token) {
            $login = $this->login();
            if (is_wp_error($login)) {
                return $login;
            }
            $token = $this->token;
        }

        $url = $this->get_base_url() . '/earsiv-services/download';
        $url = add_query_arg([
            'token' => $token,
            'ettn'  => $uuid,
            'belgeTip' => 'FATURA',
            'onpipiizlpipiampipipiazpipi' => 'RG_BASITFATURA',
            'cmd'   => 'downloadResource',
        ], $url);

        return $url;
    }

    /**
     * Fatura HTML çıktısı al
     */
    public function get_invoice_html($uuid) {
        $result = $this->dispatch(
            'EARSIV_PORTAL_FATURA_GOSTER',
            'RG_BASITFATURA',
            [
                'ettn'     => $uuid,
                'onpipiizlpipiampipipiazpipi' => 'RG_BASITFATURA',
            ]
        );

        return $result;
    }

    /**
     * Faturayı iptal et
     */
    public function cancel_invoice($uuid, $explanation = '') {
        if (empty($explanation)) {
            $explanation = __('Fatura iptal edildi.', 'api-isarud');
        }

        $result = $this->dispatch(
            'EARSIV_PORTAL_IPTAL_TALEBI_OLUSTUR',
            'RG_BASITFATURA',
            [
                'faturaUuid'  => $uuid,
                'acpipiiklpipiama' => $explanation,
            ]
        );

        if (is_wp_error($result)) {
            return $result;
        }

        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;
        $wpdb->update($table, ['durum' => 'IPTAL'], ['uuid' => $uuid]);

        $row = $wpdb->get_row($wpdb->prepare("SELECT order_id FROM $table WHERE uuid = %s", $uuid));
        if ($row) {
            $order = wc_get_order($row->order_id);
            if ($order) {
                $order->update_meta_data('_isarud_einvoice_status', 'IPTAL');
                $order->save();
            }
            $this->add_order_note($row->order_id, sprintf(
                __('E-Arşiv fatura iptal edildi. UUID: %s', 'api-isarud'),
                $uuid
            ));
        }

        return ['success' => true, 'message' => __('Fatura iptal talebi oluşturuldu.', 'api-isarud')];
    }

    /**
     * Fatura e-posta gönder
     */
    public function send_invoice_email($order_id, $uuid = '') {
        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_Error('invalid_order', __('Sipariş bulunamadı.', 'api-isarud'));
        }

        if (empty($uuid)) {
            $uuid = $order->get_meta('_isarud_einvoice_uuid');
        }

        if (empty($uuid)) {
            return new WP_Error('no_invoice', __('Bu siparişe ait fatura bulunamadı.', 'api-isarud'));
        }

        $download_url = $this->get_download_url($uuid);
        if (is_wp_error($download_url)) {
            return $download_url;
        }

        $to = $order->get_billing_email();
        $subject = sprintf(__('E-Arşiv Faturanız — Sipariş #%s', 'api-isarud'), $order->get_order_number());

        $settings = $this->get_settings();
        $company = !empty($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');

        $body = sprintf(
            __("Sayın %s,\n\nSipariş numaranız #%s olan alışverişinize ait e-Arşiv faturanız hazırlanmıştır.\n\nFatura UUID: %s\n\nFaturanızı GİB e-Arşiv Portal üzerinden görüntüleyebilirsiniz.\n\nSaygılarımızla,\n%s", 'api-isarud'),
            $order->get_formatted_billing_full_name(),
            $order->get_order_number(),
            $uuid,
            $company
        );

        $sent = wp_mail($to, $subject, $body);

        if ($sent) {
            $this->add_order_note($order_id, sprintf(
                __('E-Arşiv fatura e-posta gönderildi: %s', 'api-isarud'),
                $to
            ));
        }

        return $sent
            ? ['success' => true, 'message' => __('Fatura e-postası gönderildi.', 'api-isarud')]
            : new WP_Error('email_failed', __('E-posta gönderilemedi.', 'api-isarud'));
    }

    /**
     * Sipariş tamamlandığında otomatik fatura oluştur
     */
    public function auto_create_invoice($order_id) {
        $settings = $this->get_settings();

        if ($settings['enabled'] !== 'yes' || $settings['auto_create'] !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) return;

        // Zaten fatura varsa atla
        $existing_uuid = $order->get_meta('_isarud_einvoice_uuid');
        if (!empty($existing_uuid)) return;

        $result = $this->create_invoice($order_id);

        if (!is_wp_error($result) && $result['success']) {
            // Otomatik imzala
            if ($settings['auto_sign'] === 'yes') {
                $this->sign_invoice($result['uuid']);
            }

            // Otomatik e-posta
            if ($settings['auto_email'] === 'yes') {
                $this->send_invoice_email($order_id, $result['uuid']);
            }
        }

        $this->logout();
    }

    /**
     * Sipariş notuna ekle
     */
    private function add_order_note($order_id, $note) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->add_order_note($note);
        }
    }

    /**
     * Sipariş sayfasına E-Fatura metabox ekle
     */
    public function add_invoice_metabox() {
        $screen = class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'isarud-einvoice-metabox',
            '🧾 ' . __('E-Arşiv Fatura', 'api-isarud'),
            [$this, 'render_metabox'],
            $screen,
            'side',
            'high'
        );
    }

    /**
     * Metabox içeriği
     */
    public function render_metabox($post_or_order) {
        $order = ($post_or_order instanceof WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
        if (!$order) return;

        $order_id = $order->get_id();
        $uuid = $order->get_meta('_isarud_einvoice_uuid');
        $status = $order->get_meta('_isarud_einvoice_status');
        $settings = $this->get_settings();

        if ($settings['enabled'] !== 'yes') {
            echo '<p style="color:#999;">' . esc_html__('E-Fatura modülü devre dışı. Isarud → E-Fatura ayarlarından aktifleştirin.', 'api-isarud') . '</p>';
            return;
        }

        wp_nonce_field('isarud_einvoice_nonce', 'isarud_einvoice_nonce');

        echo '<div class="isarud-einvoice-metabox">';

        if (empty($uuid)) {
            // Fatura yok, oluşturma butonu göster
            echo '<p>' . esc_html__('Bu sipariş için henüz e-fatura oluşturulmadı.', 'api-isarud') . '</p>';
            echo '<button type="button" class="button button-primary isarud-einvoice-action" data-action="create" data-order="' . esc_attr($order_id) . '" style="width:100%;margin-bottom:5px;">';
            echo '📄 ' . esc_html__('Fatura Oluştur', 'api-isarud');
            echo '</button>';
        } else {
            // Fatura var, durum göster
            $status_labels = [
                'TASLAK'    => ['🟡', __('Taslak', 'api-isarud')],
                'IMZALANDI' => ['🟢', __('İmzalandı', 'api-isarud')],
                'IPTAL'     => ['🔴', __('İptal', 'api-isarud')],
            ];
            $label = isset($status_labels[$status]) ? $status_labels[$status] : ['⚪', $status];

            echo '<div style="background:#f8f9fa;padding:10px;border-radius:6px;margin-bottom:10px;">';
            echo '<strong>' . esc_html__('Durum:', 'api-isarud') . '</strong> ' . $label[0] . ' ' . esc_html($label[1]) . '<br>';
            echo '<strong>UUID:</strong> <code style="font-size:10px;word-break:break-all;">' . esc_html($uuid) . '</code>';
            echo '</div>';

            if ($status === 'TASLAK') {
                echo '<button type="button" class="button button-primary isarud-einvoice-action" data-action="sign" data-order="' . esc_attr($order_id) . '" data-uuid="' . esc_attr($uuid) . '" style="width:100%;margin-bottom:5px;">';
                echo '✍️ ' . esc_html__('İmzala', 'api-isarud');
                echo '</button>';
            }

            if ($status === 'IMZALANDI') {
                echo '<button type="button" class="button isarud-einvoice-action" data-action="download" data-order="' . esc_attr($order_id) . '" data-uuid="' . esc_attr($uuid) . '" style="width:100%;margin-bottom:5px;">';
                echo '📥 ' . esc_html__('PDF İndir', 'api-isarud');
                echo '</button>';
            }

            echo '<button type="button" class="button isarud-einvoice-action" data-action="email" data-order="' . esc_attr($order_id) . '" data-uuid="' . esc_attr($uuid) . '" style="width:100%;margin-bottom:5px;">';
            echo '📧 ' . esc_html__('E-posta Gönder', 'api-isarud');
            echo '</button>';

            if ($status !== 'IPTAL') {
                echo '<button type="button" class="button isarud-einvoice-action" data-action="cancel" data-order="' . esc_attr($order_id) . '" data-uuid="' . esc_attr($uuid) . '" style="width:100%;margin-bottom:5px;color:#d63638;">';
                echo '❌ ' . esc_html__('İptal Et', 'api-isarud');
                echo '</button>';
            }
        }

        if ($this->is_test_mode()) {
            echo '<p style="color:#dba617;font-size:11px;margin-top:8px;">⚠️ ' . esc_html__('Test modu aktif', 'api-isarud') . '</p>';
        }

        echo '</div>';

        // Inline JS for AJAX
        ?>
        <script>
        jQuery(function($) {
            $('.isarud-einvoice-action').on('click', function() {
                var btn = $(this);
                var action = btn.data('action');
                var orderId = btn.data('order');
                var uuid = btn.data('uuid') || '';

                if (action === 'cancel' && !confirm('<?php echo esc_js(__('Faturayı iptal etmek istediğinize emin misiniz?', 'api-isarud')); ?>')) {
                    return;
                }

                btn.prop('disabled', true).text('<?php echo esc_js(__('İşleniyor...', 'api-isarud')); ?>');

                $.post(ajaxurl, {
                    action: 'isarud_' + action + '_invoice',
                    order_id: orderId,
                    uuid: uuid,
                    nonce: $('#isarud_einvoice_nonce').val()
                }, function(resp) {
                    if (resp.success) {
                        alert(resp.data.message || '<?php echo esc_js(__('İşlem başarılı.', 'api-isarud')); ?>');
                        location.reload();
                    } else {
                        alert(resp.data || '<?php echo esc_js(__('Bir hata oluştu.', 'api-isarud')); ?>');
                        btn.prop('disabled', false);
                    }
                }).fail(function() {
                    alert('<?php echo esc_js(__('Bağlantı hatası.', 'api-isarud')); ?>');
                    btn.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Siparişe ait fatura kayıtlarını getir
     */
    public function get_invoices_for_order($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE order_id = %d ORDER BY created_at DESC", $order_id));
    }

    /**
     * Tüm faturaları listele (admin sayfası için)
     */
    public function get_all_invoices($page = 1, $per_page = 20, $status = '', $search = '') {
        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;
        $where = '1=1';
        $params = [];

        if (!empty($status)) {
            $where .= ' AND durum = %s';
            $params[] = $status;
        }

        if (!empty($search)) {
            $where .= ' AND (alici_unvan LIKE %s OR uuid LIKE %s OR order_id = %d)';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = intval($search);
        }

        $offset = ($page - 1) * $per_page;

        $count_query = "SELECT COUNT(*) FROM $table WHERE $where";
        $total = !empty($params) ? $wpdb->get_var($wpdb->prepare($count_query, ...$params)) : $wpdb->get_var($count_query);

        $query = "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($query, ...$params));

        return [
            'items'    => $items,
            'total'    => intval($total),
            'pages'    => ceil($total / $per_page),
            'current'  => $page,
        ];
    }

    /**
     * İstatistikler
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . self::INVOICES_TABLE;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $taslak = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE durum = 'TASLAK'");
        $imzali = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE durum = 'IMZALANDI'");
        $iptal = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE durum = 'IPTAL'");
        $toplam_ciro = $wpdb->get_var("SELECT SUM(toplam_tutar) FROM $table WHERE durum != 'IPTAL'");

        return [
            'total'       => intval($total),
            'taslak'      => intval($taslak),
            'imzali'      => intval($imzali),
            'iptal'       => intval($iptal),
            'toplam_ciro' => floatval($toplam_ciro),
        ];
    }

    // ==========================================
    // AJAX Handlers
    // ==========================================

    public function ajax_create_invoice() {
        check_ajax_referer('isarud_einvoice_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(__('Yetki yok.', 'api-isarud'));

        $order_id = intval($_POST['order_id']);
        $result = $this->create_invoice($order_id);
        $this->logout();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success($result);
    }

    public function ajax_sign_invoice() {
        check_ajax_referer('isarud_einvoice_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(__('Yetki yok.', 'api-isarud'));

        $uuid = sanitize_text_field($_POST['uuid']);
        $result = $this->sign_invoice($uuid);
        $this->logout();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success($result);
    }

    public function ajax_download_invoice() {
        check_ajax_referer('isarud_einvoice_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(__('Yetki yok.', 'api-isarud'));

        $uuid = sanitize_text_field($_POST['uuid']);
        $url = $this->get_download_url($uuid);

        if (is_wp_error($url)) {
            wp_send_json_error($url->get_error_message());
        }

        wp_send_json_success(['url' => $url, 'message' => __('PDF indirme bağlantısı hazır.', 'api-isarud')]);
    }

    public function ajax_send_invoice_email() {
        check_ajax_referer('isarud_einvoice_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(__('Yetki yok.', 'api-isarud'));

        $order_id = intval($_POST['order_id']);
        $uuid = sanitize_text_field($_POST['uuid']);
        $result = $this->send_invoice_email($order_id, $uuid);
        $this->logout();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success($result);
    }

    public function ajax_test_connection() {
        check_ajax_referer('isarud_einvoice_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(__('Yetki yok.', 'api-isarud'));

        $result = $this->login();
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $this->logout();
        wp_send_json_success(['message' => __('GİB bağlantısı başarılı!', 'api-isarud')]);
    }

    public function ajax_cancel_invoice() {
        check_ajax_referer('isarud_einvoice_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(__('Yetki yok.', 'api-isarud'));

        $uuid = sanitize_text_field($_POST['uuid']);
        $result = $this->cancel_invoice($uuid);
        $this->logout();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success($result);
    }
}
