<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Normalizes HTTP responses from Criadex, Criabot, and related services.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cria;

class api_response {

    /**
     * Whether the API response represents success (HTTP 200).
     *
     * @param mixed $response Decoded JSON object from gpt::_make_call().
     * @return bool
     */
    public static function is_success($response): bool {
        if (!is_object($response) || !isset($response->status)) {
            return false;
        }
        return (int) $response->status === 200;
    }

    /**
     * Extract a human-readable error message from an API response.
     *
     * @param mixed $response
     * @param string $fallback
     * @return string
     */
    public static function error_message($response, string $fallback = ''): string {
        if (!is_object($response)) {
            return $fallback !== '' ? $fallback : get_string('sync_error_unknown', 'local_cria');
        }

        $parts = [];
        if (isset($response->status)) {
            $parts[] = 'HTTP ' . $response->status;
        }
        if (!empty($response->code)) {
            $parts[] = (string) $response->code;
        }
        if (!empty($response->message)) {
            $parts[] = (string) $response->message;
        }

        if (!empty($parts)) {
            return implode(' — ', $parts);
        }

        return $fallback !== '' ? $fallback : get_string('sync_error_unknown', 'local_cria');
    }

    /**
     * Show a Moodle error notification from an API response.
     *
     * @param mixed $response
     * @param string $fallback
     * @return void
     */
    public static function notify_error($response, string $fallback = ''): void {
        \core\notification::error(self::error_message($response, $fallback));
    }

    /**
     * Log an API issue for administrators (visible when debugging is enabled).
     *
     * @param string $context Short label, e.g. "bot push" or "document upload".
     * @param mixed $response
     * @return void
     */
    public static function log_issue(string $context, $response): void {
        debugging('local_cria ' . $context . ': ' . self::error_message($response), DEBUG_NORMAL);
    }
}
