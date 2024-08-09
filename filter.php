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
 * Implementation of the Moodle filter API for the Automulti-lang filter.
 *
 * @copyright  2023 Tina John <tina.john@th-luebeck.de>
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

    public static function replace_images_with_placeholder($text) {
        // Define a pattern to match <img src="data:image/..."> tags with base64 images
        $pattern = '/<img\s+[^>]*>/i';
        
        // Use preg_match_all to find all matches and their base64 content
        preg_match_all($pattern, $text, $matches);
        
        // Create an array to store the base64 content
        $base64_images = [];
        
        // Loop through the matches and replace them with placeholders
        foreach ($matches[0] as $index => $img_tag) {
            // Generate a unique placeholder
            $placeholder = '{{ANY_IMAGE_' . $index . '}}';
            
            // Store the base64 content in the array
            $base64_images[$placeholder] = $img_tag;
            
            // Replace the img tag with the placeholder in the text
            $text = str_replace($img_tag, $placeholder, $text);
        }
        
        // Return an array containing the modified text and the array of base64 images
        return [$text, $base64_images];
    }

    public static function restore_images_from_placeholder($text, $base64_images) {
        // Loop through the base64 images array and replace placeholders with the original base64 images
        foreach ($base64_images as $placeholder => $img_tag) {
            $text = str_replace($placeholder, $img_tag, $text);
        }
        
        return $text;
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
        $notranslang = get_config('local_h5ptranslate','notranslationforlang');
        if($lang == $notranslang) {
            return $text;
        }


        $texthash = self::get_contenthash($text);
        //debugecho " - texthash " . $texthash;
        $translation = null;
        
        // get translated text from db
        $newRecord = FALSE;
        $sql = "SELECT transcontent FROM {filter_automultilang} WHERE texthash = ? AND lang = ?";
        $filterRecord = $DB->get_record_sql($sql, [$texthash, $lang]);

        if(!$filterRecord) {
            // clean up base64 images - will throw 413 Error 
            // Replace base64 images with placeholders
            list($modified_text, $base64_images) = self::replace_images_with_placeholder($text);
            //$text = $modified_text;
            //tinjohnartprep echo " automultilang- no record found - try to translate onthefly:-".$text."-";
            $translationinfo = deepltranslate::transWithDeeplHTML($modified_text, $lang);
            if($translationinfo->translationdone) {
            // Typo aber WANN SOLL DAS PASSIERT SEIN????
            // if($transstringinfo->translationdone) {
                $translation = $translationinfo->transstring;
                // Restore the base64 images from placeholders
                $restored_text = self::restore_images_from_placeholder($translation, $base64_images);

                writeTranslationToDB::writeTranslationToDB ($lang, $restored_text, $texthash);
                $sql = "SELECT transcontent FROM {filter_automultilang} WHERE texthash = ? AND lang = ?";
                $filterRecord = $DB->get_record_sql($sql, [$texthash, $lang]);
                if($filterRecord->transcontent) {
                    $newRecord = TRUE;
                }    
            } else {
                // Print the script to send the message to the browser console
                //echo '<script>console.error("filter_automultitrans: NO translation returned by DeepL - Not written to db");</script>';
                debugging("filter_automultitrans: NO translation returned by DeepL - check API KEy and URL in settings - Not written to db", DEBUG_NORMAL);
            }
        }
        if ($filterRecord || $newRecord) {
            //debug
            //echo "- load transversion ";
            $translation = $filterRecord->transcontent;
        } else {
            //echo " no translation available";
        }
        if (is_null($translation)) {
            return $text; 
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
