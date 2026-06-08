<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Coordinates synchronization between Moodle, Criadex, and Criabot.
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cria;

class sync_manager {

    /**
     * Ensure local provider rows exist for each provider type returned by Criadex.
     *
     * @return int Number of provider rows created.
     */
    public static function ensure_providers_for_remote_types(): int {
        global $DB, $USER;

        $response = criadex::list_models();
        if (!api_response::is_success($response) || empty($response->models)) {
            return 0;
        }

        $providers = $DB->get_records('local_cria_providers');
        $providersbytype = [];
        foreach ($providers as $provider) {
            $providersbytype[strtolower($provider->type ?? '')] = true;
        }

        $created = 0;
        $neededtypes = [];
        foreach ($response->models as $remotemodel) {
            $ptype = strtolower($remotemodel->provider_type ?? '');
            if ($ptype !== '' && !isset($providersbytype[$ptype])) {
                $neededtypes[$ptype] = true;
            }
        }

        foreach (array_keys($neededtypes) as $ptype) {
            $insert = new \stdClass();
            $insert->name = ucfirst($ptype);
            $insert->idnumber = $ptype;
            $insert->type = $ptype;
            $insert->llm_models = '';
            $insert->usermodified = $USER->id ?? 0;
            $insert->timecreated = time();
            $insert->timemodified = time();
            $DB->insert_record('local_cria_providers', $insert);
            $providersbytype[$ptype] = true;
            $created++;
        }

        return $created;
    }

    public static function sync_models_from_criadex(bool $removestale = false): \stdClass {
        global $DB, $USER;

        self::ensure_providers_for_remote_types();

        $result = (object) [
            'success' => false,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'removed' => 0,
            'message' => '',
        ];

        $response = criadex::list_models();
        if (!api_response::is_success($response)) {
            $result->message = api_response::error_message(
                $response,
                get_string('sync_models_failed', 'local_cria')
            );
            return $result;
        }

        if (empty($response->models)) {
            $result->success = true;
            $result->message = get_string('sync_models_empty', 'local_cria');
            return $result;
        }

        $providers = $DB->get_records('local_cria_providers');
        $providersbytype = [];
        foreach ($providers as $provider) {
            $ptype = strtolower($provider->type ?? 'azure');
            if (!isset($providersbytype[$ptype])) {
                $providersbytype[$ptype] = $provider;
            }
        }

        $remoteids = [];
        $seenapimodels = [];

        $remotemodels = $response->models;
        usort($remotemodels, function ($a, $b) {
            return ((int) ($a->id ?? 0)) <=> ((int) ($b->id ?? 0));
        });

        foreach ($remotemodels as $remotemodel) {
            $providertype = strtolower($remotemodel->provider_type ?? 'azure');
            if (!isset($providersbytype[$providertype])) {
                $result->skipped++;
                continue;
            }

            $provider = $providersbytype[$providertype];
            $remotemodelid = (int) ($remotemodel->id ?? 0);
            if ($remotemodelid <= 0) {
                $result->skipped++;
                continue;
            }

            $apimodel = trim((string) ($remotemodel->api_model ?? ''));
            $dedupekey = $provider->id . '|' . strtolower($apimodel);
            if ($apimodel !== '' && isset($seenapimodels[$dedupekey])) {
                $result->skipped++;
                continue;
            }
            if ($apimodel !== '') {
                $seenapimodels[$dedupekey] = $remotemodelid;
            }

            $remoteids[$remotemodelid] = true;
            $apideployment = trim((string) ($remotemodel->api_deployment ?? ''));
            $apiresource = trim((string) ($remotemodel->api_resource ?? ''));

            $name = $apimodel;
            if ($name === '') {
                $name = $apideployment !== '' ? $apideployment : ('model-' . $remotemodelid);
            }

            $isembedding = 0;
            if ($apimodel !== '' && stripos($apimodel, 'embedding') !== false) {
                $isembedding = 1;
            }

            $value = json_encode([
                'api_model' => $apimodel,
                'api_resource' => $apiresource,
                'api_deployment' => $apideployment,
                'provider_type' => $providertype,
                'config' => $remotemodel->config ?? null,
            ]);

            $existing = $DB->get_record('local_cria_models', [
                'provider_id' => $provider->id,
                'criadex_model_id' => $remotemodelid,
            ]);

            if (!$existing && $apimodel !== '') {
                $existing = $DB->get_record('local_cria_models', [
                    'provider_id' => $provider->id,
                    'name' => $name,
                ], '*', IGNORE_MULTIPLE);
            }

            if ($existing) {
                $update = new \stdClass();
                $update->id = $existing->id;
                $update->name = $name;
                $update->value = $value;
                $update->is_embedding = $isembedding;
                $update->criadex_model_id = $remotemodelid;
                $update->timemodified = time();
                $update->usermodified = $USER->id ?? 0;
                $DB->update_record('local_cria_models', $update);
                $result->updated++;
                continue;
            }

            $insert = new \stdClass();
            $insert->provider_id = $provider->id;
            $insert->name = $name;
            $insert->value = $value;
            $insert->max_tokens = 4092;
            $insert->is_embedding = $isembedding;
            $insert->criadex_model_id = $remotemodelid;
            $insert->prompt_cost = 0;
            $insert->completion_cost = 0;
            $insert->timecreated = time();
            $insert->timemodified = time();
            $insert->usermodified = $USER->id ?? 0;
            $DB->insert_record('local_cria_models', $insert);
            $result->created++;
        }

        if ($removestale) {
            $localmodels = $DB->get_records_select(
                'local_cria_models',
                'criadex_model_id > 0'
            );
            foreach ($localmodels as $localmodel) {
                if (!isset($remoteids[(int) $localmodel->criadex_model_id])) {
                    $DB->delete_records('local_cria_models', ['id' => $localmodel->id]);
                    $result->removed++;
                }
            }
        }

        $result->success = true;
        $result->message = get_string('sync_models_success', 'local_cria', (object) [
            'created' => $result->created,
            'updated' => $result->updated,
            'skipped' => $result->skipped,
            'removed' => $result->removed,
        ]);

        set_config('last_model_sync_at', time(), 'local_cria');

        return $result;
    }

    /**
     * Remove duplicate Moodle model rows that share provider and display name.
     *
     * @return int Number of duplicate rows removed.
     */
    public static function dedupe_local_models(): int {
        global $DB;

        $models = $DB->get_records('local_cria_models', null, 'provider_id ASC, name ASC, id ASC');
        $canonical = [];
        $removed = 0;

        foreach ($models as $model) {
            $name = strtolower(trim((string) $model->name));
            if ($name === '') {
                continue;
            }

            $key = (int) $model->provider_id . '|' . $name;
            if (!isset($canonical[$key])) {
                $canonical[$key] = (int) $model->id;
                continue;
            }

            self::remap_model_references((int) $model->id, $canonical[$key]);
            $DB->delete_records('local_cria_models', ['id' => $model->id]);
            $removed++;
        }

        return $removed;
    }

    /**
     * Merge duplicate generic models in Criadex and dedupe local Moodle rows.
     *
     * @return \stdClass
     */
    public static function dedupe_models(): \stdClass {
        $result = (object) [
            'success' => false,
            'remote_deleted' => 0,
            'local_removed' => 0,
            'message' => '',
        ];

        $response = criadex::dedupe_models();
        if (!api_response::is_success($response)) {
            $result->message = api_response::error_message(
                $response,
                get_string('sync_dedupe_failed', 'local_cria')
            );
            return $result;
        }

        $result->remote_deleted = (int) ($response->deleted_models ?? 0);
        $result->local_removed = self::dedupe_local_models();
        $result->success = true;
        $result->message = get_string('sync_dedupe_success', 'local_cria', (object) [
            'remote' => $result->remote_deleted,
            'local' => $result->local_removed,
        ]);

        return $result;
    }

    /**
     * Point bot model references at the canonical local model row.
     *
     * @param int $fromid
     * @param int $toid
     */
    private static function remap_model_references(int $fromid, int $toid): void {
        global $DB;

        if ($fromid === $toid) {
            return;
        }

        $DB->set_field_select('local_cria_bot', 'model_id', $toid, 'model_id = :fromid', ['fromid' => $fromid]);
        $DB->set_field_select('local_cria_bot', 'embedding_id', $toid, 'embedding_id = :fromid', ['fromid' => $fromid]);
        $DB->set_field_select(
            'local_cria_bot',
            'rerank_model_id',
            $toid,
            'rerank_model_id = :fromid',
            ['fromid' => $fromid]
        );
    }

    /**
     * Run model sync and surface Moodle notifications when requested.
     *
     * @param bool $notify
     * @param bool $removestale
     * @return \stdClass
     */
    public static function run_model_sync(bool $notify = true, bool $removestale = false): \stdClass {
        $result = self::sync_models_from_criadex($removestale);

        if ($notify) {
            if ($result->success) {
                if ($result->created > 0 || $result->updated > 0 || $result->removed > 0) {
                    \core\notification::success($result->message);
                }
            } else {
                \core\notification::warning($result->message);
            }
        }

        return $result;
    }

    /**
     * Build a health report for the sync status dashboard.
     *
     * @return array
     */
    public static function get_health_report(): array {
        global $DB;

        $config = get_config('local_cria');
        $checks = [];

        $checks[] = self::make_check(
            'criadex_url',
            get_string('sync_check_criadex_url', 'local_cria'),
            !empty($config->criadex_url)
        );

        $checks[] = self::make_check(
            'criabot_url',
            get_string('sync_check_criabot_url', 'local_cria'),
            !empty($config->criabot_url)
        );

        $checks[] = self::make_check(
            'api_key',
            get_string('sync_check_api_key', 'local_cria'),
            !empty($config->criadex_api_key)
        );

        $modelsresponse = criadex::list_models();
        $checks[] = self::make_check(
            'criadex_models',
            get_string('sync_check_criadex_models', 'local_cria'),
            api_response::is_success($modelsresponse),
            api_response::error_message($modelsresponse, get_string('sync_check_unreachable', 'local_cria'))
        );

        $remotecount = 0;
        if (api_response::is_success($modelsresponse) && !empty($modelsresponse->models)) {
            $remotecount = count($modelsresponse->models);
        }

        $localwithcriadex = $DB->count_records_select('local_cria_models', 'criadex_model_id > 0');
        $localwithoutcriadex = $DB->count_records_select(
            'local_cria_models',
            'criadex_model_id IS NULL OR criadex_model_id = 0'
        );

        $checks[] = self::make_check(
            'local_models_linked',
            get_string('sync_check_local_models', 'local_cria'),
            $localwithoutcriadex === 0,
            get_string('sync_check_local_models_detail', 'local_cria', (object) [
                'linked' => $localwithcriadex,
                'unlinked' => $localwithoutcriadex,
                'remote' => $remotecount,
            ])
        );

        $botsmissingmodels = self::count_bots_missing_model_links();
        $checks[] = self::make_check(
            'bots_model_links',
            get_string('sync_check_bots_models', 'local_cria'),
            $botsmissingmodels === 0,
            get_string('sync_check_bots_models_detail', 'local_cria', $botsmissingmodels)
        );

        $criabotreachable = false;
        $criabotdetail = get_string('sync_check_unreachable', 'local_cria');
        if (!empty($config->criabot_url)) {
            $about = criabot::bot_about('0');
            $criabotreachable = is_object($about) && isset($about->status);
            if ($criabotreachable) {
                $criabotdetail = get_string('sync_check_criabot_ok', 'local_cria');
            } else {
                $criabotdetail = api_response::error_message($about, get_string('sync_check_unreachable', 'local_cria'));
            }
        }

        $checks[] = self::make_check(
            'criabot',
            get_string('sync_check_criabot', 'local_cria'),
            $criabotreachable,
            $criabotdetail
        );

        $ragflowstatus = ragflow::check_reachable();
        $checks[] = self::make_check(
            'ragflow',
            get_string('sync_check_ragflow', 'local_cria'),
            !empty($ragflowstatus->ok),
            $ragflowstatus->detail ?? get_string('sync_check_unreachable', 'local_cria')
        );

        $providertypes = $DB->get_records_menu('local_cria_providers', null, '', 'id, type');
        $remotetypes = [];
        if (api_response::is_success($modelsresponse) && !empty($modelsresponse->models)) {
            foreach ($modelsresponse->models as $remotemodel) {
                $remotetypes[strtolower($remotemodel->provider_type ?? '')] = true;
            }
        }
        $missingtypes = [];
        foreach (array_keys($remotetypes) as $rtype) {
            if ($rtype === '') {
                continue;
            }
            $found = false;
            foreach ($providertypes as $ptype) {
                if (strtolower($ptype) === $rtype) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingtypes[] = $rtype;
            }
        }
        $checks[] = self::make_check(
            'provider_types',
            get_string('sync_check_provider_types', 'local_cria'),
            empty($missingtypes),
            empty($missingtypes)
                ? get_string('sync_check_provider_types_ok', 'local_cria')
                : get_string('sync_check_provider_types_missing', 'local_cria', implode(', ', $missingtypes))
        );

        $missingbots = self::find_missing_criabot_bots();
        $checks[] = self::make_check(
            'criabot_bots',
            get_string('sync_check_criabot_bots', 'local_cria'),
            empty($missingbots),
            empty($missingbots)
                ? get_string('sync_check_criabot_bots_ok', 'local_cria')
                : get_string('sync_check_criabot_bots_missing', 'local_cria', implode(', ', $missingbots))
        );

        $bottypecount = $DB->count_records('local_cria_type');
        $checks[] = self::make_check(
            'bot_types',
            get_string('sync_check_bot_types', 'local_cria'),
            $bottypecount > 0,
            get_string('sync_check_bot_types_detail', 'local_cria', $bottypecount)
        );

        $lastsync = (int) get_config('local_cria', 'last_model_sync_at');
        $lastsynclabel = $lastsync > 0
            ? userdate($lastsync, get_string('strftimedatetime', 'langconfig'))
            : get_string('never', 'local_cria');

        return [
            'checks' => $checks,
            'last_model_sync' => $lastsynclabel,
            'healthy' => !in_array(false, array_column($checks, 'ok'), true),
        ];
    }

    /**
     * Re-push all bot configurations to Criabot.
     *
     * @return \stdClass pushed, failed, skipped, messages[]
     */
    public static function repush_all_bots(): \stdClass {
        global $DB;

        return self::repush_bot_records($DB->get_records('local_cria_bot'));
    }

    /**
     * @param int $typeid
     * @return \stdClass pushed, failed, skipped, messages[]
     */
    public static function repush_bots_for_type(int $typeid): \stdClass {
        global $DB;

        return self::repush_bot_records($DB->get_records('local_cria_bot', ['bot_type' => $typeid]));
    }

    /**
     * @param array $botrows
     * @return \stdClass
     */
    private static function repush_bot_records(array $botrows): \stdClass {
        global $DB;

        $result = (object) [
            'pushed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'messages' => [],
        ];

        foreach ($botrows as $botrow) {
            $bot = new bot($botrow->id);
            if (!$bot->has_valid_model_links()) {
                $result->skipped++;
                $result->messages[] = get_string(
                    'sync_repush_skipped_models',
                    'local_cria',
                    (object) ['bot' => $botrow->id]
                );
                continue;
            }

            if ($bot->use_bot_server()) {
                $intents = $DB->get_records('local_cria_intents', ['bot_id' => $botrow->id, 'published' => 1]);
                if (empty($intents)) {
                    $result->skipped++;
                    $result->messages[] = get_string(
                        'sync_repush_skipped_no_intents',
                        'local_cria',
                        (object) ['bot' => $botrow->id]
                    );
                    continue;
                }
                foreach ($intents as $intentrow) {
                    $intent = new intent($intentrow->id);
                    $label = $botrow->id . '-' . $intentrow->id;
                    if ($intent->update_intent_on_bot_server(false)) {
                        $result->pushed++;
                    } else {
                        $result->failed++;
                        $result->messages[] = get_string(
                            'sync_repush_failed_bot',
                            'local_cria',
                            (object) ['name' => $label]
                        );
                    }
                }
            } else if ($bot->update_bot_on_bot_server(0, false)) {
                $result->pushed++;
            } else {
                $result->failed++;
                $result->messages[] = get_string(
                    'sync_repush_failed_bot',
                    'local_cria',
                    (object) ['name' => (string) $botrow->id]
                );
            }
        }

        return $result;
    }

    /**
     * Verify Moodle bots exist on Criabot for published intents.
     *
     * @return array List of missing bot names.
     */
    public static function find_missing_criabot_bots(): array {
        global $DB;

        $missing = [];
        $bots = $DB->get_records('local_cria_bot');

        foreach ($bots as $botrow) {
            $bot = new bot($botrow->id);
            $publishedintents = $DB->get_records('local_cria_intents', ['bot_id' => $botrow->id, 'published' => 1]);

            if (!empty($publishedintents) && $bot->use_bot_server()) {
                foreach ($publishedintents as $intentrow) {
                    $botname = $botrow->id . '-' . $intentrow->id;
                    $about = criabot::bot_about((string) $botname);
                    if (!api_response::is_success($about) && (int) ($about->status ?? 0) === 404) {
                        $missing[] = $botname;
                    }
                }
                continue;
            }

            if (!empty($publishedintents)) {
                foreach ($publishedintents as $intentrow) {
                    $botname = $botrow->id . '-' . $intentrow->id;
                    $about = criabot::bot_about((string) $botname);
                    if (!api_response::is_success($about) && (int) ($about->status ?? 0) === 404) {
                        $missing[] = $botname;
                    }
                }
                continue;
            }

            if (empty($botrow->bot_type)) {
                $missing[] = get_string('sync_missing_bot_type', 'local_cria', $botrow->id);
                continue;
            }

            $about = criabot::bot_about((string) $botrow->id);
            if (!api_response::is_success($about) && (int) ($about->status ?? 0) === 404) {
                $missing[] = (string) $botrow->id;
            }
        }

        return $missing;
    }

    /**
     * Count bots that reference models without a Criadex model id.
     *
     * @return int
     */
    public static function count_bots_missing_model_links(): int {
        global $DB;

        $bots = $DB->get_records('local_cria_bot');
        $count = 0;

        foreach ($bots as $botrow) {
            $bot = new bot($botrow->id);
            if (!$bot->has_valid_model_links()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param string $id
     * @param string $label
     * @param bool $ok
     * @param string $detail
     * @return array
     */
    private static function make_check(string $id, string $label, bool $ok, string $detail = ''): array {
        return [
            'id' => $id,
            'label' => $label,
            'ok' => $ok,
            'status' => $ok
                ? get_string('sync_status_ok', 'local_cria')
                : get_string('sync_status_issue', 'local_cria'),
            'detail' => $detail,
        ];
    }
}
