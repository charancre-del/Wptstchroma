<?php

namespace ChromaAgentAPI;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Rate_Limiter
{
    public static function check(int $key_id, int $limit_per_min)
    {
        if ($limit_per_min <= 0) {
            return true;
        }

        $window = gmdate('YmdHi');
        $transient_key = 'caa_rl_' . $key_id . '_' . $window;
        $count = get_transient($transient_key);

        if ($count === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS + 5);
            return true;
        }

        $count = (int) $count;
        if ($count >= $limit_per_min) {
            return new WP_Error(
                'caa_rate_limited',
                'Rate limit exceeded for this API key.',
                ['status' => 429]
            );
        }

        set_transient($transient_key, $count + 1, MINUTE_IN_SECONDS + 5);
        return true;
    }
}
