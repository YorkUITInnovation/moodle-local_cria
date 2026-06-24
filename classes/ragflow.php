<?php

/**
 * This file is part of Cria.
 *
 * Ragflow reachability and optional credential validation for the sync dashboard.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cria;

class ragflow {

    /**
     * Default Ragflow API base URL for Docker Compose stacks.
     */
    public static function default_url(): string {
        return 'http://ragflow:9380';
    }

    /**
     * @return string
     */
    public static function get_url(): string {
        $config = get_config('local_cria');
        $url = trim((string) ($config->ragflow_url ?? ''));
        return $url !== '' ? $url : self::default_url();
    }

    /**
     * Optional API key copied from Ragflow UI / docker env for sync validation.
     *
     * @return string
     */
    public static function get_api_key(): string {
        $config = get_config('local_cria');
        return trim((string) ($config->ragflow_api_key ?? ''));
    }

    /**
     * Optional tenant id that must match Criadex/Criabot RAGFLOW_TENANT_ID.
     *
     * @return string
     */
    public static function get_tenant_id(): string {
        $config = get_config('local_cria');
        return trim((string) ($config->ragflow_tenant_id ?? ''));
    }

    /**
     * Probe Ragflow /v1/system/healthz (same endpoint used by Docker healthcheck).
     *
     * @return \stdClass Properties: ok (bool), detail (string)
     */
    public static function check_reachable(): \stdClass {
        $healthurl = rtrim(self::get_url(), '/') . '/v1/system/healthz';

        $ch = curl_init($healthurl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = new \stdClass();
        if ($errno !== 0) {
            $result->ok = false;
            $result->detail = $error !== ''
                ? $error
                : get_string('sync_check_unreachable', 'local_cria');
            return $result;
        }

        $result->ok = $httpcode >= 200 && $httpcode < 300;
        $result->detail = $result->ok
            ? get_string('sync_check_ragflow_ok', 'local_cria')
            : get_string('sync_check_ragflow_http', 'local_cria', $httpcode);

        return $result;
    }

    /**
     * Validate Ragflow API key against /api/v1/datasets when configured in plugin settings.
     *
     * @param string|null $apikey
     * @return \stdClass Properties: ok (?bool), skipped (bool), detail (string)
     */
    public static function check_api_key_auth(?string $apikey = null): \stdClass {
        $apikey = $apikey ?? self::get_api_key();
        $result = new \stdClass();
        $result->skipped = false;

        if ($apikey === '') {
            $result->ok = false;
            $result->skipped = true;
            $result->detail = get_string('sync_check_ragflow_api_not_configured', 'local_cria');
            return $result;
        }

        $url = rtrim(self::get_url(), '/') . '/api/v1/datasets';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apikey,
                'Accept: application/json',
            ],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ]);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            $result->ok = false;
            $result->detail = $error !== ''
                ? $error
                : get_string('sync_check_unreachable', 'local_cria');
            return $result;
        }

        if ($httpcode === 401 || $httpcode === 403) {
            $result->ok = false;
            $result->detail = get_string('sync_check_ragflow_api_invalid', 'local_cria', $httpcode);
            return $result;
        }

        $payload = json_decode((string) $body, true);
        if (is_array($payload) && array_key_exists('code', $payload) && (int) $payload['code'] === 0) {
            $result->ok = true;
            $result->detail = get_string('sync_check_ragflow_api_ok', 'local_cria');
            return $result;
        }

        $result->ok = false;
        $result->detail = get_string('sync_check_ragflow_api_http', 'local_cria', $httpcode);
        return $result;
    }

    /**
     * Validate optional tenant id is configured to match backend docker env.
     *
     * @return \stdClass Properties: ok (bool), skipped (bool), detail (string)
     */
    public static function check_tenant_configured(): \stdClass {
        $tenantid = self::get_tenant_id();
        $apikey = self::get_api_key();
        $result = new \stdClass();
        $result->skipped = false;

        if ($apikey === '' && $tenantid === '') {
            $result->ok = false;
            $result->skipped = true;
            $result->detail = get_string('sync_check_ragflow_tenant_not_configured', 'local_cria');
            return $result;
        }

        if ($apikey !== '' && $tenantid === '') {
            $result->ok = false;
            $result->detail = get_string('sync_check_ragflow_tenant_missing', 'local_cria');
            return $result;
        }

        if ($tenantid !== '' && !preg_match('/^[a-f0-9]{32}$/i', $tenantid)) {
            $result->ok = false;
            $result->detail = get_string('sync_check_ragflow_tenant_invalid', 'local_cria');
            return $result;
        }

        $result->ok = true;
        $result->detail = get_string('sync_check_ragflow_tenant_ok', 'local_cria', $tenantid);
        return $result;
    }

    /**
     * Combined Ragflow credential check for sync status.
     *
     * @return \stdClass Properties: ok (bool), detail (string), skipped (bool)
     */
    public static function check_credentials(): \stdClass {
        $api = self::check_api_key_auth();
        $tenant = self::check_tenant_configured();

        $result = new \stdClass();
        $result->skipped = !empty($api->skipped) && !empty($tenant->skipped);

        if ($result->skipped) {
            $result->ok = true;
            $result->detail = get_string('sync_check_ragflow_credentials_skipped', 'local_cria');
            return $result;
        }

        if (empty($api->ok) || empty($tenant->ok)) {
            $result->ok = false;
            $parts = array_filter([$api->detail ?? '', $tenant->detail ?? '']);
            $result->detail = implode(' ', $parts);
            return $result;
        }

        $result->ok = true;
        $result->detail = get_string(
            'sync_check_ragflow_credentials_ok',
            'local_cria',
            (object) [
                'tenant' => self::get_tenant_id(),
            ]
        );
        return $result;
    }

    /**
     * Ragflow-safe display name (matches Criadex kb_sync sanitize_ragflow_name).
     *
     * @param string $value
     * @param int $maxlen
     * @return string
     */
    public static function sanitize_name(string $value, int $maxlen = 128): string {
        $chars = preg_split('//u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        $filtered = '';
        if (is_array($chars)) {
            foreach ($chars as $ch) {
                if (mb_ord($ch) <= 0xFFFF) {
                    $filtered .= $ch;
                }
            }
        }
        $text = trim(preg_replace('/\s+/', ' ', $filtered) ?? '');
        if ($text === '') {
            $text = 'cria-group';
        }
        if (mb_strlen($text) > $maxlen) {
            $text = mb_substr($text, 0, $maxlen);
        }
        return $text;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array|null $jsonbody
     * @return array|null
     */
    private static function api_request(string $method, string $path, ?array $jsonbody = null): ?array {
        $apikey = self::get_api_key();
        if ($apikey === '') {
            return null;
        }

        $url = rtrim(self::get_url(), '/') . $path;
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $apikey,
            'Accept: application/json',
        ];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];
        if ($jsonbody !== null) {
            $payload = json_encode($jsonbody);
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            $options[CURLOPT_POSTFIELDS] = $payload;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false || $body === '') {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param string $path
     * @param string|null $name
     * @return array
     */
    private static function list_api_items(string $path, ?string $name = null): array {
        $items = [];
        for ($page = 1; $page <= 20; $page++) {
            $params = ['page' => $page, 'page_size' => 100];
            if ($name !== null && $name !== '') {
                $params['name'] = $name;
            }
            $query = http_build_query($params);
            $payload = self::api_request('GET', $path . '?' . $query);
            if (!is_array($payload)) {
                break;
            }
            $data = $payload['data'] ?? null;
            $batch = [];
            if (is_array($data) && isset($data['docs']) && is_array($data['docs'])) {
                $batch = $data['docs'];
            } else if (is_array($data) && isset($data['chats']) && is_array($data['chats'])) {
                $batch = $data['chats'];
            } else if (is_array($data) && array_is_list($data)) {
                $batch = $data;
            }
            if ($batch === []) {
                break;
            }
            $items = array_merge($items, $batch);
            if (count($batch) < 100) {
                break;
            }
        }
        return $items;
    }

    /**
     * @param string $endpoint
     * @param array $ids
     * @return void
     */
    private static function delete_ids(string $endpoint, array $ids): void {
        $ids = array_values(array_filter(array_map('strval', $ids)));
        if ($ids === []) {
            return;
        }
        foreach (array_chunk($ids, 50) as $batch) {
            self::api_request('DELETE', $endpoint, ['ids' => $batch]);
        }
    }

    /**
     * Remove Ragflow chat + dataset created for a Criabot document-index group.
     *
     * @param string $criabotname e.g. 181-172
     * @return void
     */
    public static function cleanup_bot_sync_targets(string $criabotname): void {
        $criabotname = trim($criabotname);
        if ($criabotname === '' || self::get_api_key() === '') {
            return;
        }

        $chatname = self::sanitize_name($criabotname, 120);
        $datasetname = self::sanitize_name($criabotname . '-document-index');

        $chatids = [];
        foreach (self::list_api_items('/api/v1/chats') as $chat) {
            if (!empty($chat['id']) && (string) ($chat['name'] ?? '') === $chatname) {
                $chatids[] = (string) $chat['id'];
            }
        }
        self::delete_ids('/api/v1/chats', $chatids);

        $datasetids = [];
        foreach (self::list_api_items('/api/v1/datasets') as $dataset) {
            if (!empty($dataset['id']) && (string) ($dataset['name'] ?? '') === $datasetname) {
                $datasetids[] = (string) $dataset['id'];
            }
        }
        self::delete_ids('/api/v1/datasets', $datasetids);
    }

    /**
     * Remove Ragflow chats/datasets left by Moodle upload-required bots (botid-intentid pattern).
     *
     * @return int Number of Ragflow resources deleted
     */
    public static function cleanup_numeric_bot_artifacts(): int {
        if (self::get_api_key() === '') {
            return 0;
        }

        $removed = 0;
        $chatids = [];
        foreach (self::list_api_items('/api/v1/chats') as $chat) {
            $name = (string) ($chat['name'] ?? '');
            if (!empty($chat['id']) && preg_match('/^\d+-\d+$/', $name)) {
                $chatids[] = (string) $chat['id'];
            }
        }
        if ($chatids !== []) {
            self::delete_ids('/api/v1/chats', $chatids);
            $removed += count($chatids);
        }

        $datasetids = [];
        foreach (self::list_api_items('/api/v1/datasets') as $dataset) {
            $name = (string) ($dataset['name'] ?? '');
            if (!empty($dataset['id']) && preg_match('/^\d+-\d+-document-index$/', $name)) {
                $datasetids[] = (string) $dataset['id'];
            }
        }
        if ($datasetids !== []) {
            self::delete_ids('/api/v1/datasets', $datasetids);
            $removed += count($datasetids);
        }

        return $removed;
    }
}
