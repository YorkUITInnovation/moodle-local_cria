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
}
