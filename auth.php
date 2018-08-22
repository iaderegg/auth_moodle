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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->libdir.'/authlib.php');

class auth_plugin_earlychildhood extends auth_plugin_base {

    public function loginpage_hook(){
        global $CFG;

        $grant_type = "password";
        $client_id = "1_3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4";
        $client_secret = "4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k";
        $earlychildhood_token = "http://primerainfanciasantiagodecali.org/app/web/oauth/v2/token";
        $earlychildhood_active_user = "http://primerainfanciasantiagodecali.org/app/web/api/currentUser/nMfCkKXYpsLQ2g==";
        $username = "admin";
        $password = "admin1234";

        $data_array = array("grant_type" => $grant_type,
                            "client_id" => $client_id,
                            "client_secret" => $client_secret,
                            "username" => $username,
                            "password" => $password);

        $data = json_encode($data_array);

        $ch_token = curl_init($earlychildhood_token);

        //Setting CURL Request

        curl_setopt($ch_token, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_token, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch_token, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch_token, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
        
        // Response to request
        $response = curl_exec($ch_token);
        
        // Close CURL request
        curl_close($ch_token);
        if(!$response) {
            return false;
        }else{
            $response_decode = json_decode($response);
        }

        $access_token = $response_decode->access_token;

        // Request for active user in app
        $ch_active_user = curl_init($earlychildhood_active_user);

        curl_setopt($ch_active_user, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_active_user, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch_active_user, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch_active_user, CURLOPT_HTTPHEADER, array(
            'Authorization Bearer: '.$access_token)
        );

        // Response to request
        $reponse_active_user = curl_exec($ch_active_user);

        curl_close($ch_active_user);

        if(!$response) {
            return false;
        }else{
            var_dump($reponse_active_user);
            $response_decode = json_decode($response);
            print_r("Response decode");
            print_r("<br>");
            print_r($reponse_active_user);
        }

    }

    public function user_login($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id, 'auth'=>'earlychildhood'))) {
            return true;
        }
        return false;
    }

    function callback_handler() {

    }


}
