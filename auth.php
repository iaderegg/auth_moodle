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
require_once($CFG->dirroot . '/auth/earlychildhood/OAuth.php');
require_once($CFG->dirroot . '/auth/earlychildhood/BasicOAuth.php');
use \OAuth1\BasicOauth;

class auth_plugin_earlychildhood extends auth_plugin_base {

    public function loginpage_hook(){
        global $CFG;

        $grant_type = "password";
        $client_key = "1_3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4";
        $client_secret = "4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k";
        $earlychildhood_host = "http://primerainfanciasantiagodecali.org/app/web";
        $user = "admin";
        $password = "admin1234";

        if( (strlen($earlychildhood_host) > 0) && (strlen($client_key) > 0) && (strlen($client_secret) > 0) ){
            
            $redirect_url = $earlychildhood_host."/oauth/v2/token?grant_type=".$grant_type."?client_key=".$client_key."&client_secret=".$client_secret."&username=".$user."&password=".$password;
            header('Location: ' . $redirect_url);
            die;
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

        global $CFG, $DB, $SESSION;

        $grant_type = "password";
        $client_key = "1_3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4";
        $client_secret = "4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k";
        $earlychildhood_host = "http://primerainfanciasantiagodecali.org/app/web";
        
        // strip the trailing slashes from the end of the host URL to avoid any confusion (and to make the code easier to read)
        $earlychildhood_host = rtrim($earlychildhood_host, '/');
        
        // at this stage we have been provided with new permanent token
        $connection = new BasicOAuth($client_key, $client_secret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'], $grant_type);
        
        $connection->host = $earlychildhood_host;
        
        $connection->accessTokenURL = $earlychildhood_host."/oauth/v2/token";
        
        $tokenCredentials = $connection->getAccessToken($_REQUEST['oauth_verifier']);
        
        if(isset($tokenCredentials['oauth_token']) && isset($tokenCredentials['oauth_token_secret'])) {
        
            $perm_connection = new BasicOAuth($client_key, $client_secret, $tokenCredentials['oauth_token'],
                    $tokenCredentials['oauth_token_secret']);
            
            $account = $perm_connection->get($earlychildhood_host . '/wp-json/wp/v2/users/me?context=edit');
            
            if(isset($account)) {
                // firstly make sure there isn't an email collision:
                if($user = $DB->get_record('user', array('email'=>$account->email))) {
                    if($user->auth != 'earlychildhood') {
                        print_error('usercollision', 'auth_earlychildhood');
                    }
                }
                
                // check to determine if a user has already been created...     
                if($user = authenticate_user_login($account->username, $account->username)) {
                    // TODO update the current user with the latest first name and last name pulled from WordPress?
        
                    if (user_not_fully_set_up($user, false)) {
                        $urltogo = $CFG->wwwroot.'/user/edit.php?id='.$user->id.'&amp;course='.SITEID;
                        // We don't delete $SESSION->wantsurl yet, so we get there later
        
                    }
                } else {
                    require_once($CFG->dirroot . '/user/lib.php');
                    
                    // we need to configure a new user account
                    $user = new stdClass();
                    
                    $user->mnethostid = $CFG->mnet_localhost_id;
                    $user->confirmed = 1;
                    $user->username = $account->username;
                    $user->password = AUTH_PASSWORD_NOT_CACHED;
                    $user->firstname = $account->first_name;
                    $user->lastname = $account->last_name;
                    $user->email = $account->email;
                    $user->description = $account->description;
                    $user->auth = 'wordpress';
                    
                    $id = user_create_user($user, false);
                    
                    $user = $DB->get_record('user', array('id'=>$id));
                }
                
                complete_user_login($user);
                
                if (isset($SESSION->wantsurl) and (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
                    $urltogo = $SESSION->wantsurl;    /// Because it's an address in this site
                    unset($SESSION->wantsurl);
                
                } else {
                    $urltogo = $CFG->wwwroot.'/';      /// Go to the standard home page
                    unset($SESSION->wantsurl);         /// Just in case
                }
                
                /// Go to my-moodle page instead of homepage if defaulthomepage enabled
                if (!has_capability('moodle/site:config',context_system::instance()) and !empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_MY and !isguestuser()) {
                    if ($urltogo == $CFG->wwwroot or $urltogo == $CFG->wwwroot.'/' or $urltogo == $CFG->wwwroot.'/index.php') {
                        $urltogo = $CFG->wwwroot.'/my/';
                    }
                }
                
                redirect($urltogo);
                
                exit;
            }
        }
    }


}
