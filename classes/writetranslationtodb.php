<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * 
 * @package    filter_automultilang
 * @copyright  2023 Tina John <tina.john@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_automultilang;



defined('MOODLE_INTERNAL') || die;


class writetranslationtodb {
        
    public static function writeTranslationToDB ($lang, $transContent, $texthash) {
        global $DB;

        // Check if the record already exists
        if($h5pId !== NULL) {
            $existingRecord = $DB->get_record('filter_automultilang', ['texthash' => $texthash, 'lang' => $lang]);
            if($existingRecord->texthash) {
                //debugecho  "already there - no need to do it again";
                // already there - no need to do it again
                return;
            }
        } else {
            $existingRecord = FALSE;
        }

        if ($existingRecord) {
            //debugecho  "Data updated successfully!";
        } else {
            // Insert a new record
            $data = new \stdClass();
            $data->lang = $lang;
            $data->transcontent = $transContent;
            $data->texthash = $texthash;
            $data->timecreated = time();
            $DB->insert_record('filter_automultilang', $data);
            //debugecho  "Data inserted successfully!";
        }
    }
}
