<?php

/**
 * This file is part of Cria.
 * Cria is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Cria is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with Cria. If not, see <https://www.gnu.org/licenses/>.
 *
 * Legacy entry point — redirect to provider-aware model editor (Criadex-backed).
 *
 * @package    local_cria
 * @copyright  2024 onwards York University (https://yorku.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$provider_id = optional_param('provider_id', 0, PARAM_INT);

require_login(1, false);

$params = ['id' => $id];
if ($provider_id) {
    $params['provider_id'] = $provider_id;
}

if ($id) {
    global $DB;
    $model = $DB->get_record('local_cria_models', ['id' => $id], 'provider_id', MUST_EXIST);
    $provider = $DB->get_record('local_cria_providers', ['id' => $model->provider_id], 'idnumber', MUST_EXIST);
    if (!empty($provider->idnumber)) {
        $params['idnumber'] = $provider->idnumber;
    }
}

redirect(new moodle_url('/local/cria/model_router.php', $params));
