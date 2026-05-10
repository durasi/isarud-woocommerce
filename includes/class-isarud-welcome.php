<?php
if (!defined('ABSPATH')) exit;

class Isarud_Welcome {
    private static $inst = null;
    private $option_key = 'isarud_welcome_dismissed';

    public static function instance() {
        if (!self::$inst) self::$inst = new self();
        return self::$inst;
    }

    public function __construct() {
        add_action('admin_init', [$this, 'maybe_redirect']);
        add_action('wp_ajax_isarud_dismiss_welcome', [$this, 'ajax_dismiss']);
        add_action('wp_ajax_isarud_restart_welcome', [$this, 'ajax_restart']);
    }

    public function maybe_redirect() {
        if (get_transient('isarud_activation_redirect')) {
            delete_transient('isarud_activation_redirect');
            if (!is_network_admin() && !isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('admin.php?page=isarud-welcome'));
                exit;
            }
        }
    }

    public function is_dismissed() {
        return get_option($this->option_key, false);
    }

    public function ajax_dismiss() {
        check_ajax_referer('isarud_nonce', 'nonce');
        update_option($this->option_key, true);
        wp_send_json_success();
    }

    public function ajax_restart() {
        check_ajax_referer('isarud_nonce', 'nonce');
        delete_option($this->option_key);
        wp_send_json_success();
    }

    public static function on_activate() {
        set_transient('isarud_activation_redirect', true, 30);
    }
}

Isarud_Welcome::instance();
