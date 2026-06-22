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
     * Trigger Ragflow tenant model sync in Criadex before reading /models/list.
     *
     * @return void
     */
    public static function sync_ragflow_models_in_criadex(): void {
        criadex::sync_ragflow_models();
    }

    /**
     * Whether a remote Criadex model entry is configured and usable.
     *
     * @param \stdClass $remotemodel
     * @return bool
     */
    public static function is_usable_remote_model(\stdClass $remotemodel): bool {
        if (property_exists($remotemodel, 'is_usable')) {
            return !empty($remotemodel->is_usable);
        }
        return true;
    }

    /**
     * Resolve display name for a remote model row.
     *
     * @param \stdClass $remotemodel
     * @return string
     */
    public static function resolve_remote_model_name(\stdClass $remotemodel): string {
        $display = trim((string) ($remotemodel->display_name ?? ''));
        if ($display !== '') {
            return $display;
        }

        $apimodel = trim((string) ($remotemodel->api_model ?? ''));
        if ($apimodel !== '') {
            return $apimodel;
        }

        $apideployment = trim((string) ($remotemodel->api_deployment ?? ''));
        if ($apideployment !== '') {
            return $apideployment;
        }

        return 'model-' . (int) ($remotemodel->id ?? 0);
    }

    /**
     * Resolve embedding / rerank flags from remote model metadata.
     *
     * @param \stdClass $remotemodel
     * @param string $apimodel
     * @return \stdClass Properties is_embedding, is_rerank (ints 0/1)
     */
    public static function resolve_remote_model_roles(\stdClass $remotemodel, string $apimodel): \stdClass {
        $config = $remotemodel->config ?? null;
        if (is_object($config)) {
            $config = (array) $config;
        }
        if (!is_array($config)) {
            $config = [];
        }

        $modeltype = strtolower(trim((string) ($remotemodel->model_type ?? ($config['model_type'] ?? ''))));
        $providertype = strtolower(trim((string) ($remotemodel->provider_type ?? '')));

        $roles = (object) [
            'is_embedding' => 0,
            'is_rerank' => 0,
        ];

        if (in_array($modeltype, ['embedding', 'embed'], true)) {
            $roles->is_embedding = 1;
            return $roles;
        }
        if ($modeltype === 'rerank' || $providertype === 'cohere') {
            $roles->is_rerank = 1;
            return $roles;
        }
        if ($apimodel !== '' && stripos($apimodel, 'embedding') !== false) {
            $roles->is_embedding = 1;
        }

        return $roles;
    }

    /**
     * Ensure local provider rows exist for each provider type returned by Criadex.
     *
     * @return int Number of provider rows created.
     */
    public static function ensure_providers_for_remote_types(): int {
        global $DB, $USER;

        self::sync_ragflow_models_in_criadex();

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
            if (!self::is_usable_remote_model($remotemodel)) {
                continue;
            }
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

        self::sync_ragflow_models_in_criadex();

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
            if (!self::is_usable_remote_model($remotemodel)) {
                $result->skipped++;
                continue;
            }

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

            $name = self::resolve_remote_model_name($remotemodel);

            $roles = self::resolve_remote_model_roles($remotemodel, $apimodel);
            $isembedding = (int) $roles->is_embedding;
            $isrerank = (int) $roles->is_rerank;

            $config = $remotemodel->config ?? null;
            if (is_object($config)) {
                $config = (array) $config;
            }
            if (!is_array($config)) {
                $config = [];
            }
            if (!empty($remotemodel->model_type)) {
                $config['model_type'] = $remotemodel->model_type;
            }

            $value = json_encode([
                'api_model' => $apimodel,
                'api_resource' => $apiresource,
                'api_deployment' => $apideployment,
                'provider_type' => $providertype,
                'model_type' => $config['model_type'] ?? ($isrerank ? 'rerank' : ($isembedding ? 'embedding' : 'chat')),
                'is_rerank' => $isrerank,
                'config' => $config,
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
     * Remove local model rows that are not linked to Criadex and not used by any bot.
     *
     * @return \stdClass
     */
    public static function cleanup_unlinked_models(): \stdClass {
        global $DB;

        $result = (object) [
            'success' => false,
            'removed' => 0,
            'blocked' => [],
            'message' => '',
        ];

        self::sync_models_from_criadex(false);

        $unlinked = $DB->get_records_select(
            'local_cria_models',
            'criadex_model_id IS NULL OR criadex_model_id = 0',
            null,
            'id ASC'
        );

        foreach ($unlinked as $model) {
            $modelid = (int) $model->id;
            if (self::is_model_referenced($modelid)) {
                $result->blocked[] = $modelid . ' (' . ($model->name ?? '') . ')';
                continue;
            }
            $DB->delete_records('local_cria_models', ['id' => $modelid]);
            $result->removed++;
        }

        $result->success = true;
        $result->message = get_string('sync_cleanup_unlinked_success', 'local_cria', (object) [
            'removed' => $result->removed,
            'blocked' => count($result->blocked),
        ]);

        return $result;
    }

    /**
     * Run safe automated repairs for common drift (models + Criabot push).
     *
     * @return \stdClass
     */
    public static function run_auto_repair(): \stdClass {
        $result = (object) [
            'success' => true,
            'messages' => [],
        ];

        $sync = self::sync_models_from_criadex(false);
        if ($sync->success) {
            $result->messages[] = $sync->message;
        } else {
            $result->success = false;
            $result->messages[] = $sync->message;
        }

        $cleanup = self::cleanup_unlinked_models();
        $result->messages[] = $cleanup->message;
        if (!empty($cleanup->blocked)) {
            $result->messages[] = get_string(
                'sync_cleanup_unlinked_blocked_detail',
                'local_cria',
                implode(', ', $cleanup->blocked)
            );
        }

        $localdeduped = self::dedupe_local_models();
        if ($localdeduped > 0) {
            $result->messages[] = get_string('sync_local_dedupe_removed', 'local_cria', $localdeduped);
        }

        $repush = self::repush_all_bots();
        if ($repush->pushed > 0 || $repush->failed > 0 || $repush->skipped > 0) {
            $result->messages[] = get_string('sync_repush_bots_partial', 'local_cria', $repush);
        }

        return $result;
    }

    /**
     * @param int $modelid
     * @return bool
     */
    private static function is_model_referenced(int $modelid): bool {
        global $DB;

        if ($modelid <= 0) {
            return false;
        }

        return $DB->record_exists('local_cria_bot', ['model_id' => $modelid])
            || $DB->record_exists('local_cria_bot', ['embedding_id' => $modelid])
            || $DB->record_exists('local_cria_bot', ['rerank_model_id' => $modelid]);
    }

    /**
     * @param array $botids
     * @return array
     */
    private static function build_bot_edit_items(array $botids): array {
        $items = [];
        foreach ($botids as $botid) {
            $botid = (int) $botid;
            if ($botid <= 0) {
                continue;
            }
            $items[] = [
                'url' => (new \moodle_url('/local/cria/edit_bot.php', ['bot_id' => $botid]))->out(false),
                'label' => get_string('sync_bot_edit_link', 'local_cria', $botid),
            ];
        }
        return $items;
    }

    /**
     * @return array
     */
    private static function find_unlinked_model_labels(): array {
        global $DB;

        $models = $DB->get_records_select(
            'local_cria_models',
            'criadex_model_id IS NULL OR criadex_model_id = 0',
            null,
            'id ASC'
        );
        $labels = [];
        foreach ($models as $model) {
            $labels[] = $model->id . ' (' . ($model->name ?? '') . ')';
        }
        return $labels;
    }

    /**
     * @param string $checkid
     * @param array $context
     * @return array
     */
    private static function remediation_for_check(string $checkid, array $context = []): array {
        global $CFG;

        $settingsurl = (new \moodle_url('/admin/settings.php', ['section' => 'local_cria_settings']))->out(false);
        $syncurl = (new \moodle_url('/local/cria/sync_status.php'))->out(false);

        switch ($checkid) {
            case 'criadex_url':
            case 'criabot_url':
            case 'api_key':
                return [
                    'solution' => get_string('sync_solution_settings', 'local_cria'),
                    'actionurl' => $settingsurl,
                    'actionlabel' => get_string('sync_action_open_settings', 'local_cria'),
                ];
            case 'criadex_models':
            case 'criabot':
            case 'ragflow':
                return [
                    'solution' => get_string('sync_solution_service_unreachable', 'local_cria'),
                    'actionurl' => $settingsurl,
                    'actionlabel' => get_string('sync_action_open_settings', 'local_cria'),
                ];
            case 'ragflow_credentials':
                return [
                    'solution' => get_string('sync_solution_ragflow_credentials', 'local_cria'),
                    'actionurl' => $settingsurl,
                    'actionlabel' => get_string('sync_action_open_settings', 'local_cria'),
                ];
            case 'local_models_linked':
                $solution = get_string('sync_solution_unlinked_models', 'local_cria');
                if (!empty($context['unlinkedlabels'])) {
                    $solution .= ' ' . get_string(
                        'sync_solution_unlinked_models_list',
                        'local_cria',
                        implode(', ', $context['unlinkedlabels'])
                    );
                }
                return [
                    'solution' => $solution,
                    'actionurl' => (new \moodle_url('/local/cria/sync_status.php', [
                        'action' => 'cleanupunlinked',
                        'sesskey' => sesskey(),
                    ]))->out(false),
                    'actionlabel' => get_string('sync_action_cleanup_unlinked', 'local_cria'),
                ];
            case 'bots_model_links':
                return [
                    'solution' => get_string('sync_solution_bots_model_links', 'local_cria'),
                    'actionurl' => (new \moodle_url('/local/cria/bot_models.php'))->out(false),
                    'actionlabel' => get_string('sync_action_open_models', 'local_cria'),
                ];
            case 'criabot_bots':
                return [
                    'solution' => get_string('sync_solution_missing_criabot_bots', 'local_cria'),
                    'actionurl' => (new \moodle_url('/local/cria/sync_status.php', [
                        'action' => 'repushbots',
                        'sesskey' => sesskey(),
                    ]))->out(false),
                    'actionlabel' => get_string('sync_action_repush_bots', 'local_cria'),
                ];
            case 'bot_type_assigned':
                return [
                    'solution' => get_string('sync_solution_missing_bot_type', 'local_cria'),
                    'items' => self::build_bot_edit_items($context['botids'] ?? []),
                ];
            case 'provider_types':
                return [
                    'solution' => get_string('sync_solution_provider_types', 'local_cria'),
                    'actionurl' => (new \moodle_url('/local/cria/sync_status.php', [
                        'action' => 'syncmodels',
                        'sesskey' => sesskey(),
                    ]))->out(false),
                    'actionlabel' => get_string('sync_action_pull_models', 'local_cria'),
                ];
            case 'bot_types':
                return [
                    'solution' => get_string('sync_solution_no_bot_types', 'local_cria'),
                    'actionurl' => (new \moodle_url('/local/cria/bot_types.php'))->out(false),
                    'actionlabel' => get_string('sync_action_manage_bot_types', 'local_cria'),
                ];
            default:
                return [
                    'solution' => get_string('sync_solution_generic', 'local_cria', $syncurl),
                ];
        }
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
        $usablecount = 0;
        if (api_response::is_success($modelsresponse) && !empty($modelsresponse->models)) {
            $remotecount = count($modelsresponse->models);
            foreach ($modelsresponse->models as $remotemodel) {
                if (self::is_usable_remote_model($remotemodel)) {
                    $usablecount++;
                }
            }
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
                'usable' => $usablecount,
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

        $ragflowcredentials = ragflow::check_credentials();
        $checks[] = self::make_check(
            'ragflow_credentials',
            get_string('sync_check_ragflow_credentials', 'local_cria'),
            !empty($ragflowcredentials->ok),
            $ragflowcredentials->detail ?? get_string('sync_check_unreachable', 'local_cria')
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

        $botsmissingtype = self::find_bots_missing_bot_type();
        $checks[] = self::make_check(
            'bot_type_assigned',
            get_string('sync_check_bot_type_assigned', 'local_cria'),
            empty($botsmissingtype),
            empty($botsmissingtype)
                ? get_string('sync_check_bot_type_assigned_ok', 'local_cria')
                : get_string('sync_check_bot_type_assigned_missing', 'local_cria', implode(', ', $botsmissingtype))
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

        $unlinkedlabels = self::find_unlinked_model_labels();
        $remediationcontext = [
            'local_models_linked' => ['unlinkedlabels' => $unlinkedlabels],
            'bot_type_assigned' => ['botids' => $botsmissingtype],
        ];

        foreach ($checks as $index => $check) {
            if (!empty($check['ok'])) {
                continue;
            }
            $context = $remediationcontext[$check['id']] ?? [];
            $checks[$index] = self::apply_remediation($check, self::remediation_for_check($check['id'], $context));
        }

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
     * Bots saved without a bot type cannot push to Criabot correctly.
     *
     * @return array List of bot ids missing bot_type.
     */
    public static function find_bots_missing_bot_type(): array {
        global $DB;

        $missing = [];
        $bots = $DB->get_records_select('local_cria_bot', 'bot_type IS NULL OR bot_type = 0');

        foreach ($bots as $botrow) {
            $missing[] = (string) $botrow->id;
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
     * @param array $check
     * @param array $remediation
     * @return array
     */
    private static function apply_remediation(array $check, array $remediation): array {
        if (!empty($remediation['solution'])) {
            $check['solution'] = $remediation['solution'];
        }
        if (!empty($remediation['actionurl'])) {
            $check['has_action'] = true;
            $check['actionurl'] = $remediation['actionurl'];
            $check['actionlabel'] = $remediation['actionlabel'] ?? '';
        }
        if (!empty($remediation['items'])) {
            $check['has_items'] = true;
            $check['items'] = $remediation['items'];
        }
        return $check;
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
