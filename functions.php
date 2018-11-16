<?php
/*
Plugin Name: MSP Shipping Integration
Description: Allows for a website to connect with shipping API's
Version: 0.1.0
Author: Gregory Bastianelli
Author URI: http://drunk.kiwi
Text Domain: msp-shipping
*/
add_action( 'wp_enqueue_scripts', 'msp_enqueue_scripts');
add_shortcode( 'return_form', 'msp_return_form_dispatcher' );
add_action( 'admin_init', 'msp_register_settings');

function msp_enqueue_scripts(){
  wp_enqueue_style( 'style', plugin_dir_url( __FILE__ ) . '/style.css', false, rand(1, 1000), 'all' );
  wp_enqueue_script( 'script', plugin_dir_url( __FILE__ ) . '/main.js', array( 'jquery' ), rand(1, 1000) );
}

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

if( ! function_exists( 'msp_return_form_dispatcher' ) ){
  /**
  *
  * checks for $_GET variables if none, then send to form to get it
  *
  */
  function msp_return_form_dispatcher(){
    if( isset( $_GET['id'], $_GET['email'] ) ){
      msp_validate_user( $_GET['id'], $_GET['email'] );
    } else {
      msp_non_valid_user_return_form();
    }
  }
}

if( ! function_exists( 'msp_non_valid_user_return_form' ) ){
  /**
  * outputs the form for users to enter the order id and creds to verify user
  * @param string $error - provides user with feedback when things dont match up.
  */
  function msp_non_valid_user_return_form( $error = '' ){
    ?>
    <div class="col-12 text-center">
      <h4 class="danger"><?php echo $error; ?></h4>
      <form class="text-center" method="GET" style="max-width: 450px; margin: auto">
        <h2>Please log in, or enter the ID and Email attached to the order.</h2>
        <div class="form-group">
          <input type="tel" name="id" placeholder="Order ID" />
          <input type="email" name="email" placeholder="youremail@example.com" />
        </div>
        <button type="submit" class="woocommerce-button button">Submit</button>
      </form>
    </div>
    <?php
  }
}

if( ! function_exists( 'msp_validate_user' ) ){
  /**
  *
  * makes sure that the user is in fact the person who made the order
  */
  function msp_validate_user( $order_id, $given_email  ){
    if( isset( $_GET['id'], $_GET['email'] ) ){
        $order_id = $_GET['id'];
        $given_email = $_GET['email'];
    }

    $order = wc_get_order( $order_id );
    if( empty( $order ) ) {
      msp_non_valid_user_return_form( 'Sorry, that order does not exist!' );
    } else {
      $order_email = $order->get_billing_email();
      if( $order_email != urldecode( $given_email ) ){
        msp_non_valid_user_return_form( 'Sorry, ' . urldecode( $given_email ) . ' that is not the email on the order!' );
      } else {
        msp_get_return_form_html( $order );
      }
    }
  }
}

add_action( 'admin_post_confirm_return', 'msp_confirm_return' );
add_action( 'admin_post_nopriv_confirm_return', 'msp_confirm_return' );

if( ! function_exists( 'msp_confirm_return' ) ){
  function msp_confirm_return(){
    pre_dump( $_POST );
  }
}

if( ! function_exists( 'msp_get_return_form_html' ) ){
  function msp_get_return_form_html( $order ){
    if( $order ){
      $items = $order->get_items();
      // pre_dump( $items );
      ?>
      <h3>Which Item's would you like to return/exchange?</h3>
      <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="row">
        <div class="col-12 col-sm-6">
          <?php foreach( $items as $key => $item ) : ?>
            <?php $id = msp_get_actual_id( $item ); ?>
            <?php $product = wc_get_product( $id ); ?>
            <?php if( $product ) : ?>
            <div id="<?php echo $id ?>" class="row return-product" data-qty="<?php echo $item['quantity'] ?>">
              <div class="col-3">
                <?php $image_src = wp_get_attachment_image_src( $product->get_image_id() ); ?>
                <div class="return-product-img" aria-checked="false">
                  <img src="<?php echo $image_src[0]; ?>" class="thumbnail" />
                  <i class="fa fa-check-circle fa-4x"></i>
                </div>
              </div>
              <div class="col-9">
                <span class="title" style="margin: 0px;"><?php echo $product->get_name(); ?></span>
                <span class="sku"><?php echo $product->get_sku(); ?></span><br>
                <span class="price"><?php echo '$' . $item['total']; ?></span>
              </div>
              </div>
            <div class=" <?php echo $id ?>_hidden-return-form hidden-return-form"></div>
            <?php endif; ?>
          <?php endforeach; ?>
          <br>
          <input type="hidden" name="order_id" value="<?php echo $order->get_id(); ?>">
          <input type="hidden" name="action" value="confirm_return">
        </div>
        <div class="col-12 col-sm-6">
          <div class="confirmation">
            <h4>I am returning...</h4>
            <div id="return-data"></div>
            <div style="display: flex; margin-bottom: 1rem;">
              <input id="check_return" class="form-control" type="checkbox">
              <label style="line-height: 12px;">I am confirming the above information is accurate.</label>
            </div>
            <button type="submit" class="woocommerce-button button w-100" disabled>Submit</button>
          </div>
        </div>
      </form>
      <?php
    }
  }
}

if( ! function_exists( 'sc_return_item_html' ) ){
  /**
  * helper function helps to reduce which id to use - product or variation
  * @param array $item - array of item returned from WC_ORDER->get_items();
  * @return string $actual_id - the proper id to use.
  */
  function msp_get_actual_id( $item ){
    return ( empty( $item['variation_id'] ) ) ? $item['product_id'] : $item['variation_id'];
  }
}

add_action( 'msp_after_my_account_order_actions', 'sc_return_item_html', 5, 1 );
if( ! function_exists( 'sc_return_item_html' ) ){
  /**
  *
  */
  function sc_return_item_html( $order_id ){
    // TODO: Add logic which will only display the button if an order can be returned
    $order = wc_get_order( $order_id );
    $email = $order->get_billing_email();
    $link = get_site_url( ) . '/returns?id='. $order_id . '&email=' . $email;
    $return_btn = '<a href="'. $link .'" class="woocommerce-button button">Return</a>';
    echo $return_btn;
  }

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
    return sc_format_date_and_return( $response );
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
    // pre_dump( $shipment );

    $delivery_details = array(
      'delivered' => $shipment['Shipment']['Package']['DeliveryIndicator'],
      'status' => $shipment['Shipment']['Package']['Activity'][0]['Status']['StatusType']['Description'],
    );

    if( $delivery_details['delivered'] == 'Y' ){
      return 'Delivered ' . date( 'F, j, Y', strtotime( $shipment['Shipment']['Package']['DeliveryDate'] ) );
    } else {
      return 'Delivers ' . date( 'F, j, Y', strtotime( $shipment['Shipment']['ScheduledDeliveryDate'] ) );
    }
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
