<?php
/**
 * monday.com Integration Service
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Integrations;

use ChromaQA\Settings;
use WP_Error;

class Monday
{
    const API_URL = 'https://api.monday.com/v2';
    const API_VERSION = '2026-01';
    const DEFAULT_STATUS = 'Not Started';
    const REMOVED_STATUS = 'Removed from QA Sync';

    /**
     * Determine whether monday sync is enabled.
     *
     * @return bool
     */
    public static function is_enabled()
    {
        return Settings::get('monday_enabled', 'no') === 'yes';
    }

    /**
     * Return monday API token.
     *
     * @return string
     */
    public static function get_token()
    {
        return trim((string) Settings::get('monday_api_token', ''));
    }

    /**
     * Whether approval-triggered sync is enabled.
     *
     * @return bool
     */
    public static function auto_sync_on_approval()
    {
        return Settings::get('monday_auto_sync_on_approval', 'yes') === 'yes';
    }

    /**
     * Default status label for newly created monday items.
     *
     * @return string
     */
    public static function default_status_label()
    {
        return (string) Settings::get('monday_default_status_label', self::DEFAULT_STATUS);
    }

    /**
     * Required board columns.
     *
     * @return array<string,array<string,string>>
     */
    public static function required_columns()
    {
        return [
            'monday_status_column_id' => [
                'title' => 'Status',
                'type' => 'status',
                'id' => 'qa_status',
            ],
            'monday_priority_column_id' => [
                'title' => 'Priority',
                'type' => 'status',
                'id' => 'qa_priority',
            ],
            'monday_date_column_id' => [
                'title' => 'Due Date',
                'type' => 'date',
                'id' => 'qa_due_date',
            ],
            'monday_notes_column_id' => [
                'title' => 'Notes',
                'type' => 'long_text',
                'id' => 'qa_notes',
            ],
            'monday_person_column_id' => [
                'title' => 'Person',
                'type' => 'people',
                'id' => 'qa_person',
            ],
        ];
    }

    /**
     * Execute a monday GraphQL request.
     *
     * @param string $query GraphQL query.
     * @param array  $variables Variables.
     * @return array|WP_Error
     */
    public static function request($query, $variables = [])
    {
        $token = self::get_token();
        if ($token === '') {
            return new WP_Error('cqa_monday_missing_token', __('monday.com API token is not configured.', 'chroma-qa-reports'), ['status' => 422]);
        }

        $response = wp_remote_post(
            self::API_URL,
            [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => $token,
                    'Content-Type' => 'application/json',
                    'API-Version' => self::API_VERSION,
                ],
                'body' => wp_json_encode(
                    [
                        'query' => $query,
                        'variables' => $variables,
                    ]
                ),
            ]
        );

        if (is_wp_error($response)) {
            self::log_error('transport_error', $response->get_error_messages());

            return new WP_Error(
                'cqa_monday_transport_error',
                __('Could not reach monday.com. Please try again in a moment.', 'chroma-qa-reports'),
                [
                    'status' => 424,
                    'details' => $response->get_error_messages(),
                ]
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw_body = (string) wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);

        if ($code >= 400) {
            $message = self::extract_http_error_message($body, $code);
            self::log_error(
                'http_error',
                [
                    'http_status' => $code,
                    'body' => is_array($body) ? $body : substr($raw_body, 0, 500),
                ]
            );

            return new WP_Error(
                'cqa_monday_http_error',
                $message,
                [
                    'status' => self::map_http_status($code),
                    'monday_status' => $code,
                    'body' => is_array($body) ? $body : null,
                ]
            );
        }

        if (!is_array($body)) {
            self::log_error('invalid_response', substr($raw_body, 0, 500));

            return new WP_Error(
                'cqa_monday_invalid_response',
                __('Invalid response received from monday.com.', 'chroma-qa-reports'),
                [
                    'status' => 424,
                    'response_text' => substr($raw_body, 0, 500),
                ]
            );
        }

        if (!empty($body['errors'])) {
            $message = self::extract_graphql_error_message($body['errors']);
            self::log_error('graphql_error', $body['errors']);

            return new WP_Error(
                'cqa_monday_api_error',
                $message,
                [
                    'status' => self::map_graphql_status($body['errors']),
                    'errors' => $body['errors'],
                ]
            );
        }

        return $body['data'] ?? [];
    }

    /**
     * Map monday HTTP responses to JSON-safe application statuses.
     *
     * Avoid returning 5xx here for ordinary dependency/app failures so upstream
     * proxies don't replace our REST JSON with branded HTML error pages.
     *
     * @param int $http_status monday HTTP status.
     * @return int
     */
    private static function map_http_status($http_status)
    {
        if (in_array($http_status, [401, 403, 404, 409, 422, 429], true)) {
            return $http_status;
        }

        return 424;
    }

    /**
     * Map monday GraphQL errors to JSON-safe application statuses.
     *
     * @param array $errors GraphQL errors.
     * @return int
     */
    private static function map_graphql_status($errors)
    {
        foreach ((array) $errors as $error) {
            $extensions = is_array($error['extensions'] ?? null) ? $error['extensions'] : [];
            $code = strtolower((string) ($extensions['code'] ?? ''));
            $status_code = isset($extensions['status_code']) ? (int) $extensions['status_code'] : 0;
            $message = strtolower((string) ($error['message'] ?? ''));

            if (in_array($status_code, [401, 403, 404, 409, 422, 429], true)) {
                return $status_code;
            }

            if (
                str_contains($code, 'auth') ||
                str_contains($message, 'unauthorized') ||
                str_contains($message, 'invalid api token')
            ) {
                return 401;
            }

            if (
                str_contains($code, 'permission') ||
                str_contains($message, 'forbidden') ||
                str_contains($message, 'permission')
            ) {
                return 403;
            }

            if (
                str_contains($code, 'validation') ||
                str_contains($message, 'column') ||
                str_contains($message, 'argument') ||
                str_contains($message, 'variable')
            ) {
                return 400;
            }
        }

        return 400;
    }

    /**
     * Extract the most useful message from monday GraphQL errors.
     *
     * @param array $errors GraphQL errors.
     * @return string
     */
    private static function extract_graphql_error_message($errors)
    {
        foreach ((array) $errors as $error) {
            if (!empty($error['message'])) {
                return (string) $error['message'];
            }
        }

        return __('Unknown monday.com API error.', 'chroma-qa-reports');
    }

    /**
     * Extract the most useful message from monday HTTP/body errors.
     *
     * @param mixed $body Decoded body.
     * @param int   $code HTTP status code.
     * @return string
     */
    private static function extract_http_error_message($body, $code)
    {
        if (is_array($body)) {
            if (!empty($body['error_message'])) {
                return (string) $body['error_message'];
            }

            if (!empty($body['message'])) {
                return (string) $body['message'];
            }

            if (!empty($body['errors']) && is_array($body['errors'])) {
                return self::extract_graphql_error_message($body['errors']);
            }
        }

        return sprintf(__('monday.com request failed with status %d.', 'chroma-qa-reports'), $code);
    }

    /**
     * Log monday integration failures for easier support diagnosis.
     *
     * @param string $context Error context.
     * @param mixed  $payload Debug payload.
     * @return void
     */
    private static function log_error($context, $payload)
    {
        error_log(
            sprintf(
                '[CQA Monday] %s: %s',
                $context,
                wp_json_encode($payload)
            )
        );
    }

    /**
     * Test monday connection.
     *
     * @return array|WP_Error
     */
    public static function test_connection()
    {
        // monday's auth docs use `me { id name }` as the stable test query.
        $data = self::request('query { me { id name } }');
        if (is_wp_error($data)) {
            return $data;
        }

        return $data['me'] ?? [];
    }

    /**
     * Fetch available workspaces.
     *
     * @return array|WP_Error
     */
    public static function get_workspaces()
    {
        $query = <<<'GRAPHQL'
query {
  workspaces {
    id
    name
    kind
    description
  }
}
GRAPHQL;

        $data = self::request($query);
        if (is_wp_error($data)) {
            return $data;
        }

        return $data['workspaces'] ?? [];
    }

    /**
     * Fetch available boards.
     *
     * @return array|WP_Error
     */
    public static function get_boards($workspace_id = null)
    {
        if ($workspace_id) {
            $query = <<<'GRAPHQL'
query ($workspaceIds: [ID!]) {
  boards(limit: 200, workspace_ids: $workspaceIds) {
    id
    name
    state
  }
}
GRAPHQL;

            $data = self::request($query, ['workspaceIds' => [(string) $workspace_id]]);
        } else {
            $query = <<<'GRAPHQL'
query {
  boards(limit: 200) {
    id
    name
    state
  }
}
GRAPHQL;

            $data = self::request($query);
        }

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['boards'] ?? [];
    }

    /**
     * Fetch board metadata.
     *
     * @param string|int $board_id Board ID.
     * @return array|WP_Error
     */
    public static function get_board($board_id)
    {
        $query = <<<'GRAPHQL'
query ($boardId: [ID!]) {
  boards(ids: $boardId) {
    id
    name
    columns {
      id
      title
      type
      settings_str
    }
    groups {
      id
      title
      archived
      deleted
    }
  }
}
GRAPHQL;

        $data = self::request($query, ['boardId' => [(string) $board_id]]);
        if (is_wp_error($data)) {
            return $data;
        }

        return $data['boards'][0] ?? [];
    }

    /**
     * Find a group by ID on a board.
     *
     * @param string|int $board_id Board ID.
     * @param string|int $group_id Group ID.
     * @return array|null|WP_Error
     */
    public static function find_group($board_id, $group_id)
    {
        $board = self::get_board($board_id);
        if (is_wp_error($board)) {
            return $board;
        }

        foreach (($board['groups'] ?? []) as $group) {
            if (!empty($group['deleted']) || !empty($group['archived'])) {
                continue;
            }

            if ((string) ($group['id'] ?? '') === (string) $group_id) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Inspect a board and find matching columns.
     *
     * @param string|int $board_id Board ID.
     * @param array      $current_map Existing school mapping.
     * @return array|WP_Error
     */
    public static function inspect_board($board_id, $current_map = [])
    {
        $board = self::get_board($board_id);
        if (is_wp_error($board)) {
            return $board;
        }

        $columns = $board['columns'] ?? [];
        $mapping = [];
        $missing = [];

        foreach (self::required_columns() as $field => $definition) {
            $mapped_id = isset($current_map[$field]) ? (string) $current_map[$field] : '';
            $matched = self::find_matching_column($columns, $definition, $mapped_id);

            if ($matched) {
                $mapping[$field] = $matched['id'];
            } else {
                $mapping[$field] = '';
                $missing[$field] = $definition;
            }
        }

        return [
            'board' => $board,
            'mapping' => $mapping,
            'missing' => $missing,
        ];
    }

    /**
     * Ensure required board columns exist.
     *
     * @param string|int $board_id Board ID.
     * @param array      $current_map Existing mapping.
     * @return array|WP_Error
     */
    public static function ensure_board_columns($board_id, $current_map = [])
    {
        $inspection = self::inspect_board($board_id, $current_map);
        if (is_wp_error($inspection)) {
            return $inspection;
        }

        $mapping = $inspection['mapping'];
        $missing = $inspection['missing'];

        foreach ($missing as $field => $definition) {
            $created = self::create_column($board_id, $definition['title'], $definition['type'], $definition['id']);
            if (is_wp_error($created)) {
                return $created;
            }

            $mapping[$field] = $created['id'] ?? '';
        }

        return [
            'board_id' => (string) $board_id,
            'board_name' => $inspection['board']['name'] ?? '',
            'mapping' => $mapping,
        ];
    }

    /**
     * Create a board column.
     *
     * @param string|int $board_id Board ID.
     * @param string     $title Column title.
     * @param string     $type Column type.
     * @param string     $id Preferred custom ID.
     * @return array|WP_Error
     */
    public static function create_column($board_id, $title, $type, $id)
    {
        $query = <<<'GRAPHQL'
mutation ($boardId: ID!, $title: String!, $columnType: ColumnType!, $id: String) {
  create_column(board_id: $boardId, title: $title, column_type: $columnType, id: $id) {
    id
    title
    type
  }
}
GRAPHQL;

        $data = self::request(
            $query,
            [
                'boardId' => (string) $board_id,
                'title' => $title,
                'columnType' => $type,
                'id' => $id,
            ]
        );

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['create_column'] ?? [];
    }

    /**
     * Find a group by title on a board.
     *
     * @param string|int $board_id Board ID.
     * @param string     $group_title Group title.
     * @return array|null|WP_Error
     */
    public static function find_group_by_title($board_id, $group_title)
    {
        $board = self::get_board($board_id);
        if (is_wp_error($board)) {
            return $board;
        }

        foreach (($board['groups'] ?? []) as $group) {
            if (!empty($group['deleted']) || !empty($group['archived'])) {
                continue;
            }

            if (($group['title'] ?? '') === $group_title) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Create a report group on a board.
     *
     * @param string|int $board_id Board ID.
     * @param string     $group_name Group title.
     * @return array|WP_Error
     */
    public static function create_group($board_id, $group_name)
    {
        $query = <<<'GRAPHQL'
mutation ($boardId: ID!, $groupName: String!) {
  create_group(board_id: $boardId, group_name: $groupName) {
    id
    title
  }
}
GRAPHQL;

        $data = self::request(
            $query,
            [
                'boardId' => (string) $board_id,
                'groupName' => $group_name,
            ]
        );

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['create_group'] ?? [];
    }

    /**
     * Create a monday item.
     *
     * @param string|int $board_id Board ID.
     * @param string|int $group_id Group ID.
     * @param string     $item_name Item title.
     * @param array      $column_values Column values.
     * @return array|WP_Error
     */
    public static function create_item($board_id, $group_id, $item_name, $column_values = [])
    {
        $query = <<<'GRAPHQL'
mutation ($boardId: ID!, $groupId: String!, $itemName: String!, $columnValues: JSON!, $createLabelsIfMissing: Boolean) {
  create_item(board_id: $boardId, group_id: $groupId, item_name: $itemName, column_values: $columnValues, create_labels_if_missing: $createLabelsIfMissing) {
    id
    name
  }
}
GRAPHQL;

        $data = self::request(
            $query,
            [
                'boardId' => (string) $board_id,
                'groupId' => (string) $group_id,
                'itemName' => $item_name,
                'columnValues' => wp_json_encode($column_values),
                'createLabelsIfMissing' => true,
            ]
        );

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['create_item'] ?? [];
    }

    /**
     * Update multiple column values on an item.
     *
     * @param string|int $board_id Board ID.
     * @param string|int $item_id Item ID.
     * @param array      $column_values Column values.
     * @param bool       $create_labels Whether to create missing labels.
     * @return array|WP_Error
     */
    public static function update_item($board_id, $item_id, $column_values = [], $create_labels = false)
    {
        if (empty($column_values)) {
            return ['id' => (string) $item_id];
        }

        $query = <<<'GRAPHQL'
mutation ($boardId: ID!, $itemId: ID!, $columnValues: JSON!, $createLabelsIfMissing: Boolean) {
  change_multiple_column_values(board_id: $boardId, item_id: $itemId, column_values: $columnValues, create_labels_if_missing: $createLabelsIfMissing) {
    id
  }
}
GRAPHQL;

        $data = self::request(
            $query,
            [
                'boardId' => (string) $board_id,
                'itemId' => (string) $item_id,
                'columnValues' => wp_json_encode($column_values),
                'createLabelsIfMissing' => (bool) $create_labels,
            ]
        );

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['change_multiple_column_values'] ?? [];
    }

    /**
     * Rename an item.
     *
     * @param string|int $item_id Item ID.
     * @param string     $name New name.
     * @return array|WP_Error
     */
    public static function rename_item($item_id, $name)
    {
        $query = <<<'GRAPHQL'
mutation ($itemId: ID!, $itemName: String!) {
  change_item_name(item_id: $itemId, name: $itemName) {
    id
    name
  }
}
GRAPHQL;

        $data = self::request(
            $query,
            [
                'itemId' => (string) $item_id,
                'itemName' => $name,
            ]
        );

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['change_item_name'] ?? [];
    }

    /**
     * Fetch a monday item by ID.
     *
     * @param string|int $item_id Item ID.
     * @return array|null|WP_Error
     */
    public static function get_item($item_id)
    {
        $query = <<<'GRAPHQL'
query ($itemIds: [ID!]) {
  items(ids: $itemIds) {
    id
    name
  }
}
GRAPHQL;

        $data = self::request(
            $query,
            [
                'itemIds' => [(string) $item_id],
            ]
        );

        if (is_wp_error($data)) {
            return $data;
        }

        return $data['items'][0] ?? null;
    }

    /**
     * Build monday column payloads from mapped school columns.
     *
     * @param array $mapping School monday mapping.
     * @param array $values Values keyed by logical field.
     * @return array
     */
    public static function format_column_values($mapping, $values)
    {
        $payload = [];

        if (!empty($mapping['monday_status_column_id']) && isset($values['status'])) {
            $payload[$mapping['monday_status_column_id']] = ['label' => (string) $values['status']];
        }

        if (!empty($mapping['monday_priority_column_id']) && isset($values['priority'])) {
            $payload[$mapping['monday_priority_column_id']] = ['label' => (string) $values['priority']];
        }

        if (!empty($mapping['monday_date_column_id']) && !empty($values['date'])) {
            $payload[$mapping['monday_date_column_id']] = ['date' => (string) $values['date']];
        }

        if (!empty($mapping['monday_notes_column_id']) && array_key_exists('notes', $values)) {
            $payload[$mapping['monday_notes_column_id']] = ['text' => (string) $values['notes']];
        }

        if (!empty($mapping['monday_person_column_id']) && !empty($values['person_id'])) {
            $payload[$mapping['monday_person_column_id']] = [
                'personsAndTeams' => [
                    [
                        'id' => (int) $values['person_id'],
                        'kind' => 'person',
                    ],
                ],
            ];
        }

        return $payload;
    }

    /**
     * Find a matching column by existing mapping, custom ID, or title/type.
     *
     * @param array  $columns Columns from board.
     * @param array  $definition Required definition.
     * @param string $mapped_id Existing mapped ID.
     * @return array|null
     */
    private static function find_matching_column($columns, $definition, $mapped_id = '')
    {
        foreach ($columns as $column) {
            if ($mapped_id !== '' && ($column['id'] ?? '') === $mapped_id) {
                return $column;
            }
        }

        foreach ($columns as $column) {
            if (($column['id'] ?? '') === $definition['id']) {
                return $column;
            }
        }

        foreach ($columns as $column) {
            if (
                strtolower((string) ($column['title'] ?? '')) === strtolower($definition['title']) &&
                strtolower((string) ($column['type'] ?? '')) === strtolower($definition['type'])
            ) {
                return $column;
            }
        }

        return null;
    }
}
