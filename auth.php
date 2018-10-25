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

    /**
     * Constructor.
    */  
    function __construct() {
        global $CFG;
        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        $this->authtype = 'earlychildhood';
        $this->config = get_config('auth_earlychildhood');
        $this->errorlogtag = '[AUTH EARLYCHILDHOOD] ';
    }

    public function loginpage_hook(){
        
        global $CFG, $DB, $OUTPUT, $PAGE;

        if(!isset($_GET['currentUser'])){
            $authplugin = get_auth_plugin('email');
            $authplugin->loginpage_hook();
            
        }else{
            $current_user_encode = $_GET['currentUser'];

            $config = $this->config;

            $earlychildhood_active_user = $config->url_current_user."/".$current_user_encode;
            $username = "admin";
            $password = "admin1234";

            $data_array = array("grant_type" => $config->grant_type,
                                "client_id" => $config->client_id,
                                "client_secret" => $config->client_secret,
                                "username" => $username,
                                "password" => $password);

            $data = json_encode($data_array);

            $ch_token = curl_init();

            //Setting CURL Request
            curl_setopt($ch_token, CURLOPT_URL, $config->url_token);
            curl_setopt($ch_token, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_token, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch_token, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch_token, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch_token, CURLOPT_TIMEOUT, 500);
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
            $ch_active_user = curl_init();

            curl_setopt($ch_active_user, CURLOPT_URL, $earlychildhood_active_user);
            curl_setopt($ch_active_user, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_active_user, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch_active_user, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch_active_user, CURLOPT_HEADER, false); 
            curl_setopt($ch_active_user, CURLOPT_VERBOSE, false);
            curl_setopt($ch_active_user, CURLOPT_TIMEOUT, 500);
            curl_setopt($ch_active_user, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer '.$access_token
                )
            );

            // Response to request
            $response_active_user = curl_exec($ch_active_user);
            $httpcode = curl_getinfo($ch_active_user);

            curl_close($ch_active_user);

            if(!$response_active_user) {
                
                return false;

            }else{

                $response_active_user = json_decode($response_active_user);
                $urltogo = $CFG->wwwroot.'/course/view.php?id=2';
                $current_user = $response_active_user->info;
                $username = $current_user[0]->persona->documentoIdentidad;
                $user = $this->user_login($username, $username);

		$studentrole = $DB->get_record('role', array('shortname'=>'student'));
                $instance = $DB->get_record('enrol', array('courseid'=>2, 'enrol'=>'manual'), '*', MUST_EXIST);
                $context = context_course::instance(2);
                $selfplugin = enrol_get_plugin('manual');

                if($user){

                    $sql_query = "SELECT * 
                                FROM {user}
                                WHERE username = '$username'";

                    $user = $DB->get_record_sql($sql_query);

                    
                    complete_user_login($user);
                    redirect($urltogo);
                }else{

                    require_once($CFG->dirroot . '/user/lib.php');
                        
                    // Se configura el nuevo usuario a crear
                    $user = new stdClass();
                
                    $user->username = (string)$username;
                    $user->password =  MD5((string)$username);
                    $user->firstname = (string)$current_user[0]->persona->nombres;
                    $user->lastname = (string)$current_user[0]->persona->apellidos;
                    $user->email = (string)$current_user[0]->persona->correo;
                    $user->description = (string)$account->description;
		    $user->confirmed = 1;
                    $user->mnethostid = 1;
                    
                    $id = user_create_user($user, false);
                    
                    $sql_query = "SELECT * 
                                FROM {user}
                                WHERE id = $id";

                    $user = $DB->get_record_sql($sql_query);

                    complete_user_login($user);

		    $selfplugin->enrol_user($instance, $user->id, $studentrole->id);

                    redirect($urltogo);
                }
            }
        }

        
    }

    public function user_login($username, $password) {
        global $CFG, $DB;

        $user = $DB->get_record('user', array('username'=>$username));
        if ($user) {
            return true;
        }
        return false;
    }

    function callback_handler() {

    }
}
