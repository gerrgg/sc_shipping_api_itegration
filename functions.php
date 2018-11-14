<?php
/*
Plugin Name: MSP Shipping Integration
Description: Allows for a website to connect with shipping API's
Version: 0.1.0
Author: Gregory Bastianelli
Author URI: http://drunk.kiwi
Text Domain: msp-shipping
*/

add_action( 'admin_init', 'msp_register_settings');
function msp_register_settings(){
  register_setting( 'msp_shipping_creds', 'msp_ups_api_key' );
  register_setting( 'msp_shipping_creds', 'msp_ups_user_name' );
  register_setting( 'msp_shipping_creds', 'msp_ups_password' );
  register_setting( 'msp_shipping_creds', 'msp_usps_user_name' );
  register_setting( 'msp_shipping_creds', 'msp_usps_password' );
  register_setting( 'msp_shipping_creds', 'msp_fedex_api_key' );
  register_setting( 'msp_shipping_creds', 'msp_fedex_user_name' );
  register_setting( 'msp_shipping_creds', 'msp_fedex_password' );
  register_setting( 'msp_shipping_creds', 'msp_log_to_file' );
}

add_action( 'admin_menu', 'sc_setup_shipping_integration' );
if( ! function_exists( 'sc_setup_shipping_integration' ) ){
  /**
  *
  * Creates the spot in the backend for user to enter credentials.
  * removes hand coded sensitive materials.
  *
  */
  function sc_setup_shipping_integration(){
    add_plugins_page( 'MSP Shipping Integration', 'MSP Shipping Integration', 'administrator', 'msp_ship_menu', 'msp_ship_menu_html' );
  }
}

if( ! function_exists( 'sc_debug_log' ) ){
  /**
  *
  */
  function sc_debug_log( $data ){
    file_put_contents( plugin_dir_path( __FILE__ ) . 'msp_debug.txt', print_r( $data, TRUE ), FILE_APPEND );
  }
}

if( ! function_exists( 'msp_ship_menu_html' ) ){
  /**
  *
  * Creates the spot in the backend for user to enter credentials.
  * removes hand coded sensitive materials.
  *
  */
  function msp_ship_menu_html(){
    ?>
    <div class="wrap">
      <h1>Michgian Safety Products Shipping Integration</h1>
      <div class="ups">
        <form method="post" action="options.php">
          <?php
          settings_fields( 'msp_shipping_creds' );
          do_settings_sections( 'msp_shipping_creds' );
          ?>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">UPS API KEY</th>
              <td><input type="text" name="msp_ups_api_key" value="<?php echo esc_attr( get_option('msp_ups_api_key') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">UPS USER NAME</th>
              <td><input type="text" name="msp_ups_user_name" value="<?php echo esc_attr( get_option('msp_ups_user_name') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">UPS PASSWORD</th>
              <td><input type="text" name="msp_ups_password" value="<?php echo esc_attr( get_option('msp_ups_password') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">USPS USERNAME</th>
              <td><input type="text" name="msp_usps_user_name" value="<?php echo esc_attr( get_option('msp_usps_user_name') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">USPS PASSWORD</th>
              <td><input type="text" name="msp_usps_password" value="<?php echo esc_attr( get_option('msp_usps_password') ); ?>" /></td>
            </tr>

            <tr valign="top">
              <th scope="row">Check to Log to File</th>
              <td><input type="checkbox" name="msp_log_to_file" value="1" <?php checked( get_option( 'msp_log_to_file' ) ); ?> /></td>
            </tr>


        </table>
        <?php submit_button(); ?>
        </form>
      </div>
    </div>
    <?php
  }
}

if( ! function_exists( 'sc_bundle_tracking_info' ) ){
  /**
  *
  * retrieves and packs up tracking info
  * @param string $order_id
  * @return array $tracking_info array of shipper, tracking # and prebuilt link
  *
  */
  function sc_bundle_tracking_info( $order_id ){
    $tracking_info = array(
      'shipper'  => get_post_meta( $order_id, 'shipper', true ),
      'tracking' => get_post_meta( $order_id, 'tracking', true ),
      'link'     => get_post_meta( $order_id, 'tracking_link', true ),
    );
    return $tracking_info;
  }
}

if( ! function_exists( 'sc_get_ups_delivery_date' ) ){
  /**
  *
  * creates ups xml file and recieves data via cURL
  * @param string $tracking - the tracking # provided by ups
  */
  function sc_get_ups_delivery_date( $tracking ){
    $accessRequest = sc_ups_create_access_request_xml( );
    $trackRequestXML = sc_ups_create_tracking_request_xml( $tracking );
    $requestXML = $accessRequest->asXML() . $trackRequestXML->asXML();
    $response = sc_get_xml_by_curl( 'https://onlinetools.ups.com/ups.app/xml/Track', $requestXML );
    sc_format_date_and_return( $response );
  }
}

if( ! function_exists( 'sc_format_date_and_return' ) ){
  /**
  *
  * @param array $shipment - ups tracking api response
  *
  *
  */
  function sc_format_date_and_return( $shipment ){
    if( ! isset( $shipment['Shipment']['ScheduledDeliveryDate'] ) ) return;

    $delivery_details = array(
      'date' => $shipment['Shipment']['ScheduledDeliveryDate'],
      'delivered' => $shipment['Shipment']['Package']['DeliveryIndicator'],
      'status' => $shipment['Shipment']['Package']['Activity'][0]['Status']['StatusType']['Description'],
    );

    $timestamp = strtotime( $delivery_details['date'] );
    $delivery_str = ( $delivery_details['delivered'] == 'N' ) ? 'Delivers ' . date('F j, Y', $timestamp) : 'Delivered ' . date('F j, Y', $timestamp);
    return ( $delivery_details['status'] == 'Out For Delivery Today' ) ? $delivery_details['status'] : $delivery_str;
  }
}

if( ! function_exists( 'sc_get_fedex_delivery_date' ) ){
  /**
  *
  * creates fedex xml file and recieves data via cURL
  *
  */
  function sc_get_fedex_delivery_date( $tracking ){
    return 'Click to see Tracking';
  }

}

if( ! function_exists( 'sc_get_usps_delivery_date' ) ){
  /**
  * creates api requrest, processes response and echos result
  * @param string $tracking - USPS Tracking Number
  *
  */
  function sc_get_usps_delivery_date( $tracking ){
    $request = sc_create_usps_tracking_request( $tracking );
    $response = sc_get_xml_by_curl( $request );
    if( isset( $response['TrackInfo']['TrackSummary'] ) ) return $response['TrackInfo']['TrackSummary'];
  }
}

if( ! function_exists( 'sc_create_usps_tracking_request' ) ){
  /**
  *
  * creates usps xml file returns
  * @param string $tracking - USPS Tracking Number
  * @return string $request - String formated to work with USPS API
  */
  function sc_create_usps_tracking_request( $tracking ){
    $tracking = str_replace( ' ', '', $tracking );
    $url = "https://secure.shippingapis.com/ShippingAPI.dll";
    $service = "TrackV2";
    $xml = rawurlencode('
    <TrackRequest USERID="'. get_option( 'msp_usps_user_name' ) .'">
        <TrackID ID="'. $tracking .'"></TrackID>
        </TrackRequest>');
    $request = $url . "?API=" . $service . "&XML=" . $xml;
    return $request;
  }
}

if( ! function_exists( 'sc_ups_create_access_request_xml' ) ){
  /**
  *
  * generate ups api credentials in XML format
  * @param string $api_key - api key generated by ups
  * @param string $id - Userid for UPS ACcount
  * @param string $password - Password for UPS Account
  * @return object $accessRequest - SimpleXMLElement of ups security credentials
  */
  function sc_ups_create_access_request_xml(){
    $accessRequest = new SimpleXMLElement('<AccessRequest></AccessRequest>');
    $accessRequest->addChild( 'AccessLicenseNumber', get_option( 'msp_ups_api_key' ) );
    $accessRequest->addChild( 'UserId', get_option( 'msp_ups_user_name' ) );
    $accessRequest->addChild( 'Password', get_option( 'msp_ups_password' ) );

    return $accessRequest;
  }
}

if( ! function_exists( 'sc_ups_create_tracking_request_xml' ) ){
  /**
  *
  * generate tracking api request for ups
  * @param string $tracking - the tracking # provided by ups
  * @return object $trackRequestXML - SimpleXMLElement of ups security credentials
  */
  function sc_ups_create_tracking_request_xml( $tracking ){
    $trackRequestXML = new SimpleXMLElement ( "<TrackRequest></TrackRequest>" );
  	$request = $trackRequestXML->addChild ( "Request" );
  	$request->addChild ( "RequestAction", "Track" );
  	$request->addChild ( "RequestOption", "activity" );
    $trackRequestXML->addChild( "TrackingNumber", $tracking );

    return $trackRequestXML;
  }
}

if( ! function_exists( 'sc_get_xml_by_curl' ) ){
  /**
  *
  * sets up the environment for an api call via cURL and converts results to array
  *
  * @param string $url - the url of the api we are cURL'ing
  * @param object $xml - SimpleXMLElement - an prebuilt xml file
  * @return array $array - the response of the api converted to an array
  */
  function sc_get_xml_by_curl( $url, $xml = '', $convert = true ){
    try{
        $ch = curl_init();
        if ($ch === false) {
          throw new Exception('failed to initialize');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        // uncomment the next line if you get curl error 60: error setting certificate verify locations
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // uncommenting the next line is most likely not necessary in case of error 60
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);

        $content = curl_exec($ch);

        // Check the return value of curl_exec(), too
        if ($content === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        if( $convert == true ){
          /* Process $content here */
          $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
          $json = json_encode($xml);
          $content = json_decode($json,TRUE);
        }

        return $content;
        // Close curl handle

        curl_close($ch);
      } catch(Exception $e) {

      trigger_error(sprintf(
          'Curl failed with error #%d: %s',
          $e->getCode(), $e->getMessage()),
          E_USER_ERROR);
    }
  }
}
