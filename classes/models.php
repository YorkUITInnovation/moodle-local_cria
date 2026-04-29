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


/*
 * Author: Admin User
 * Create Date: 26-08-2023
 * License: LGPL 
 * 
 */
namespace local_cria;

use local_cria\provider;

class models {

	/**
	 *
	 *@var string
	 */
	private $results;

	/**
	 *
	 *@global \moodle_database $DB
	 */
	public function __construct() {
	    global $DB;
	    $this->results = $DB->get_records('local_cria_models', [], 'name ASC');
	}

    /**
     * Sync model definitions from Criadex into Moodle table.
     *
     * This keeps dropdowns populated even when model/provider setup is done
     * outside Moodle (e.g., Azure or other provider setup in backend services).
     */
    public static function sync_from_criadex(): void
    {
        global $DB, $USER;

        $response = \local_cria\criadex::list_models();
        if (!isset($response->status) || (int)$response->status !== 200 || empty($response->models)) {
            return;
        }

        $providers = $DB->get_records('local_cria_providers');
        $providers_by_type = [];
        foreach ($providers as $provider) {
            $ptype = strtolower($provider->type ?? 'azure');
            if (!isset($providers_by_type[$ptype])) {
                $providers_by_type[$ptype] = $provider;
            }
        }

        foreach ($response->models as $remote_model) {
            $provider_type = strtolower($remote_model->provider_type ?? 'azure');
            if (!isset($providers_by_type[$provider_type])) {
                continue;
            }

            $provider = $providers_by_type[$provider_type];
            $api_model = trim((string)($remote_model->api_model ?? ''));
            $api_deployment = trim((string)($remote_model->api_deployment ?? ''));
            $api_resource = trim((string)($remote_model->api_resource ?? ''));

            $name = $api_model;
            if ($name === '') {
                $name = $api_deployment !== '' ? $api_deployment : ('model-' . (int)$remote_model->id);
            }

            $is_embedding = 0;
            if ($api_model !== '' && stripos($api_model, 'embedding') !== false) {
                $is_embedding = 1;
            }

            $value = json_encode([
                'api_model' => $api_model,
                'api_resource' => $api_resource,
                'api_deployment' => $api_deployment,
                'provider_type' => $provider_type,
                'config' => $remote_model->config ?? null,
            ]);

            $existing = $DB->get_record('local_cria_models', [
                'provider_id' => $provider->id,
                'criadex_model_id' => (int)$remote_model->id,
            ]);

            if ($existing) {
                $update = new \stdClass();
                $update->id = $existing->id;
                $update->name = $name;
                $update->value = $value;
                $update->is_embedding = $is_embedding;
                $update->timemodified = time();
                $update->usermodified = $USER->id ?? 0;
                $DB->update_record('local_cria_models', $update);
                continue;
            }

            $insert = new \stdClass();
            $insert->provider_id = $provider->id;
            $insert->name = $name;
            $insert->value = $value;
            $insert->max_tokens = 4092;
            $insert->is_embedding = $is_embedding;
            $insert->criadex_model_id = (int)$remote_model->id;
            $insert->prompt_cost = 0;
            $insert->completion_cost = 0;
            $insert->timecreated = time();
            $insert->timemodified = time();
            $insert->usermodified = $USER->id ?? 0;
            $DB->insert_record('local_cria_models', $insert);
        }
    }

	/**
	  * Get records
	 */
	public function get_records() {
	    return $this->results;
	}

    /**
     * @return array
     */
    public function get_formated_records() {
        $results = $this->results;
        $models = [];
        $i = 0;
        foreach($results as $r) {
            $MODEL = new model($r->id);
            $models[$i]['id'] = $MODEL->get_id();
            $models[$i]['name'] = $MODEL->get_name();
            $models[$i]['value'] = $MODEL->get_value();
            $models[$i]['criadex_model_id'] = $MODEL->get_criadex_model_id();
            $models[$i]['provider_id'] = $MODEL->get_provider_id();
            $models[$i]['provider_idnumber'] = $MODEL->get_provider_idnumber();
            $models[$i]['provider_name'] = $MODEL->get_provider_name();
            $models[$i]['usermodified'] = $MODEL->get_usermodified();
            $models[$i]['timecreated'] = $MODEL->get_timecreated();
            $models[$i]['timemodified'] = $MODEL->get_timemodified();
            unset($MODEL);
            $i++;

        }

        return $models;

    }

	/**
	  * Array to be used for selects
	  * Defaults used key = record id, value = name 
	  * Modify as required. 
	 */
	public function get_select_array($embedding = false, $rerank = false) {
        global $DB;
	    $array = [
	        '' => get_string('select', 'local_cria')
	      ];

        if ($embedding && !$rerank) {
           $results =  $DB->get_records('local_cria_models', ['is_embedding' => 1], 'name ASC');
        } else if (!$embedding && $rerank) {
            $rerank_providers = $DB->get_records('local_cria_providers', ['type' => 'cohere']);
            $results = [];
            foreach ($rerank_providers as $rp) {
                $provider_models = $DB->get_records('local_cria_models', ['provider_id' => $rp->id], 'name ASC');
                $results = $results + $provider_models;
            }
        } else {
            $results = $DB->get_records('local_cria_models', ['is_embedding' => 0], 'name ASC');
        }
	      foreach($results as $r) {
	            $array[$r->id] = $r->name;
	      }
	    return $array;
	}

}