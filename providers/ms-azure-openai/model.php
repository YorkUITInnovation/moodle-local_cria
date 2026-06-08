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

require_once($CFG->dirroot . "/local/cria/providers/ms-azure-openai/model_form.php");

use local_cria\base;
use local_cria\model;
use local_cria\criadex;

global $CFG, $OUTPUT, $USER, $PAGE, $DB, $SITE;

$id = optional_param('id', 0, PARAM_INT);

$context = CONTEXT_SYSTEM::instance();

$MODEL = new model($id);

require_login(1, false);

if ($id) {
    $formdata = $MODEL->get_result();
    $values = json_decode($formdata->value);
    $formdata->api_resource = $values->api_resource ?? $values->api_base_url ?? '';
    $formdata->api_version = $values->api_version ?? '';
    $formdata->api_key = $values->api_key ?? '';
    $formdata->api_deployment = $values->api_deployment ?? '';
    $formdata->api_model = $values->api_model ?? '';
} else {
    $formdata = new stdClass();
    $provider = $DB->get_record('local_cria_providers', ['idnumber' => 'ms-azure-openai']);
    $formdata->provider_id = $provider->id;
}


$mform = new \local_cria\ms_azure_openai_model_form(null, array('formdata' => $formdata));
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
    redirect($CFG->wwwroot . '/local/cria/bot_models.php');
} else if ($data = $mform->get_data()) {

    $value = new stdClass();
    $value->api_resource = $data->api_resource;
    $value->api_version = $data->api_version;
    $value->api_key = $data->api_key;
    $value->api_deployment = $data->api_deployment;
    $value->api_model = $data->api_model;
    $data->value = json_encode($value);

    unset($data->api_resource);
    unset($data->api_version);
    unset($data->api_key);
    unset($data->api_deployment);
    unset($data->api_model);

    if ($data->id) {
        $data->usermodified = $USER->id;
        $data->timemodified = time();
        $DB->update_record('local_cria_models', $data);
        $id = $data->id;
        $params = $DB->get_record('local_cria_models', ['id' => $id]);
        $MODEL_OBJ = new model($id);
        $results = criadex::update_model($params->criadex_model_id, $data->value, $MODEL_OBJ->get_provider_type());
        if (\local_cria\api_response::is_success($results)) {
            redirect($CFG->wwwroot . '/local/cria/bot_models.php');
        } else {
            \core\notification::error(\local_cria\api_response::error_message($results));
        }
    } else {
        $data->usermodified = $USER->id;
        $data->timemodified = time();
        $data->timecreated = time();
        $id  = $DB->insert_record('local_cria_models', $data);
        $provider_type = (new \local_cria\provider($data->provider_id))->get_type();
        $results = criadex::create_model($data->value, $provider_type);
        if (\local_cria\api_response::is_success($results)) {
            $params = new stdClass();
            $params->id = $id;
            $params->criadex_model_id = $results->model->id ?? 0;
            $DB->update_record('local_cria_models', $params);
            redirect($CFG->wwwroot . '/local/cria/bot_models.php');
        } else {
            $DB->delete_records('local_cria_models', ['id' => $id]);
            \core\notification::error(\local_cria\api_response::error_message($results));
        }
    }
} else {

    $mform->set_data($mform);
}


base::page(
    new moodle_url('/local/cria/providers/ms-azure-openai/model.php', ['id' => $id]),
    'MS Azure OpenAI ' . get_string('model', 'local_cria'),
    'MS Azure OpenAI ' . get_string('model', 'local_cria'),
    $context,
    'standard'
);


echo $OUTPUT->header();
//**********************
//*** DISPLAY HEADER ***
//

$mform->display();


//**********************
//*** DISPLAY FOOTER ***
//**********************
echo $OUTPUT->footer();
?>