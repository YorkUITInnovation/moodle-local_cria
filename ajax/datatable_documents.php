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


use local_cria\datatables;
use local_cria\file;

require_once('../../../config.php');

global $CFG, $DB, $USER;

$FILE = new file();
$context = context_system::instance();
require_login(1, false);

// get Values from Datatables
$draw = optional_param('draw', 1, PARAM_INT);
$start = optional_param('start', 0, PARAM_INT);
$length = optional_param('length', 25, PARAM_INT);
$deleted = optional_param('deleted', 0, PARAM_INT);
$intent_id = optional_param('intent_id', 0, PARAM_INT);

$context = context_system::instance();

// Calculate actual Limit end based on start and length values
$end = $start + $length;
// Using $_REQUEST as optional_param_array was not working
if (isset($_REQUEST['search'])) {
    $search = $_REQUEST['search'];
} else {
    $search = [];
}

if (isset($_REQUEST['order'])) {
    $order = $_REQUEST['order'];
} else {
    $order = [];
}

if (isset($_REQUEST['columns'])) {
    $columns = $_REQUEST['columns'];
} else {
    $columns = [];
}

// Set term value
if (isset($search['value'])) {
    $term = $search['value'];
} else {
    $term = '';
}

// Get column to be sorted
if (isset($order[0]['column'])) {
    $orderColumn = $columns[$order[0]['column']]['data'];
    $orderDirection = $order[0]['dir'];
} else {
    $orderColumn = 'name';
    $orderDirection = 'ASC';
}

// Set datatables parameters
datatables::set_table('local_cria_files');
datatables::set_query_params(['intent_id' => $intent_id]);
datatables::set_term($term);
datatables::set_order_column($orderColumn);
datatables::set_order_direction($orderDirection);
datatables::set_columns(['id', 'name', 'indexed', 'intent_id', 'error_message']);
datatables::set_require_actions(true);
datatables::set_start($start);
datatables::set_end($end);
datatables::set_action_column('id');
datatables::set_select_option(true);
datatables::set_select_option_column('id');
datatables::set_select_option_class_name('cria-document-');
datatables::set_action_item_buttons(
    [
        [
            'href' => '/local/cria/edit_content.php',
            'title' => get_string('update', 'local_cria'),
            'display-title' => '<i class="bi bi-pencil"></i>',
            'class' => 'btn btn-link btn-lg',
            'query_strings' => ['id', 'intent_id']
        ],
        [
            'href' => '',
            'title' => get_string('download', 'local_cria'),
            'display-title' => '<i class="bi bi-download"></i>',
            'class' => 'btn btn-link btn-lg',
            'query_strings' => ['id'],
            'file' => [
                'context_id' => $context->id,
                'component' => 'local_cria',
                'filearea' => 'content',
                'itemid' => 'entity_id',
                'filename' => 'name',
                'forcedownload' => true
            ]
        ],
        [
            'href' => '',
            'title' => get_string('delete', 'local_cria'),
            'display-title' => '<i class="bi bi-x-circle" ></i>',
            'class' => 'btn btn-link btn-lg text-danger delete-content',
            'query_strings' => ['id']
        ]
    ]
);
// Get results
$data = datatables::get_records();

// Iterate through $data->results and add additional data
foreach ($data->results as $key => $result) {
    // Get indexed value and set appropriate html
    switch($result['indexed']) {
        case $FILE::INDEXING_PENDING:
            $data->results[$key]['indexed'] = '<span class="badge bg-warning text-dark">' . get_string('indexing_pending', 'local_cria') . '</span>';
            break;
        case $FILE::INDEXING_FAILED:
            $data->results[$key]['indexed'] = '<span class="badge bg-danger" title="' . $result['error_message'] . '">' . get_string('indexing_failed', 'local_cria') . '</span>';
            break;
        case $FILE::INDEXING_COMPLETE:
            $data->results[$key]['indexed'] = '<span class="badge bg-success">' . get_string('indexing_success', 'local_cria') . '</span>';
            break;
        case $FILE::INDEXING_STARTED:
            $data->results[$key]['indexed'] = '<span class="badge bg-info">' . get_string('indexing_started', 'local_cria') . '</span>';
            break;
    }
}
// Create datatables object
$params = [
    "draw" => $draw,
    "recordsTotal" => $data->total_filtered,
    "recordsFiltered" => $data->total_found,
    "data" => $data->results
];
//print_object($params);
// Return Datatables json object
echo json_encode($params);

