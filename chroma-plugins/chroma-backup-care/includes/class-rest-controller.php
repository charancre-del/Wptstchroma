<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_REST_Controller
{
    const NAMESPACE_NAME = 'chroma-backup-care/v1';

    private $config;
    private $service;
    private $rate_limiter;
    private $parent_access;

    public function __construct(
        Chroma_Backup_Care_Config $config,
        Chroma_Backup_Care_Service $service,
        $rate_limiter = null,
        $parent_access = null
    )
    {
        $this->config = $config;
        $this->service = $service;
        $this->rate_limiter = $rate_limiter ?: new Chroma_Backup_Care_Store();
        $this->parent_access = $parent_access ?: new Chroma_Backup_Care_Parent_Access($config);
    }

    public function register_routes()
    {
        register_rest_route(self::NAMESPACE_NAME, '/config', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_config'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route(self::NAMESPACE_NAME, '/quote', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_quote'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/parent-access/request', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'request_parent_access'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/parent-access/verify', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'verify_parent_access'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/parent-profiles', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'parent_profiles'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/checkout', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_checkout'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/manage', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'manage_order'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/cancel', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'cancel_units'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/reschedule', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'reschedule_unit'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
        register_rest_route(self::NAMESPACE_NAME, '/payment-status', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'payment_status'),
            'permission_callback' => array($this, 'check_public_request'),
        ));
    }

    public function get_config()
    {
        $settings = $this->config->public_settings();
        $readiness = $this->config->readiness();
        $settings['checkoutEnabled'] = $readiness['ready'];
        $settings['mode'] = $readiness['mode'];
        $settings['nonce'] = wp_create_nonce('chroma_backup_care_public');
        $response = new WP_REST_Response($settings, 200);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        return $response;
    }

    public function create_quote(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('quote', 30, 10 * MINUTE_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before trying again.', array('status' => 429));
        }
        $order = $this->order_from_request($request);
        if (is_wp_error($order)) {
            return $order;
        }
        try {
            $result = $this->service->quote($order);
            return new WP_REST_Response($result, $result['contract_valid'] ? 200 : 422);
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function request_parent_access(WP_REST_Request $request)
    {
        if (strlen($request->get_body()) > 2048) {
            return new WP_Error('request_too_large', 'The verification request is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        $email = is_array($parameters) && isset($parameters['email'])
            ? strtolower(trim((string) $parameters['email']))
            : '';
        $email_bucket = 'parent_access_email|' . hash_hmac(
            'sha256',
            $email,
            $this->config->quote_signing_key()
        );
        if (!$this->consume_rate_limit('parent_access_request', 5, HOUR_IN_SECONDS)
            || !$this->consume_rate_limit($email_bucket, 3, HOUR_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before requesting another code.', array('status' => 429));
        }
        try {
            return rest_ensure_response($this->parent_access->request_code($email));
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function verify_parent_access(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('parent_access_verify', 10, 15 * MINUTE_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before trying another code.', array('status' => 429));
        }
        if (strlen($request->get_body()) > 2048) {
            return new WP_Error('request_too_large', 'The verification request is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        try {
            return rest_ensure_response($this->parent_access->verify_code(
                is_array($parameters) && isset($parameters['challenge_id']) ? $parameters['challenge_id'] : '',
                is_array($parameters) && isset($parameters['email']) ? $parameters['email'] : '',
                is_array($parameters) && isset($parameters['code']) ? $parameters['code'] : ''
            ));
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function create_checkout(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('checkout', 10, 15 * MINUTE_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before trying again.', array('status' => 429));
        }
        $parameters = $request->get_json_params();
        if (!is_array($parameters) || !isset($parameters['order']) || !is_array($parameters['order'])) {
            return new WP_Error('invalid_order', 'A valid order is required.', array('status' => 400));
        }
        if (strlen($request->get_body()) > 65536) {
            return new WP_Error('request_too_large', 'The order is too large.', array('status' => 413));
        }
        try {
            $result = $this->service->checkout(
                $parameters['order'],
                isset($parameters['quote_token']) ? (string) $parameters['quote_token'] : ''
            );
            return new WP_REST_Response($result, 201);
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function parent_profiles(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('parent_profiles', 20, 15 * MINUTE_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before refreshing saved children.', array('status' => 429));
        }
        if (strlen($request->get_body()) > 4096) {
            return new WP_Error('request_too_large', 'The saved-child request is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        try {
            return rest_ensure_response($this->service->parent_profiles(
                is_array($parameters) && isset($parameters['email']) ? $parameters['email'] : '',
                is_array($parameters) && isset($parameters['parent_access_token'])
                    ? $parameters['parent_access_token']
                    : ''
            ));
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function payment_status(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('payment_status', 30, 15 * MINUTE_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before checking payment again.', array('status' => 429));
        }
        $parameters = $request->get_json_params();
        $request_id = is_array($parameters) && isset($parameters['request_id'])
            ? sanitize_text_field((string) $parameters['request_id'])
            : '';
        $email = is_array($parameters) && isset($parameters['email'])
            ? strtolower(trim((string) $parameters['email']))
            : '';
        $token = is_array($parameters) && isset($parameters['parent_access_token'])
            ? (string) $parameters['parent_access_token']
            : '';
        try {
            $this->parent_access->assert_token($token, $email);
            $result = $this->service->sync_invoice_payment($request_id, $email);
            return new WP_REST_Response($result, 200);
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function manage_order(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('manage', 60, HOUR_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before trying again.', array('status' => 429));
        }
        if (strlen($request->get_body()) > 16384) {
            return new WP_Error('request_too_large', 'The management request is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        try {
            return rest_ensure_response($this->service->manage_order(
                is_array($parameters) && isset($parameters['manage_token']) ? $parameters['manage_token'] : ''
            ));
        } catch (DomainException $error) {
            return $this->safe_error($error, 403);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function cancel_units(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('cancel', 10, HOUR_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before trying again.', array('status' => 429));
        }
        if (strlen($request->get_body()) > 16384) {
            return new WP_Error('request_too_large', 'The cancellation request is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        try {
            return rest_ensure_response($this->service->cancel_units(
                is_array($parameters) && isset($parameters['manage_token']) ? $parameters['manage_token'] : '',
                is_array($parameters) && isset($parameters['line_item_keys']) && is_array($parameters['line_item_keys'])
                    ? $parameters['line_item_keys']
                    : array()
            ));
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function reschedule_unit(WP_REST_Request $request)
    {
        if (!$this->consume_rate_limit('reschedule', 15, HOUR_IN_SECONDS)) {
            return new WP_Error('rate_limited', 'Please wait before trying again.', array('status' => 429));
        }
        if (strlen($request->get_body()) > 16384) {
            return new WP_Error('request_too_large', 'The rescheduling request is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        try {
            return rest_ensure_response($this->service->reschedule_unit(
                is_array($parameters) && isset($parameters['manage_token']) ? $parameters['manage_token'] : '',
                is_array($parameters) && isset($parameters['line_item_key']) ? $parameters['line_item_key'] : '',
                is_array($parameters) && isset($parameters['new_date']) ? $parameters['new_date'] : '',
                is_array($parameters) && isset($parameters['new_dropoff']) ? $parameters['new_dropoff'] : ''
            ));
        } catch (DomainException $error) {
            return $this->safe_error($error, 422);
        } catch (Throwable $error) {
            return $this->safe_error($error, 503);
        }
    }

    public function check_public_request(WP_REST_Request $request)
    {
        $nonce = $request->get_header('x-chroma-backup-care-nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'chroma_backup_care_public')) {
            return new WP_Error('invalid_nonce', 'The booking session expired. Refresh and try again.', array('status' => 403));
        }
        $source = $request->get_header('origin') ?: $request->get_header('referer');
        $source_host = $source ? wp_parse_url($source, PHP_URL_HOST) : '';
        $site_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        if (!$source_host || strtolower($source_host) !== strtolower($site_host)) {
            return new WP_Error('invalid_origin', 'The booking request origin is invalid.', array('status' => 403));
        }
        return true;
    }

    private function order_from_request(WP_REST_Request $request)
    {
        if (strlen($request->get_body()) > 65536) {
            return new WP_Error('request_too_large', 'The order is too large.', array('status' => 413));
        }
        $parameters = $request->get_json_params();
        if (!is_array($parameters) || !isset($parameters['order']) || !is_array($parameters['order'])) {
            return new WP_Error('invalid_order', 'A valid order is required.', array('status' => 400));
        }
        return $parameters['order'];
    }

    private function consume_rate_limit($bucket, $limit, $window)
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $key = $bucket . '|' . hash_hmac('sha256', $remote, wp_salt('nonce'));
        try {
            return (bool) $this->rate_limiter->consume_rate_limit($key, $limit, $window);
        } catch (Throwable $error) {
            error_log('Chroma Backup Care rate limiter failure: ' . get_class($error));
            return false;
        }
    }

    private function safe_error(Throwable $error, $status)
    {
        if ($error instanceof DomainException) {
            return new WP_Error('booking_rejected', $error->getMessage(), array('status' => $status));
        }
        error_log('Chroma Backup Care request failure: ' . get_class($error));
        return new WP_Error(
            'booking_unavailable',
            'Backup-care booking is temporarily unavailable. No payment was created.',
            array('status' => $status)
        );
    }
}
