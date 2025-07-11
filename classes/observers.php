<?php

namespace local_cria;

class observers
{
    /**
     * @param \core\event\base $event
     */
    public static function file_created($event)
    {
        global $DB;
        $data = $event->get_data();
        file_put_contents('/var/www/moodledata/temp/observers.json', json_encode($data, JSON_PRETTY_PRINT));
    }
}