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

namespace local_cria\task;

use local_cria\file;
use local_cria\intent;

class index_files_adhoc extends \core\task\adhoc_task
{
    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute()
    {
        global $DB;

        $data = $this->get_custom_data();
        $intentid = (int) ($data->intent_id ?? 0);
        $fileid = (int) ($data->file_id ?? 0);

        if ($intentid <= 0) {
            mtrace('local_cria index_files_adhoc: missing intent_id');
            return;
        }

        $intent = new intent($intentid);
        if ($fileid > 0) {
            $intent->index_files($fileid);
            return;
        }

        $intent->index_pending_files();
    }
}
