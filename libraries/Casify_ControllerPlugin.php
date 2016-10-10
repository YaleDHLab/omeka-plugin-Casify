<?php

class Casify_ControllerPlugin extends Zend_Controller_Plugin_Abstract
{

  public function preDispatch(Zend_Controller_Request_Abstract $request)
  {
    $this->_checkRequestedRoute($request);
  }

  /**
  * Function that identifies the collections to be kept protected
  **/

  public function getProtectedCollections() {
    $protected_collections = array("5");
    return $protected_collections;
  }

  /**
  * Read in an item id and return a boolean indicating
  * whether the item belongs to a protected collection
  **/

  public function itemIsProtected($item_id) {

    // fetch the protected collections
    $protected_collections = $this->getProtectedCollections();

    mysql_connect("localhost", "DB_USER", "DB_USER_PASSWORD", true);
    mysql_select_db("OMEKA_DB_NAME") or die("Could not select the omeka db");

    $item_collection_query = "select collection_id from omeka_items where id = ". $item_id.";";
    $requested_collection_result = mysql_query($item_collection_query);

    if ($requested_collection_result) {
      $requested_collection = mysql_fetch_assoc($requested_collection_result);
      $requested_collection_id = $requested_collection["collection_id"];
      if (in_array($requested_collection_id, $protected_collections)) {
        return TRUE;
      };
    };
  }

  /**
  * Read in a route and return a boolean indicating
  * whether the route is protected
  **/

  public function routeIsProtected($request) {

    // identify the protected routes
    $protected_collections = $this->getProtectedCollections();

    // identify the route the user has requested 
    $requested_route = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    // check if this is a protected transcribe route
    if (strpos($requested_route, 'transcribe/') !== false) {
      $requested_transcribe_path = end( explode('omeka/transcribe/', $requested_route) );
      $requested_transcribe_array = explode("/", $requested_transcribe_path);
      $requested_transcribe_item = array_shift(array_slice($requested_transcribe_array, 0, 1));

      if ($this->itemIsProtected($requested_transcribe_item)) {
        return TRUE;
      }
    }

    // check if this is a protected item route
    if (strpos($requested_route, 'items/') !== false) {
      $requested_item_path = end( explode('omeka/items/', $requested_route) );
      $requested_item_end = end(explode('/', $requested_item_path));
      $requested_item_array = explode("?", $requested_item_end);
      $requested_item = array_shift(array_slice($requested_item_array, 0, 1));

      if ($this->itemIsProtected($requested_item)) {
        return TRUE;
      }
    }

    // check if the requested route is a protected collection route
    if (strpos($requested_route, 'collections/') !== false) {
      $requested_collection_path = end( explode('omeka/collections/', $requested_route) );
      $requested_collection_end = end( explode('/', $requested_collection_path));
      $requested_collection_array = explode("?", $requested_collection_end); 
      $requested_collection = array_shift(array_slice($requested_collection_array, 0, 1));
      if (in_array($requested_collection, $protected_collections)) {
        return TRUE;
      }
    }
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

    setcookie($destination_cookie, $requested_route, time() + (86400 * 30), "/");

    if ($this->routeIsProtected($request)) {

      /**
      * If the user has an auth cookie in their browser, let them through
      * else check for a ticket that indicates new cookie request
      **/

      if(isset($_COOKIE[$auth_cookie])) {
        // Cookie named $auth_cookie is set
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
