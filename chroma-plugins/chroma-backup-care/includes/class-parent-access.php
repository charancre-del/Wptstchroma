<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_Parent_Access
{
    const CHALLENGE_TTL = 600;
    const TOKEN_TTL = 1800;
    const MAX_ATTEMPTS = 5;

    private $config;

    public function __construct(Chroma_Backup_Care_Config $config)
    {
        $this->config = $config;
    }

    public function request_code($email)
    {
        $email = $this->normalize_email($email);
        $challenge_id = bin2hex(random_bytes(16));
        $code = (string) random_int(100000, 999999);
        $state = array(
            'email_subject' => $this->email_subject($email),
            'code_hash' => $this->code_hash($challenge_id, $code),
            'attempts' => 0,
            'expires_at' => time() + self::CHALLENGE_TTL,
        );
        set_transient($this->challenge_key($challenge_id), $state, self::CHALLENGE_TTL);

        $sent = wp_mail(
            $email,
            'Your Chroma Backup Care verification code',
            "Your Chroma Backup Care verification code is {$code}.\n\n"
                . "It expires in 10 minutes. If you did not request it, you can ignore this email.",
            array('From: Chroma ELA <' . $this->config->email_from() . '>')
        );
        if (!$sent) {
            delete_transient($this->challenge_key($challenge_id));
            throw new RuntimeException('The verification email could not be sent.');
        }

        return array(
            'challenge_id' => $challenge_id,
            'expires_in_seconds' => self::CHALLENGE_TTL,
        );
    }

    public function verify_code($challenge_id, $email, $code)
    {
        $challenge_id = strtolower(trim((string) $challenge_id));
        $code = trim((string) $code);
        if (!preg_match('/^[a-f0-9]{32}$/', $challenge_id) || !preg_match('/^[0-9]{6}$/', $code)) {
            throw new DomainException('The verification code is invalid or expired.');
        }
        $email = $this->normalize_email($email);
        $key = $this->challenge_key($challenge_id);
        $state = get_transient($key);
        if (!is_array($state) || empty($state['expires_at']) || (int) $state['expires_at'] < time()) {
            delete_transient($key);
            throw new DomainException('The verification code is invalid or expired.');
        }

        $attempts = isset($state['attempts']) ? (int) $state['attempts'] : 0;
        if ($attempts >= self::MAX_ATTEMPTS
            || empty($state['email_subject'])
            || !hash_equals((string) $state['email_subject'], $this->email_subject($email))
            || empty($state['code_hash'])
            || !hash_equals((string) $state['code_hash'], $this->code_hash($challenge_id, $code))) {
            $state['attempts'] = $attempts + 1;
            if ($state['attempts'] >= self::MAX_ATTEMPTS) {
                delete_transient($key);
            } else {
                $remaining = max(1, (int) $state['expires_at'] - time());
                set_transient($key, $state, $remaining);
            }
            throw new DomainException('The verification code is invalid or expired.');
        }

        delete_transient($key);
        return array(
            'parent_access_token' => $this->create_token($email),
            'expires_in_seconds' => self::TOKEN_TTL,
        );
    }

    public function assert_token($token, $email)
    {
        $parts = explode('.', trim((string) $token), 2);
        if (count($parts) !== 2) {
            throw new DomainException('Verify the parent email before reviewing the booking.');
        }
        $payload_json = $this->base64url_decode($parts[0]);
        $signature = $this->base64url_decode($parts[1]);
        $expected = hash_hmac('sha256', $parts[0], $this->config->quote_signing_key(), true);
        $payload = json_decode($payload_json, true);
        if (!hash_equals($expected, $signature) || !is_array($payload)
            || empty($payload['subject']) || empty($payload['issued_at']) || empty($payload['expires_at'])
            || (int) $payload['issued_at'] > time() + 60
            || (int) $payload['expires_at'] < time()
            || (int) $payload['expires_at'] - (int) $payload['issued_at'] > self::TOKEN_TTL
            || !hash_equals((string) $payload['subject'], $this->email_subject($this->normalize_email($email)))) {
            throw new DomainException('Verify the parent email before reviewing the booking.');
        }
        return true;
    }

    private function create_token($email)
    {
        $issued_at = time();
        $payload = $this->base64url(wp_json_encode(array(
            'subject' => $this->email_subject($email),
            'issued_at' => $issued_at,
            'expires_at' => $issued_at + self::TOKEN_TTL,
            'nonce' => bin2hex(random_bytes(8)),
        )));
        return $payload . '.' . $this->base64url(
            hash_hmac('sha256', $payload, $this->config->quote_signing_key(), true)
        );
    }

    private function normalize_email($email)
    {
        $email = strtolower(trim((string) $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new DomainException('Enter a valid parent email address.');
        }
        return $email;
    }

    private function email_subject($email)
    {
        return hash_hmac('sha256', $email, $this->config->quote_signing_key());
    }

    private function code_hash($challenge_id, $code)
    {
        return hash_hmac('sha256', $challenge_id . '|' . $code, $this->config->quote_signing_key());
    }

    private function challenge_key($challenge_id)
    {
        return 'cbc_parent_access_' . $challenge_id;
    }

    private function base64url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64url_decode($value)
    {
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }
}
