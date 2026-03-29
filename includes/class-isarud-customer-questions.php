<?php
if (!defined('ABSPATH')) exit;

class Isarud_Customer_Questions {
    private static ?self $instance = null;
    public static function instance(): self { if (!self::$instance) self::$instance = new self(); return self::$instance; }

    public function __construct() {
        add_action('wp_ajax_isarud_fetch_questions', [$this, 'ajax_fetch_questions']);
        add_action('wp_ajax_isarud_answer_question', [$this, 'ajax_answer_question']);
    }

    /**
     * Fetch customer questions from Trendyol
     */
    public function fetch_trendyol_questions(int $page = 0, string $status = ''): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        if (!$seller_id) return ['error' => 'Trendyol not configured'];
        $url = "suppliers/{$seller_id}/questions/filter?page={$page}&size=50";
        if ($status) $url .= "&status={$status}";
        $result = $this->mp_request('trendyol', $url);
        if (isset($result['error'])) return $result;
        $questions = [];
        foreach ($result['content'] ?? $result['data'] ?? [] as $q) {
            $questions[] = [
                'id' => $q['id'] ?? '',
                'product_name' => $q['productName'] ?? '',
                'question' => $q['text'] ?? $q['question'] ?? '',
                'customer' => $q['customerName'] ?? ($q['customerFirstName'] ?? ''),
                'answer' => $q['answer'] ?? null,
                'status' => $q['status'] ?? '',
                'created_at' => isset($q['creationDate']) ? date('Y-m-d H:i', $q['creationDate'] / 1000) : '',
                'marketplace' => 'trendyol',
            ];
        }
        return ['success' => true, 'questions' => $questions, 'total' => $result['totalElements'] ?? count($questions)];
    }

    /**
     * Answer a Trendyol question
     */
    public function answer_trendyol_question(string $question_id, string $answer): array {
        $seller_id = $this->get_cred('trendyol', 'seller_id');
        return $this->mp_request('trendyol', "suppliers/{$seller_id}/questions/{$question_id}/answers", 'POST', ['text' => $answer]);
    }

    public function ajax_fetch_questions(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $mp = sanitize_text_field($_POST['marketplace'] ?? 'trendyol');
        $page = intval($_POST['page'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $result = match($mp) {
            'trendyol' => $this->fetch_trendyol_questions($page, $status),
            default => ['error' => 'Only Trendyol supports customer questions API'],
        };
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    public function ajax_answer_question(): void {
        check_ajax_referer('isarud_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $mp = sanitize_text_field($_POST['marketplace'] ?? '');
        $qid = sanitize_text_field($_POST['question_id'] ?? '');
        $answer = sanitize_textarea_field($_POST['answer'] ?? '');
        if (!$qid || !$answer) wp_send_json_error('Missing data');
        $result = match($mp) {
            'trendyol' => $this->answer_trendyol_question($qid, $answer),
            default => ['error' => 'Not supported'],
        };
        isset($result['error']) ? wp_send_json_error($result) : wp_send_json_success($result);
    }

    private function mp_request(string $mp, string $ep, string $m = 'GET', $d = null): array {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'marketplace_request'); $r->setAccessible(true); return $r->invoke($p, $mp, $ep, $m, $d);
    }
    private function get_cred(string $mp, string $k): string {
        $p = Isarud_Plugin::instance(); $r = new \ReflectionMethod($p, 'get_cred'); $r->setAccessible(true); return $r->invoke($p, $mp, $k);
    }
}
