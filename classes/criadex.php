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

use local_cria\gpt;

class criadex
{

    /**
     * @param $data
     * @param string $type azure,cohere
     * @return void
     */
    public static function create_model($data, $type = 'azure')
    {
        // Get Config
        $config = get_config('local_cria');
        // Create model
        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            $data,
            '/models/' . $type . '/create',
            'POST'
        );
    }

    /**
     * @param $model_id
     * @param $data
     * @param string $type azure,cohere
     * @return void
     */
    public static function update_model($model_id, $data, $type = 'azure')
    {
        // Get config
        $config = get_config('local_cria');
        // Update model
        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            $data,
            '/models/' . $type . '/' . $model_id . '/update',
            'PATCH'
        );
    }

    /**
     * @param $model_id
     * @return void
     */
    public static function delete_model($model_id, $type = 'azure')
    {
        // Get config
        $config = get_config('local_cria');
        // Update model
        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            [],
            '/models/' . $type . '/' . $model_id . '/delete',
            'DELETE'
        );
    }

    /**
     * @param $model_id
     * @return void
     */
    public static function about_model($model_id, $type = 'azure')
    {
        // Get config
        $config = get_config('local_cria');
        // Update model
        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            [], '/models/' . $type . '/' . $model_id . '/about',
            'GET'
        );
    }

    /**
     * @param string $type Provider type or empty for aggregate list
     * @return mixed
     */
    public static function list_models(string $type = '')
    {
        $config = get_config('local_cria');
        $path = '/models/list';
        if (!empty($type)) {
            $path = '/models/' . $type . '/list';
        }

        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            [],
            $path,
            'GET'
        );
    }

    /**
     * Sync Ragflow tenant models into the Criadex registry.
     *
     * @return mixed
     */
    public static function sync_ragflow_models()
    {
        $config = get_config('local_cria');

        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            [],
            '/models/ragflow/sync',
            'POST'
        );
    }

    /**
     * Merge duplicate generic models in Criadex (same provider_type + api_model).
     *
     * @return mixed
     */
    public static function dedupe_models()
    {
        $config = get_config('local_cria');

        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            [],
            '/models/dedupe',
            'POST'
        );
    }

    /**
     * @param $model_id
     * @param $system_message
     * @param $prompt
     * @param $max_tokens int
     * @param $temperature float
     * @param $top_p float
     * @return mixed
     * @throws \dml_exception
     */
    public static function query(
        $model_id,
        $system_message,
        $prompt,
        $max_tokens = 512,
        $temperature = 0.1,
        $top_p = 0.1,
    )
    {
        $config = get_config('local_cria');

        $data = [
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'history' => [
                [
                    'role' => 'system',
                    'content' => $system_message
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            json_encode($data),
            '/models/ragflow/' . $model_id . '/agents/chat',
            'POST'
        );
    }

    /*****************************Intents**********************************/
    /**
     * @param $model_id
     * @param $intents
     * @param $prompt
     * @return void
     */
    public static function get_top_intent($bot_id, $prompt) {
        $config = get_config('local_cria');
        $BOT = new bot($bot_id);

        $data = [
            'max_tokens' => $BOT->get_max_tokens(),
            'temperature' => $BOT->get_temperature(),
            'intents' => $BOT->get_intents(),
            'prompt' => $prompt
        ];

        $model_config = $BOT->get_model_config();

        return gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            json_encode($data),
            '/models/ragflow/' . $model_config->criadex_model_id . '/agents/intents',
            'POST'
        );
    }

    /**
     * List all content in the bot's indexes.
     * @param $bot_id
     * @return \stdClass
     * @throws \dml_exception
     */
    public static function list_content($bot_id)
    {
        // Get config
        $config = get_config('local_cria');
        $BOT = new bot($bot_id);

        $groups = new \stdClass();
        $document_index = $BOT->get_bot_name() . '-document-index';
        $question_index = $BOT->get_bot_name() . '-question-index';


        // Update model
        $groups->document_index =  gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            '',
            '/groups/' . $document_index .  '/content/list',
            'GET'
        );
        $groups->question_index =  gpt::_make_call(
            $config->criadex_url,
            $config->criadex_api_key,
            '',
            '/groups/' . $question_index .  '/content/list',
            'GET'
        );

        return $groups;
    }

    /**
     * Return document file names already indexed in Criadex for a bot.
     *
     * @param int $botid
     * @return string[]
     * @throws \dml_exception
     */
    public static function list_document_file_names(int $botid): array
    {
        $groups = self::list_content($botid);
        $documentindex = $groups->document_index ?? null;
        if (!is_object($documentindex) || (int) ($documentindex->status ?? 0) !== 200) {
            return [];
        }
        if (!isset($documentindex->files) || !is_array($documentindex->files)) {
            return [];
        }

        return $documentindex->files;
    }
}