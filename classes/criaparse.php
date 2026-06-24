<?php

/**
* This file is part of Cria.
* Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
* Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
* You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
*
* @package    local_cria
* @author     Patrick Thibaudeau
* @copyright  2024 onwards York University (https://yorku.ca)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


namespace local_cria;

class criaparse
{
    public static function get_strategies() {
        $config = get_config('local_cria');
        $endpoint = $config->criaparse_url . '/parser/strategies?x-api-key=' . $config->criadex_api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            debugging('CriaParse unavailable: ' . curl_error($ch), DEBUG_DEVELOPER);
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    /******** Document Content ********/
    /**
     * Parse document content
     * @param $llm_model_id String
     * @param $embedding_model_id String
     * @param $strategy String
     * @param $file_path String
     * @param $mime_type String
     * @return Array
     */
    public static function execute($llm_model_id, $embedding_model_id, $strategy, $file_path, $mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        $config = get_config('local_cria');
        $endpoint = $config->criaparse_url . '/parser/parse?strategy=' . $strategy;
        if ($strategy !== 'PARAGRAPH') {
            $endpoint .= '&llm_model_id=' . $llm_model_id . '&embedding_model_id=' . $embedding_model_id;
        }
        $endpoint .= '&x-api-key=' . $config->criadex_api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new \CURLFile($file_path, $mime_type)
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            debugging('CriaParse execute failed: ' . curl_error($ch), DEBUG_DEVELOPER);
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * File types that PARAGRAPH can parse when GENERIC prerequisites are missing.
     *
     * @return string[]
     */
    public static function paragraph_capable_file_types(): array {
        return ['docx', 'md', 'txt', 'html'];
    }

    /**
     * Whether both Criadex model ids still exist in the remote registry.
     *
     * @param int $llm_model_id
     * @param int $embedding_model_id
     * @return bool
     */
    public static function criadex_models_exist(int $llm_model_id, int $embedding_model_id): bool {
        if ($llm_model_id <= 0 || $embedding_model_id <= 0) {
            return false;
        }

        $response = criadex::list_models();
        if (!is_object($response) || empty($response->models) || !is_array($response->models)) {
            return false;
        }

        $remoteids = [];
        foreach ($response->models as $model) {
            if (isset($model->id)) {
                $remoteids[(int) $model->id] = true;
            }
        }

        return isset($remoteids[$llm_model_id], $remoteids[$embedding_model_id]);
    }

    /**
     * Pick a parsing strategy for Moodle document indexing.
     *
     * Falls back to PARAGRAPH for text-like files when GENERIC would require
     * missing/stale Criadex model ids.
     *
     * @param string $filetype
     * @param string $botstrategy
     * @param int $llm_model_id
     * @param int $embedding_model_id
     * @return string
     */
    public static function resolve_indexing_strategy(
        string $filetype,
        string $botstrategy,
        int $llm_model_id,
        int $embedding_model_id
    ): string {
        $strategy = self::set_parsing_strategy_based_on_file_type($filetype, $botstrategy);
        if ($strategy === 'PARAGRAPH') {
            return $strategy;
        }

        if (
            in_array($filetype, self::paragraph_capable_file_types(), true)
            && !self::criadex_models_exist($llm_model_id, $embedding_model_id)
        ) {
            return 'PARAGRAPH';
        }

        return $strategy;
    }

    /**
     * Set parsing strategy based on file type. If docx, use strategy from bot. Otherwise, use generic strategy.
     * @param $file_type String
     * @param $current_strategy String
     * @return String
     */
    public static function set_parsing_strategy_based_on_file_type($file_type, $current_strategy) {
        switch ($file_type) {
            case 'docx':
                return $current_strategy;
            case 'md':
            case 'txt':
            case 'html':
                return 'PARAGRAPH';
            default:
                return 'GENERIC';
        }
    }
}