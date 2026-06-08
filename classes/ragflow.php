<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Lightweight Ragflow reachability checks (no tenant/API key management).
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
}
