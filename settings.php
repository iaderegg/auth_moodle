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
 * EarlyChilhood auth
 *
 * Contains all functions for authentication using Early Chilhood App
 *
 * @package    earlychildhood
 * @copyright  2018 Iader E. García Gómez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if($ADMIN->fulltree){

    // Needed for constants.
    require_once($CFG->libdir.'/authlib.php');

    $plugin = 'auth_earlychildhood';

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_earlychildhood/pluginname', '', 
                                             new lang_string('auth_echdescription', $plugin)));

    $settings->add(new admin_setting_configtext("{$plugin}/grant_type", 
                                                 new lang_string("grant_type", $plugin),
                                                 new lang_string("grant_type_description", $plugin),
                                                 '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext("{$plugin}/client_id", 
                                                 new lang_string("client_id", $plugin),
                                                 new lang_string("client_id_description", $plugin),
                                                 '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext("{$plugin}/client_secret", 
                                                 new lang_string("client_secret", $plugin),
                                                 new lang_string("client_secret_description", $plugin),
                                                 '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext("{$plugin}/url_token", 
                                                 new lang_string("url_token", $plugin),
                                                 new lang_string("url_token_description", $plugin),
                                                 '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext("{$plugin}/url_current_user", 
                                                 new lang_string("url_current_user", $plugin),
                                                 new lang_string("url_current_user_description", $plugin),
                                                 '', PARAM_TEXT));


}