<?php
class MSP_Shipping {
  public $order;
  public $log_to_file;
  public $return_policy;
  public $ups;
  public $usps;
  public $fedex;
  public $return;

  public function __construct( $return ) {
    $this->order = wc_get_order( $return['order'] );
    $this->log_to_file = get_option( 'msp_debug' );
    $this->return_policy = get_return_policy();
    $this->ups = get_ups();
    $this->usps = get_usps();
    $this->fedex = get_fedex();
    $this->return = $return;
  }

  function get_return_policy(){
    return array(
      'return_by' => get_option( 'msp_return_by' ),
      'email_to' => get_option( 'msp_send_return_email_to' )
    );
  }

  function get_ups(){
    return array(
      'creds'   => get_ups_api_creds(),
      'shipper' => get_ups_shipper(),
      'url'     => get_option( 'msp_ups_test_mode' ),
      // TODO: add return_service to options page
      'return_service' => '8',
      'validation' => get_option( 'msp_ups_validation_strictness' ),
      'shipment_settings' => get_ups_shipment_settings(),
    );
  }

  function get_ups_shipment_settings(){
    return array(
      'length' => get_option( 'msp_ups_box_dims_length' ),
      'width' => get_option( 'msp_ups_box_dims_width' ),
      'height' => get_option( 'msp_ups_box_dims_height' ),
      'units' => array(
        'dimensions' => get_option( 'msp_ups_box_dims_units' ),
        'weight' => get_option( 'msp_ups_box_weight_units' ),
      )
    );
  }

  function get_ups_api_creds(){
    return array(
      'key' => get_option( 'msp_ups_api_key' ),
      'user' => get_option( 'msp_ups_user_name' ),
      'password' => get_option( 'msp_ups_password' ),
    );
  }

  function get_ups_shipper(){
    return array(
      'account' => get_option( 'msp_ups_account_number' ),
      'name' => get_option( 'msp_ups_shipper_company_name' ),
      'display_name' => get_option( 'msp_ups_shipper_company_display_name' ),
      'attn' => get_option( 'msp_ups_shipper_attn' ),
      'phone' =>  get_option( 'msp_ups_shipper_phone' ),
      'tin' => get_option( 'msp_ups_shipper_tin' ),
      'address_1' => get_option( 'msp_ups_shipper_address_1' ),
      'address_2' => get_option( 'msp_ups_shipper_address_2' ),
      'city' => get_option( 'msp_ups_shipper_city' ),
      'state' => get_option( 'msp_ups_shipper_state' ),
      'postal_code' => get_option( 'msp_ups_shipper_postal_code' ),
      'country_code' => get_option( 'msp_ups_shipper_country_code' ),
    );
  }

  function get_fedex(){
    return array(
      'key'      => get_option( 'msp_fedex_api_key' ),
      'user'     => get_option( 'msp_usps_user_name' ),
      'password' => get_option( 'msp_usps_password' ),
    );
  }

  function get_usps(){
    return array(
      'user'     => get_option( 'msp_usps_user_name' ),
      'password' => get_option( 'msp_usps_password' ),
    );
  }

  	public function get_ups_shipment_request_xml(){
      /**
      *
      * creates and sends ups shipping confirm
      *
      */
  		$accessRequest = sc_ups_get_access_request_xml();
  		$shipmentConfirmRequest = get_ups_shipment_confirm_request();
  		$xml = $accessRequest->asXML() . $shipmentConfirmRequest->asXML();

  		$response = sc_get_xml_by_curl( 'https://'. $this->ups['url'] .'.ups.com/ups.app/xml/ShipConfirm', $xml );

  		if( $response['Response']['ResponseStatusCode'] ){
  			msp_shipment_accept_request( $response );
  		} else {
  			pre_dump( $response );
  			return $response['Response']['ResponseStatusDescription'];
  		}
  	}

    /**
    *
    * creates the msp_shipment_accept_request
    *
    */
  	function msp_shipment_accept_request( $response ){
  		$access_request = sc_ups_get_access_request_xml();
  		$shipment_accept_request = msp_create_shipment_accept_request( $response );
  		$xml = $access_request->asXML() . $shipment_accept_request->asXML();
  		$response = sc_get_xml_by_curl( 'https://'. get_option( 'msp_ups_test_mode' ) .'.ups.com/ups.app/xml/ShipAccept', $xml );

  		if( $response['Response']['ResponseStatusCode'] ){
  			pre_dump( $response );
  			// msp_save_ups_label( $response );
  		} else {
  			return $response['Response']['ResponseStatusDescription'];
  		}
  	}

    /**
    *
    * creates the ShipmentAcceptRequest xml
    *
    */
  	function msp_create_shipment_accept_request( $response ){
  		$accept = new SimpleXMLElement( '<ShipmentAcceptRequest></ShipmentAcceptRequest>' );

  		$accept->addChild( 'Request' );
  		$accept->Request->addChild( 'CustomerContext', $response['Response']['TransactionReference']['CustomerContext'] );
  		$accept->Request->addChild( 'RequestAction', 'ShipAccept' );
  		$accept->Request->addChild( 'RequestOption', '01' );

  		$accept->addChild( 'ShipmentDigest', $response['ShipmentDigest'] );

  		return $accept;
  	}


  	function get_ups_shipment_confirm_request(){
      /**
      *
      * creates the shipment xml
      *
      */
  		$shipmentConfirmRequest = new SimpleXMLElement('<ShipmentConfirmRequest></ShipmentConfirmRequest>');
  		$request = get_ups_shipment_action();
  		$shipment = msp_ups_get_shipment();
  		$label = msp_ups_get_label();

  		sxml_append( $shipmentConfirmRequest, $request );
  		sxml_append( $shipmentConfirmRequest, $shipment );
  		sxml_append( $shipmentConfirmRequest, $label );

  		return $shipmentConfirmRequest;
  	}

    /**
    *
    * creates the ups xml for label specification
    *
    */
  	public function msp_ups_create_label( ){
  		$label = new SimpleXMLElement('<LabelSpecification></LabelSpecification>');

  		$label->addChild( 'LabelPrintMethod' );
  		$label->LabelPrintMethod->addChild( 'Code', 'GIF' );
  		$label->LabelPrintMethod->addChild( 'Description', 'GIF' );

  		$label->addChild( 'LabelImageFormat' );
  		$label->LabelImageFormat->addChild( 'Code', 'GIF' );
  		$label->LabelImageFormat->addChild( 'Description', 'GIF' );

  		$label->addChild( 'HTTPUserAgent', $_SERVER['HTTP_USER_AGENT'] );

  		return $label;
  	}


    public function ups_add_label_options_to_xml( ){
      $shipment->addChild( 'ShipmentServiceOptions' );
      $shipment->ShipmentServiceOptions->addChild( 'LabelDelivery' );
      $shipment->ShipmentServiceOptions->LabelDelivery->addChild( 'EMailMessage' );
      $shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'EMailAddress', $this->order->get_billing_email() );
      $shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'FromEMailAddress', 'gregbast1994@gmail.com' );
      $shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'FromName', get_bloginfo( 'name' ) );
      $shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'Memo', 'Here\'s your shipping label!' );
      $shipment->ShipmentServiceOptions->LabelDelivery->EMailMessage->addChild( 'Subject', 'Here\'s your shipping label!' );
      return $shipment;
    }

  function ups_add_shipper_to_xml( $shipment ){
    $shipment->addChild( 'Shipper' );
    $shipment->Shipper->addChild( 'Name', $this->ups['name'] );
    $shipment->Shipper->addChild( 'AttentionName', $this->ups['attn'] );
    $shipment->Shipper->addChild( 'CompanyDisplayableName', $this->ups['display_name'] );
    $shipment->Shipper->addChild( 'PhoneNumber', $this->ups['phone'] );
    $shipment->Shipper->addChild( 'ShipperNumber', $this->ups['account'] );
    $shipment->Shipper->addChild( 'TaxIdentificationNumber', $this->ups['tin'] );

    $shipment->Shipper->addChild( 'Address' );
    $shipment->Shipper->Address = ups_add_address_to_xml( $shipment->ShipTo->Address );

    return $shipment;
  }

  function ups_add_shipto_to_xml( $shipment ){
    $shipment->addChild( 'ShipTo' );
    $shipment->ShipTo->addChild( 'CompanyName', $this->ups['msp_ups_shipper_company_name'] );
    $shipment->ShipTo->addChild( 'AttentionName', $this->ups['msp_ups_shipper_attn'] );
    $shipment->ShipTo->addChild( 'PhoneNumber', $this->ups['msp_ups_shipper_phone'] );

    $shipment->ShipTo->addChild( 'Address' );
    $shipment->ShipTo->Address = ups_add_address_to_xml( $shipment->ShipTo->Address );

    return $shipment;
  }

  function ups_add_address_to_xml( $xml ){
    $xml->addChild( 'AddressLine1', $this->ups['address_1'] );
    $xml->addChild( 'AddressLine2', $this->ups['address_2'] );
    $xml->addChild( 'City', $this->ups['city'] );
    $xml->addChild( 'StateProvinceCode', $this->ups['state'] );
    $xml->addChild( 'PostalCode', $this->ups['postal_code'] );
    $xml->addChild( 'CountryCode', $this->ups['country_code'] );
    return $xml;
  }

  public function ups_add_shipfrom_to_xml( $shipment ){
    $shipment->addChild( 'ShipFrom' );
    $shipment->ShipFrom->addChild( 'CompanyName', $this->order->get_billing_company() );
    $shipment->ShipFrom->addChild( 'AttentionName', $data['name'] );
    $shipment->ShipFrom->addChild( 'AttentionName', $this->order->get_billing_phone() );

    $shipment->ShipFrom->addChild( 'Address' );
    $shipment->ShipFrom->Address->addChild( 'AddressLine1', $this->order->get_shipping_address_1() );
    $shipment->ShipFrom->Address->addChild( 'AddressLine2', $this->order->get_shipping_address_2() );
    $shipment->ShipFrom->Address->addChild( 'City', $this->order->get_shipping_city() );
    $shipment->ShipFrom->Address->addChild( 'StateProvinceCode', $this->order->get_shipping_state() );
    $shipment->ShipFrom->Address->addChild( 'PostalCode', $this->order->get_shipping_postcode() );
    $shipment->ShipFrom->Address->addChild( 'CountryCode', $this->order->get_shipping_country() );
    return;
  }

  if( ! function_exists( 'msp_ups_get_shipment' ) ){
    /**
    *
    * creates the shipment xml
    *
  	*/
  	function msp_ups_get_shipment( $data ){
  		$shipment = new SimpleXMLElement( '<Shipment></Shipment>' );

  		// TODO: Add return service options
  		$shipment->addChild( 'ReturnService' );
  		$shipment->ReturnService->addChild( 'Code', $this->ups['return_service'] );

      if( $this->return_service === '8' ){
        $shipment = ups_add_label_options_to_xml( $shipment );
      }
  		return ups_add_the_shipment_to_xml( $shipment );
  	}
  }

  public function ups_add_the_shipment_to_xml( $shipment ){
    $shipment = ups_add_shipper_to_xml( $shipment );
    $shipment = ups_add_shipto_to_xml( $shipment );
    $shipment = ups_add_shipfrom_to_xml( $shipment );
    $shipment = ups_add_payment_info_to_xml( $shipment );
    $shipment = ups_add_package_to_xml( $shipment );
    return $shipment
  }

  function ups_add_package_to_xml( $shipment ){
    $shipment->addChild( 'Package' );
    // TODO: Add more detail to the order return.
    $shipment->Package->addChild( 'Description', msp_get_store_description() );

    $shipment->Package->addChild( 'PackagingType' );
    // TODO: Add option
    $shipment->Package->PackagingType->addChild( 'Code', '02' );
    $shipment->Package->PackagingType->addChild( 'Description', 'Customer Supplied Package' );

    $shipment->Package->addChild( 'Dimensions' );
    $shipment->Package->Dimensions->addChild( 'UnitOfMeasurement' );
    $shipment->Package->Dimensions->UnitOfMeasurement->addChild( 'Code', $this->ups['shipment_settings']['units']['dimensions'] );
    $shipment->Package->Dimensions->addChild( 'Length', $this->ups['shipment_settings']['length'] );
    $shipment->Package->Dimensions->addChild( 'Width', $this->ups['shipment_settings']['width'] );
    $shipment->Package->Dimensions->addChild( 'Height', $this->ups['shipment_settings']['height'] );

    $shipment->Package->addChild( 'PackageWeight' );
    $shipment->Package->PackageWeight->addChild( 'UnitOfMeasurement' );
    $shipment->Package->PackageWeight->UnitOfMeasurement->addChild( 'Code', $this->ups['shipment_settings']['units']['weight'] );
    $shipment->Package->PackageWeight->addChild( 'Weight', msp_get_package_weight( $this->returns['items'] ) );

    return $shipment;
  }

  /**
  *
  * returns an educated guess of package weight
	* @param array $items - items the user wishes to return, and data about those items
	* @param int $weight - a quick estimation of package weight using item weight + qty
  *
  */
	function msp_get_package_weight( $items ){
		$weight = 8;
		foreach( $items as $item ){
			$item_weight = $item['weight'] * $item['qty'];
			$weight += $item_weight;
		}

		$convert_unit = $this->ups['shipment_settings']['units']['weight'];

		if( $convert_unit == 'OZS' ){
			return $weight / 16;
		} else if( $convert_unit == 'KGS' ){
			return $weight * 2.205;
		} else {
			return $weight;
		}

	}

  /**
	*
	* gets store
	*
	*/
	function msp_get_store_description( ){
		return substr( get_bloginfo( 'name' ) . ' #: ' . $this->order->get_id(), 0, 35 );
	}

  function ups_add_payment_info_to_xml( $shipment ){
    $shipment->addChild( 'Service' );
    $shipment->Service->addChild( 'Code', '03' );
    // TODO: Add option?
    $shipment->Service->addChild( 'Description', 'Ground' );

    return $shipment;
  }

  if( ! function_exists( 'get_ups_shipment_action' ) ){
    /**
    *
    * creates the shipment xml
    *
    */
  	function get_ups_shipment_action( ){
  		$request = new SimpleXMLElement( '<Request></Request>' );
  		$request->addChild( 'TransactionReference');
  		$request->TransactionReference->addChild( 'CustomerContext', $this->order->get_id() );
  		$request->addChild( 'RequestAction', 'ShipConfirm' );
  		$request->addChild( 'RequestOption', $this->ups['validation'] );
  		return $request;
  	}
  }

  if( ! function_exists( 'sc_ups_get_access_request_xml' ) ){
    /**
    *
    * generate ups api credentials in XML format
    * @return object $accessRequest - SimpleXMLElement of ups security credentials
    */
    function sc_ups_get_access_request_xml(){
      $accessRequest = new SimpleXMLElement('<AccessRequest></AccessRequest>');
      $accessRequest->addChild( 'AccessLicenseNumber', $this->ups_api_key; );
      $accessRequest->addChild( 'UserId', $this->ups_user_name );
      $accessRequest->addChild( 'Password', $this->ups_password );
      return $accessRequest;
    }
  }

  if( ! function_exists( 'get_store_description' ) ){
  	/**
  	*
  	* gets store name and order number
  	* @param string order_id
  	* @return string storename and order id ( Limit 35 Chars )
  	*
  	*/
  	public function get_store_description( order_id ){
  		return substr( get_bloginfo( 'name' ) . ' #: ' . order_id, 0, 35 );
  	}

  }

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
