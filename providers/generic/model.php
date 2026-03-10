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

require_once("../../../../config.php");
global $CFG;
require_once($CFG->dirroot . "/local/cria/providers/generic/model_form.php");

use local_cria\base;
use local_cria\model;
use local_cria\provider;
use local_cria\criadex;

global $CFG, $OUTPUT, $USER, $PAGE, $DB, $SITE;

$id = optional_param('id', 0, PARAM_INT);
$provider_id = optional_param('provider_id', 0, PARAM_INT);

$context = CONTEXT_SYSTEM::instance();

$MODEL = new model($id);

require_login(1, false);

if ($id) {
    $formdata = $MODEL->get_result();
    $values = json_decode($formdata->value);
    $formdata->api_base_url = $values->api_base_url ?? '';
    $formdata->api_key = $values->api_key ?? '';
    $formdata->api_model = $values->api_model ?? '';
    $provider_id = $formdata->provider_id;
} else {
    $formdata = new stdClass();
    if ($provider_id) {
        $formdata->provider_id = $provider_id;
    }
}

$PROVIDER = new provider($provider_id);

$mform = new \local_cria\generic_model_form(null, array('formdata' => $formdata));
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/local/cria/bot_models.php');
} else if ($data = $mform->get_data()) {

    $value = new stdClass();
    $value->api_base_url = $data->api_base_url ?? '';
    $value->api_key = $data->api_key ?? '';
    $value->api_model = $data->api_model ?? '';
    $data->value = json_encode($value);

    unset($data->api_base_url);
    unset($data->api_key);
    unset($data->api_model);

    $provider_type = $PROVIDER->get_type();

    if ($data->id) {
        $data->usermodified = $USER->id;
        $data->timemodified = time();
        $DB->update_record('local_cria_models', $data);
        $id = $data->id;
        $params = $DB->get_record('local_cria_models', ['id' => $id]);
        $results = criadex::update_model($params->criadex_model_id, $data->value, $provider_type);
        if (isset($results->status) && $results->status == '200') {
            redirect($CFG->wwwroot . '/local/cria/bot_models.php');
        } else {
            $msg = isset($results->status) ? $results->status : 'Unknown error';
            $msg .= isset($results->message) ? "\n" . $results->message : '';
            $msg .= isset($results->code) ? "\n" . $results->code : '';
            \core\notification::error($msg);
        }
    } else {
        $data->usermodified = $USER->id;
        $data->timemodified = time();
        $data->timecreated = time();
        $id = $DB->insert_record('local_cria_models', $data);
        $results = criadex::create_model($data->value, $provider_type);
        if (isset($results->status) && $results->status == '200') {
            $params = new stdClass();
            $params->id = $id;
            $params->criadex_model_id = $results->model->id ?? 0;
            $DB->update_record('local_cria_models', $params);
            redirect($CFG->wwwroot . '/local/cria/bot_models.php');
        } else {
            $msg = isset($results->status) ? $results->status : 'Criadex route not available for provider type: ' . $provider_type;
            $msg .= isset($results->message) ? "\n" . $results->message : '';
            $msg .= isset($results->code) ? "\n" . $results->code : '';
            \core\notification::error($msg);
        }
    }
} else {
    $mform->set_data($mform);
}


base::page(
    new moodle_url('/local/cria/providers/generic/model.php', ['id' => $id, 'provider_id' => $provider_id]),
    $PROVIDER->get_name() . ' ' . get_string('model', 'local_cria'),
    $PROVIDER->get_name() . ' ' . get_string('model', 'local_cria'),
    $context,
    'standard'
);


echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
