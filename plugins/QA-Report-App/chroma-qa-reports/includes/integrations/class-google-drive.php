<?php
/**
 * Google Drive Integration
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Integrations;

use ChromaQA\Auth\Google_OAuth;
use ChromaQA\Utils\Logger;

/**
 * Handles Google Drive API integration.
 */
class Google_Drive
{

    /**
     * Drive API base URL.
     */
    const API_URL = 'https://www.googleapis.com/drive/v3';
    const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3';

    /**
     * Get Drive root folder ID.
     *
     * @return string
     */
    public static function get_root_folder_id()
    {
        return get_option('cqa_drive_root_folder', '');
    }

    /**
     * Create a folder in Google Drive.
     *
     * @param string $name Folder name.
     * @return string|WP_Error Folder ID on success.
     */
    public static function create_folder($name, $parent_id = '')
    {
        $user_id = get_current_user_id();
        $access_token = Google_OAuth::get_access_token($user_id);

        if (is_wp_error($access_token)) {
            Logger::error('GoogleDrive', 'create_folder', ['name' => $name], $access_token->get_error_message());
            return $access_token;
        }

        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];

        if ($parent_id) {
            $metadata['parents'] = [$parent_id];
        } elseif (self::get_root_folder_id()) {
            $metadata['parents'] = [self::get_root_folder_id()];
        }

        $response = wp_remote_post(self::API_URL . '/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($metadata),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            $err = new \WP_Error('drive_error', $body['error']['message']);
            Logger::error('GoogleDrive', 'create_folder', $metadata, $body);
            return $err;
        }

        Logger::info('GoogleDrive', 'create_folder', $metadata, ['id' => $body['id']]);
        return $body['id'];
    }

    /**
     * Upload a file to Google Drive.
     *
     * @param string $file_path Local file path.
     * @param string $name File name.
     * @param string $folder_id Target folder ID.
     * @param string $mime_type MIME type.
     * @return array|WP_Error File data on success.
     */
    public static function upload_file($file_path, $name, $folder_id = '', $mime_type = '')
    {
        $user_id = get_current_user_id();
        $access_token = Google_OAuth::get_access_token($user_id);

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', __('File not found.', 'chroma-qa-reports'));
        }

        if (!$mime_type) {
            $mime_type = mime_content_type($file_path);
        }

        $metadata = [
            'name' => $name,
        ];

        if ($folder_id) {
            $metadata['parents'] = [$folder_id];
        } elseif (self::get_root_folder_id()) {
            $metadata['parents'] = [self::get_root_folder_id()];
        }

        // Create multipart request
        $boundary = wp_generate_uuid4();

        // Build preamble
        $preamble = "--{$boundary}\r\n";
        $preamble .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $preamble .= wp_json_encode($metadata) . "\r\n";
        $preamble .= "--{$boundary}\r\n";
        $preamble .= "Content-Type: {$mime_type}\r\n\r\n";

        $postamble = "\r\n--{$boundary}--";

        // Memory-efficient body construction
        // We use a temporary file to build the multipart body instead of building a massive string in memory
        $multipart_tmp = sys_get_temp_dir() . '/upload-' . wp_generate_uuid4() . '.tmp';
        $out = fopen($multipart_tmp, 'wb');
        if ($out) {
            fwrite($out, $preamble);

            $in = fopen($file_path, 'rb');
            if ($in) {
                while (!feof($in)) {
                    fwrite($out, fread($in, 1024 * 64)); // 64kb chunks
                }
                fclose($in);
            }

            fwrite($out, $postamble);
            fclose($out);

            // Now we read it back, but at least we didn't do string concatenation which is 2x memory
            $body = file_get_contents($multipart_tmp);
            @unlink($multipart_tmp);
        } else {
            // Fallback to legacy if temp file fails
            $body = $preamble . file_get_contents($file_path) . $postamble;
        }

        $response = wp_remote_post(self::UPLOAD_URL . '/files?uploadType=multipart', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'multipart/related; boundary=' . $boundary,
            ],
            'body' => $body,
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            $err = new \WP_Error('drive_error', $body['error']['message']);
            Logger::error('GoogleDrive', 'upload_file', $metadata, $body);
            return $err;
        }

        Logger::info('GoogleDrive', 'upload_file', $metadata, ['id' => $body['id']]);
        return [
            'id' => $body['id'],
            'name' => $body['name'],
            'mimeType' => $body['mimeType'],
        ];
    }

    /**
     * Get file metadata.
     *
     * @param string $file_id File ID.
     * @return array|WP_Error
     */
    public static function get_file($file_id)
    {
        $user_id = get_current_user_id();
        $access_token = Google_OAuth::get_access_token($user_id);

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $response = wp_remote_get(self::API_URL . '/files/' . $file_id . '?fields=id,name,mimeType,thumbnailLink,webViewLink', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new \WP_Error('drive_error', $body['error']['message']);
        }

        return $body;
    }

    /**
     * List files in a folder.
     *
     * @param string $folder_id Folder ID.
     * @param int    $limit Max files to return.
     * @return array|WP_Error
     */
    public static function list_files($folder_id, $limit = 100)
    {
        $user_id = get_current_user_id();
        $access_token = Google_OAuth::get_access_token($user_id);

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $query = "'{$folder_id}' in parents and trashed = false";
        $url = self::API_URL . '/files?' . http_build_query([
            'q' => $query,
            'pageSize' => $limit,
            'fields' => 'files(id,name,mimeType,thumbnailLink,webViewLink)',
        ]);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new \WP_Error('drive_error', $body['error']['message']);
        }

        return $body['files'] ?? [];
    }

    /**
     * Delete a file.
     *
     * @param string $file_id File ID.
     * @return bool|WP_Error
     */
    public static function delete_file($file_id)
    {
        $user_id = get_current_user_id();
        $access_token = Google_OAuth::get_access_token($user_id);

        if (is_wp_error($access_token)) {
            return $access_token;
        }

        $response = wp_remote_request(self::API_URL . '/files/' . $file_id, [
            'method' => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);

        return $status === 204 || $status === 200;
    }

    /**
     * Create folder structure for a school.
     *
     * @param string $school_name School name.
     * @return string|WP_Error Folder ID on success.
     */
    public static function create_school_folder($school_name)
    {
        $folder_id = self::create_folder($school_name);

        if (is_wp_error($folder_id)) {
            return $folder_id;
        }

        // Create subfolders
        $subfolders = [
            'Photos',
            'Reports',
            'Archives',
        ];

        foreach ($subfolders as $subfolder) {
            self::create_folder($subfolder, $folder_id);
        }

        return $folder_id;
    }

    /**
     * Test connection to Google Drive API.
     * 
     * @return bool
     */
    public static function test_connection()
    {
        $user_id = get_current_user_id();
        $access_token = Google_OAuth::get_access_token($user_id);
        if (is_wp_error($access_token))
            return false;

        $response = wp_remote_get(self::API_URL . '/about?fields=user', [
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
            'timeout' => 5
        ]);

        if (is_wp_error($response))
            return false;
        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    }

    /**
     * Check if a file exists.
     * 
     * @param string $file_id File ID.
     * @return bool
     */
    public function file_exists($file_id)
    {
        $metadata = self::get_file($file_id);
        return !is_wp_error($metadata) && isset($metadata['id']);
    }
}
