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
	    $this->results = self::get_usable_records();
	}

    /**
     * Sync model definitions from Criadex into Moodle table.
     *
     * This keeps dropdowns populated even when model/provider setup is done
     * outside Moodle (e.g., Azure or other provider setup in backend services).
     */
    public static function sync_from_criadex(): void
    {
        sync_manager::sync_models_from_criadex();
    }

    /**
     * Return synced models that are linked to Criadex and usable for bots.
     *
     * @return array
     */
    public static function get_usable_records(): array {
        global $DB;

        $records = $DB->get_records_select(
            'local_cria_models',
            'criadex_model_id > 0',
            null,
            'name ASC'
        );

        $usable = [];
        foreach ($records as $record) {
            if (self::is_usable_record($record)) {
                $usable[$record->id] = $record;
            }
        }

        return $usable;
    }

    /**
     * @param \stdClass $record
     * @return bool
     */
    public static function is_usable_record(\stdClass $record): bool {
        if ((int) ($record->criadex_model_id ?? 0) <= 0) {
            return false;
        }

        $value = json_decode((string) ($record->value ?? ''), true);
        if (!is_array($value)) {
            return true;
        }

        if (!empty($value['is_rerank'])) {
            return true;
        }
        if (!empty($record->is_embedding)) {
            return true;
        }

        $modeltype = strtolower((string) ($value['model_type'] ?? ''));
        if ($modeltype === 'rerank') {
            return true;
        }

        $providertype = strtolower((string) ($value['provider_type'] ?? ''));
        if ($providertype === 'ragflow') {
            return true;
        }

        if ($providertype === 'azure') {
            $resource = (string) ($value['api_resource'] ?? '');
            if ($resource !== '' && str_starts_with($resource, 'your-resource')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param bool $embedding
     * @param bool $rerank
     * @return array
     */
    private static function filter_records_by_role(bool $embedding, bool $rerank): array {
        $records = self::get_usable_records();
        $filtered = [];

        foreach ($records as $record) {
            $value = json_decode((string) ($record->value ?? ''), true);
            if (!is_array($value)) {
                $value = [];
            }

            $isrerank = !empty($value['is_rerank'])
                || strtolower((string) ($value['model_type'] ?? '')) === 'rerank';
            $isembedding = !empty($record->is_embedding)
                || in_array(strtolower((string) ($value['model_type'] ?? '')), ['embedding', 'embed'], true);

            if ($embedding && !$rerank && $isembedding) {
                $filtered[$record->id] = $record;
                continue;
            }

            if (!$embedding && $rerank && $isrerank) {
                $filtered[$record->id] = $record;
                continue;
            }

            if (!$embedding && !$rerank && !$isembedding && !$isrerank) {
                $filtered[$record->id] = $record;
            }
        }

        return $filtered;
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
            $value = json_decode((string) $MODEL->get_value(), true);
            if (!is_array($value)) {
                $value = [];
            }
            $models[$i]['id'] = $MODEL->get_id();
            $models[$i]['name'] = $MODEL->get_name();
            $models[$i]['value'] = $MODEL->get_value();
            $models[$i]['criadex_model_id'] = $MODEL->get_criadex_model_id();
            $models[$i]['provider_id'] = $MODEL->get_provider_id();
            $models[$i]['provider_idnumber'] = $MODEL->get_provider_idnumber();
            $models[$i]['provider_name'] = $MODEL->get_provider_name();
            $modeltype = strtolower((string) ($value['model_type'] ?? 'chat'));
            if ($modeltype === 'embedding' || $modeltype === 'embed') {
                $modeltypelabel = get_string('model_type_embedding', 'local_cria');
            } else if ($modeltype === 'rerank') {
                $modeltypelabel = get_string('model_type_rerank', 'local_cria');
            } else {
                $modeltypelabel = get_string('model_type_chat', 'local_cria');
            }
            $models[$i]['model_type'] = $modeltypelabel;
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
	    $array = [
	        '' => get_string('select', 'local_cria')
	      ];

        $results = self::filter_records_by_role((bool) $embedding, (bool) $rerank);
	      foreach($results as $r) {
	            $array[$r->id] = $r->name;
	      }
	    return $array;
	}

}
