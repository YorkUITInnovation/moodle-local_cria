<?php

namespace local_cria\event;

use core\event\base;

defined('MOODLE_INTERNAL') || die();

class file_created extends base
{

    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->data['objecttable'] = 'local_cria_file';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function file_created(\local_cria\bot $bot, \local_cria\file $file)
    {
        $event = self::create([
            'objectid' => $file->get_id,
            'context' => \context_system::instance(),
            'other' => [
                'bot_id' => $bot->get_id(),
                'bot_name' => $bot->get_name(),
                'file_id' => $file->get_id(),
            ]
        ]);
        $event->trigger();
    }

    /**
     * Get the event name
     */
    public static function get_name() {
        return get_string('event_file_created', 'local_cria');
    }

    /**
     * Get the description of the event
     */
    public function get_description()
    {
        return "The user with id '$this->userid' has created a file with id '$this->objectid' " .
            "for the bot with bot name '$this->bot_name'.";
    }
}