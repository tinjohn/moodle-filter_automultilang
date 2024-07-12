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
 * @copyright  2024 Tina John <tina.john@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_automultilang;



defined('MOODLE_INTERNAL') || die;


class deepltranslate {
    public static function getAPIkey () {
        global $CFG;

        // Ensure the configurations for this site are set
        $settings = get_config('filter_automultilang');
        // tinjohnartprep var_dump($settings);
        
            // Check if the API key setting exists
            $apiKey =  $CFG->filter_automultilang_deeplapikey;
            if($apiKey) {
                // Use the API key 
                //debugecho "API Key: " . $apiKey;
                return $apiKey;
            } else {
                echo '<script>console.log("filter_automultitrans: DEEPL API KEY not set yet");</script>';
                return "not set yet";
            }
        
    }
    public static function getAPIurl () {
        global $CFG;

        // Ensure the configurations for this site are set
        $settings = get_config('filter_automultilang');
        // tinjohnartprep var_dump($settings);
            // Check if the API key setting exists
            $apiUrl = $CFG->filter_automultilang_deeplapiUrl;
            if($apiUrl) {
                // Use the API URL 
                //debugecho echo '<script>console.log("filter_automultitrans: use API URL: ' . $apiUrl . '");</script>';
                return $apiUrl;
            } else {
                echo '<script>console.log("filter_automultitrans: DEEPL API URL not set yet");</script>';
                return "not set yet";
            }        
    }

    // USED (debugoff) translation with Deepl of flatten lang text array as json string
    public static function transWithDeeplHTML ($string, $trglang) {
        // https://www.deepl.com/docs-api/translate-text/
        $transstringinfo = new \stdClass();
        $transstringinfo->string = $string;
        $transstringinfo->translationdone = false;
        $transstringinfo->transstring = "";
        

        // tinjohnartprep echo "translation by Deepl.com "; 
        // Replace [yourAuthKey] with your actual DeepL API authentication key
        $authKey = self::getAPIkey();
        //debug echo "<h1> KEY <h1>" . $authKey;
       
        //$authKey = 'for-debugging-off';
        if($authKey == 'for-debugging-off' || $authKey == 'not set yet') {
            //debug echo "<h1 style='color: red;'> DeepL API key is: " . $authKey . "</h1>";
            return $transstringinfo;
        }

        $apiUrl = self::getAPIurl();
        //$apiUrl = 'https://api-free.deepl.com/v2/translate';

        if($apiUrl == 'for-debugging-off' || $apiUrl == 'not set yet') {
           //debug echo "<h1 style='color: red;'> DeepL API URL is: " . $apiUrl . "</h1>";
            return $transstringinfo;
        }

        // Data to be translated and target language
        $data = array(
            'text' => array($string),
            'target_lang' => $trglang,
            'tag_handling' => 'html'
        );

        // Convert data to JSON format
        try {
            $dataJson = json_encode($data);
            // Print the script to send the message to the browser console
            //echo '<script>console.error("filter_automultitrans: json encoded");</script>';
        } catch (Exception $e) {
            $message = new \core\message\message();
            $message->courseid = SITEID;
            $message->component = 'moodle';
            $message->name = 'automultilang';
            $message->notification = 1;
            $message->userfrom = core_user::get_noreply_user();
            $message->subject = 'debug message';
            $message->fullmessage = $e->getMessage() . " automultitrans " .$string;
            send_message($message);
            // Print the script to send the message to the browser console
            echo '<script>console.error("filter_automultitrans: JSON encoding error: ' . addslashes($e->getMessage()) . '");</script>';

            return($transstringinfo);

        }

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: DeepL-Auth-Key ' . $authKey,
            'Content-Type: application/json'
        ));

        // Execute cURL session and get the response
        $response = curl_exec($ch);

        // Check for cURL errors

        if (curl_errno($ch)) {
            $error_message = curl_error($ch);
            echo 'Debug: cURL Error: ' . $error_message;
        }
        if (curl_errno($ch)) {
            //debug echo 'cURL Error check API key and API url: ' . curl_error($ch);
            // Print the script to send the message to the browser console
            echo '<script>console.error("filter_automultitrans: cURL Error check API key and API url: ' . addslashes(curl_error($ch)) . '");</script>';

        } else {
            // Check for HTTP status codes
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 400) {
                // Print the script to send the message to the browser console
                echo '<script>console.error("filter_automultitrans: HTTP Error send by DeepL: ' . $httpCode . ' (404 - check API URL)");</script>';
            }
        }



        // Close cURL session
        curl_close($ch);

        try {
            // Decode the JSON response
            $translatedData = json_decode($response, true);
        } catch (Exception $e) {
            echo 'filter_automultitrans: JSON decoding error: ' . $e->getMessage();
            $message = new \core\message\message();
            $message->courseid = SITEID;
            $message->component = 'moodle';
            $message->name = 'automultilang';
            $message->notification = 1;
            $message->userfrom = core_user::get_noreply_user();
            $message->subject = 'debug message';
            $message->fullmessage = $e->getMessage() . " automultitrans " .$string;
            send_message($message);
            // Print the script to send the message to the browser console
            echo '<script>console.error("filter_automultitrans: JSON decoding error: ' . addslashes($e->getMessage()) . '");</script>';

            return($transstringinfo);
        }

        // Output the translated text
        if (isset($translatedData['translations'][0]['text'])) {
            //debugecho " ------ remove the . in json file --------";
            $newstring = $translatedData['translations'][0]['text'];
            $newstring = trim($newstring, ".");
            // not a good solution if($newstring != $string) {} - translates again and again
            $transstringinfo->translationdone = true;
            $transstringinfo->transstring = $newstring;
            return($transstringinfo);
        } else {
            return($transstringinfo);
        }
    }
    

}
