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


error_reporting(E_ALL); 
ini_set('display_errors', 1);

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
        
        global $CFG;

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

        $ch_token = curl_init($config->url_token);

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

        // print_r('<br>');
        // print_r($httpcode);

        curl_close($ch_active_user);

        if(!$response_active_user) {
            return false;
        }else{

            $response_active_user = json_decode($response_active_user);

            print_r('Response decoded <br>');
            print_r($response_active_user);

            $current_user = $response_active_user->info;

            $username = $current_user[0]->persona->documentoIdentidad;

            $user = authenticate_user_login($username, null);

            if($user){

                require_once($CFG->dirroot . '/user/lib.php');
                    
                // we need to configure a new user account
                $user = new stdClass();
                
                //$user->mnethostid = $CFG->mnet_localhost_id;
                //$user->confirmed = 1;
                $user->username = $username;
                $user->password = AUTH_PASSWORD_NOT_CACHED;
                $user->firstname = $current_user[0]->persona->nombres;
                $user->lastname = $current_user[0]->persona->apellidos;
                $user->email = $current_user[0]->persona->correo;
                $user->description = $account->description;
                
                $id = user_create_user($user, false);
                
                $user = $DB->get_record('user', array('id'=>$id));

            }
        }
    }

    public function user_login($username, $password) {
        global $CFG, $DB;

        print_r("entro");
        $user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id, 'auth'=>'earlychildhood'));
        if ($user) {
            return true;
        }
        return false;
    }

    function callback_handler() {

    }

    // stdClass Object ( [error] => 
    //                   [info] => Array ( [0] => stdClass Object ( [idUsuario] => 5 
    //                                                              [nick] => pedroperez 
    //                                                              [clave] => d8ae5776067290c4712fa454006c8ec6 
    //                                                              [ultimoAcceso] => stdClass Object ( [date] => 2018-08-24 19:51:33.000000 
    //                                                                                                  [timezone_type] => 3 [timezone] => UTC ) 
    //                                                              [estado] => 1 
    //                                                              [rol] => stdClass Object ( [idRol] => 4 
    //                                                                                         [nombre] => Tutor 
    //                                                                                         [descripcion] => Parsona encargada de diligenciar los formularios ) 
    //                                                              [persona] => stdClass Object ( [idPersona] => 32 
    //                                                                                             [nombres] => PEDRO PEREZ 
    //                                                                                             [apellidos] => 
    //                                                                                             [documentoIdentidad] => 987654321 
    //                                                                                             [tipoDoc] => [fechaNacimiento] => 
    //                                                                                             [genero] => 
    //                                                                                             [telefonoFijo] => 
    //                                                                                             [numeroContacto] => 9999999 
    //                                                                                             [cei] => 9999 
    //                                                                                             [correo] => sistemas.revistas@correounivalle.edu.co [datoExtra] => ) [etapaActual] => stdClass Object ( [idEtapa] => 5 [nombre] => FASE 1A [descripcion] => Acompañamiento - Fase 1 [fechaInicio] => stdClass Object ( [date] => 2018-01-17 00:00:00.000000 [timezone_type] => 3 [timezone] => UTC ) [fechaFin] => stdClass Object ( [date] => 2018-11-30 00:00:00.000000 [timezone_type] => 3 [timezone] => UTC ) ) ) ) )     


}
