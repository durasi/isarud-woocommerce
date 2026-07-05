<?php
/**
 * Isarud trial-expired visibility (v6.9).
 *
 * Sunucu, 30 gunluk deneme bitince API'yi 402 {error: trial_expired, upgrade_url}
 * ile keser. Bu sinif TUM wp_remote_* cevaplarini tek noktadan izler (http_response
 * filtresi), 402'yi yakalayip yonetici paneline yukseltme banneri basar ve
 * Isarud'dan gelen ilk basarili cevapta banneri kendiliginden kaldirir.
 */
if (!defined('ABSPATH')) exit;

if (!class_exists('Isarud_Trial_Notice')) {

class Isarud_Trial_Notice
{
    const TRANSIENT = 'isarud_trial_expired';

    public static function init()
    {
        add_filter('http_response', [__CLASS__, 'watch'], 10, 3);
        add_action('admin_notices', [__CLASS__, 'notice']);
    }

    /**
     * Tum HTTP cevaplarindan gecer; yalniz isarud.com/api trafigine bakar.
     */
    public static function watch($response, $args, $url)
    {
        if (!is_string($url) || strpos($url, 'isarud.com/api') === false) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code === 402) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            set_transient(self::TRANSIENT, [
                'message'     => isset($body['message']) ? sanitize_text_field($body['message']) : '',
                'upgrade_url' => isset($body['upgrade_url']) ? esc_url_raw($body['upgrade_url']) : 'https://isarud.com/billing',
            ], WEEK_IN_SECONDS);
        } elseif ($code >= 200 && $code < 300) {
            // Plan yenilendi / trial degil — banner kendiliginden kalkar.
            if (get_transient(self::TRANSIENT)) {
                delete_transient(self::TRANSIENT);
            }
        }

        return $response;
    }

    public static function notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $data = get_transient(self::TRANSIENT);
        if (!$data) {
            return;
        }
        $upgrade = !empty($data['upgrade_url']) ? $data['upgrade_url'] : 'https://isarud.com/billing';
        $server_msg = !empty($data['message']) ? $data['message'] : '';
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php echo esc_html__('Isarud: 30 günlük ücretsiz denemeniz sona erdi.', 'api-isarud'); ?></strong>
                <?php echo esc_html__('Pazaryeri senkronizasyonu, yaptırım taraması ve API özellikleri duraklatıldı. Planınızı yükselterek kaldığınız yerden devam edebilirsiniz.', 'api-isarud'); ?>
                <?php if ($server_msg) : ?>
                    <em>(<?php echo esc_html($server_msg); ?>)</em>
                <?php endif; ?>
            </p>
            <p>
                <a href="<?php echo esc_url($upgrade); ?>" class="button button-primary" target="_blank" rel="noopener">
                    <?php echo esc_html__('Planı Yükselt', 'api-isarud'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

Isarud_Trial_Notice::init();
}
