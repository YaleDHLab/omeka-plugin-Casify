<?php

class Casify_ControllerPlugin extends Zend_Controller_Plugin_Abstract
{

  public function preDispatch(Zend_Controller_Request_Abstract $request)
  {
    $this->_checkRequestedRoute($request);
  }

  protected function _checkRequestedRoute($request)
  {

    $requested_route = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $cas_endpoint = "https://secure.its.yale.edu/";
    $auth_cookie = "omekaUserAuth";
    $destination_cookie = "omekaUserDestination";

    /**
    * Check if the requested route is forbidden. If it is,
    * store the requested route and authenticate the user
    **/

    if (strpos($requested_route, 'collection') !== false) {
      setcookie($destination_cookie, $requested_route, time() + (86400 * 30), "/");

      /**
      * If the user has an auth cookie in their browser, let them through
      * else check for a ticket that indicates new cookie request
      **/

      if(isset($_COOKIE[$auth_cookie])) {
        echo "Cookie named '" . $auth_cookie . "' is set!";
      } else {

        /** 
        * If they don't have a cookie, check if the user 
        * has a ticket in their request and validate if they do
        **/

        if (strpos($requested_route, 'ticket=') !== false) {
          $destination = $_COOKIE[$destination_cookie];
          $ticket = $_GET["ticket"];

          $curl_request = curl_init($cas_endpoint . 'cas/serviceValidate?ticket=' . $ticket . '&service=' . $destination);
          curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl_request, CURLOPT_HEADER, 0);
          $curl_response = curl_exec($curl_request);
          curl_close($curl_request);
          error_log($curl_response, 3, "/var/tmp/casifyControllerPlugin.log"); 

          if (strpos($curl_response, '<cas:user>') !== false) {
            $ticket_is_valid = 1;
          } else {
            $ticket_is_valid = 0;
          }

          /**
          * if the ticket is not valid, send the user back to the home destination
          * else store a cookie valid for one day
          **/
          if ($ticket_is_valid == 0) {
            $this->_getRedirect()->gotoUrl($base_url);
          } else { 
            $cookie_value = $ticket;
            setcookie($auth_cookie, $cookie_value, time() + (86400 * 30), "/");
          }

        /**
        * else store the user's destination in a cookie,
        * and send user for auth at CAS endpoint
        **/
        } else {
          setcookie($destination_cookie, $requested_route, time() + (86400 * 30), "/");   
          $cas_login_endpoint = $cas_endpoint .  'cas/login?service=' . urlencode($requested_route);
          $this->_getRedirect()->gotoUrl($cas_login_endpoint); 
        }

      } // closes the check for forbidden route
    } // closes the check for auth cookie
  }

  protected function _getRedirect()
  {
    return Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
  }
}
?>
