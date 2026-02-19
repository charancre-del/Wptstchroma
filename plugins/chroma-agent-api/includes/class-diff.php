<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Diff
{
    public static function compare($before, $after)
    {
        if (is_object($before)) {
            $before = (array) $before;
        }

        if (is_object($after)) {
            $after = (array) $after;
        }

        if (!is_array($before) || !is_array($after)) {
            if ($before === $after) {
                return [];
            }

            return [
                'from' => $before,
                'to' => $after,
            ];
        }

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $diff = [];

        foreach ($keys as $key) {
            $exists_before = array_key_exists($key, $before);
            $exists_after = array_key_exists($key, $after);

            if (!$exists_before) {
                $diff[$key] = ['from' => null, 'to' => $after[$key]];
                continue;
            }

            if (!$exists_after) {
                $diff[$key] = ['from' => $before[$key], 'to' => null];
                continue;
            }

            if ($before[$key] === $after[$key]) {
                continue;
            }

            if ((is_array($before[$key]) || is_object($before[$key])) && (is_array($after[$key]) || is_object($after[$key]))) {
                $child = self::compare($before[$key], $after[$key]);
                if (!empty($child)) {
                    $diff[$key] = $child;
                }
                continue;
            }

            $diff[$key] = [
                'from' => $before[$key],
                'to' => $after[$key],
            ];
        }

        return $diff;
    }
}
