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

use local_cria\provider;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/config.php');

class generic_model_form extends \moodleform
{

    protected function definition()
    {
        global $DB;

        $formdata = $this->_customdata['formdata'];
        $mform = &$this->_form;

        $PROVIDER = new provider($formdata->provider_id);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'provider_id');
        $mform->setType('provider_id', PARAM_INT);

        $mform->addElement(
            'header',
            'model_form',
            $PROVIDER->get_name() . ' ' . get_string('model', 'local_cria')
        );

        $mform->addElement('text', 'name', get_string('name', 'local_cria'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'api_base_url', get_string('api_base_url', 'local_cria'));
        $mform->setType('api_base_url', PARAM_URL);
        $mform->addHelpButton('api_base_url', 'api_base_url', 'local_cria');

        $mform->addElement('passwordunmask', 'api_key', get_string('api_key', 'local_cria'));
        $mform->setType('api_key', PARAM_TEXT);

        $llm_models = $PROVIDER->get_llm_models_array();
        if (!empty($llm_models) && count($llm_models) > 1) {
            $mform->addElement('select', 'api_model', get_string('api_model', 'local_cria'), $llm_models);
        } else {
            $mform->addElement('text', 'api_model', get_string('api_model', 'local_cria'));
        }
        $mform->setType('api_model', PARAM_TEXT);

        $mform->addElement('text', 'max_tokens', get_string('max_tokens', 'local_cria'));
        $mform->setType('max_tokens', PARAM_INT);
        $mform->setDefault('max_tokens', 4096);

        $mform->addElement('text', 'prompt_cost', get_string('prompt_cost', 'local_cria'));
        $mform->setType('prompt_cost', PARAM_FLOAT);

        $mform->addElement('text', 'completion_cost', get_string('completion_cost', 'local_cria'));
        $mform->setType('completion_cost', PARAM_FLOAT);

        $mform->addElement('selectyesno', 'is_embedding', get_string('is_embedding', 'local_cria'));
        $mform->setDefault('is_embedding', 0);

        $this->add_action_buttons();
        $this->set_data($formdata);
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
