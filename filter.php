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
 * @package    filter_automultilang
 * @copyright  2023 Tina John <johnt.22.tijo@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use filter_automultilang\deepltranslate;
use filter_automultilang\writetranslationtodb;

/**
 * Implementation of the Moodle filter API for the Multi-lang filter.
 *
 * @copyright  Gaetan Frenoy <gaetan@frenoy.net>
 * @copyright  2004 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_automultilang extends moodle_text_filter {
     
    /**
     * Calculate contenthash of a given content string
     *
     * @param string|null $content
     * @return string
     */
    public static function get_contenthash(?string $content = null): string {
        return sha1($content ?? '');
    }
    function filter($text, array $options = array()) {
        global $CFG, $DB;

        if (empty($text) or is_numeric($text)) {
            return $text;
        }
        if (!class_exists('\file_storage')) {
            return $text;
        }
        $result = $text . "automultilangfilter";
        $lang = current_language();
        echo "current_language" . $lang . " ";
        if($lang == "de" || $lang == "DE") {
            return $result;
        }
        $texthash = \file_storage::hash_from_string($text);
        //debugecho " - texthash " . $texthash;
                
   
       // get translated text from db
        $newRecord = FALSE;
        $sql = "SELECT transcontent FROM {filter_automultilang} WHERE texthash = ? AND lang = ?";
        $filterRecord = $DB->get_record_sql($sql, [$texthash, $lang]);

        if(!$filterRecord) {
            echo " - no record found - try to translate onthefly";
            $translation = deepltranslate::transWithDeeplXML($text, $lang);
            writeTranslationToDB::writeTranslationToDB ($lang, $translation, $texthash);
            $sql = "SELECT transcontent FROM {filter_automultilang} WHERE texthash = ? AND lang = ?";
            $filterRecord = $DB->get_record_sql($sql, [$texthash, $lang]);
            if($filterRecord->transcontent) {
                $newRecord = TRUE;
            }
        }
        if ($filterRecord || $newRecord) {
            //debug
            echo "- load transversion ";
            $translation = $filterRecord->transcontent;
        } else {
            echo " no translation available";
        }
        if (is_null($translation)) {
            return $result; 
        } else {
            //writeTranslationToDB ($lang, $translation, $hash)
            return $translation;
        }
    }

    /**
     * Puts some caching around get_parent_language().
     *
     * Also handle parent == 'en' in a way that works better for us.
     *
     * @param string $lang a Moodle language code, e.g. 'fr'.
     * @return string the parent language.
     */
    protected function get_parent_lang(string $lang): string {
        static $parentcache;
        if (!isset($parentcache)) {
            $parentcache = ['en' => ''];
        }
        if (!isset($parentcache[$lang])) {
            $parentcache[$lang] = get_parent_language($lang);
            // The standard get_parent_language method returns '' for parent == 'en'.
            // That is less helpful for us, so change it back.
            if ($parentcache[$lang] === '') {
                $parentcache[$lang] = 'en';
            }
        }
        return $parentcache[$lang];
    }
}
