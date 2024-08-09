<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext(
        'filter_automultilang_deeplapikey',
        new lang_string('deeplapikey', 'filter_automultilang'),
        new lang_string('deeplapikey_desc', 'filter_automultilang'),
        'not set yet',
        PARAM_TEXT
   )); 
    $settings->add(new admin_setting_configtext(
        'filter_automultilang_deeplapiUrl',
        new lang_string('deeplapiurl', 'filter_automultilang'),
        new lang_string('deeplapiurl_desc', 'filter_automultilang'), 
        'https://api-free.deepl.com/v2/translate',
        PARAM_TEXT
    )); 
    $settings->add(new admin_setting_configtext(
        'local_automultilang/notranslationforlang',
        new lang_string('notranslationforlang', 'filter_automultilang'),
        new lang_string('notranslationforlang_desc', 'filter_automultilang'),
        'de',
        PARAM_TEXT
    )); 


}
