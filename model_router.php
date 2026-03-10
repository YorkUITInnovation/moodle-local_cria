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

require_once('../../config.php');

$id = optional_param('id', 0, PARAM_INT);
$idnumber = optional_param('idnumber', '', PARAM_TEXT);
$provider_id = optional_param('provider_id', 0, PARAM_INT);

require_login(1, false);

$provider_page = $CFG->dirroot . '/local/cria/providers/' . $idnumber . '/model.php';

if ($idnumber && file_exists($provider_page)) {
    $params = ['id' => $id];
    redirect(new moodle_url('/local/cria/providers/' . $idnumber . '/model.php', $params));
} else {
    $params = ['id' => $id, 'provider_id' => $provider_id];
    redirect(new moodle_url('/local/cria/providers/generic/model.php', $params));
}
