<?php
class WC_MoneyTigo extends WC_Payment_Gateway {
  /**
   * Construction of the classical method
   *
   * @return void
   */
  public function __construct() {
    global $woocommerce;
    $this->version = moneytigo_universale_params()[ 'Version' ];
    $this->id = 'moneytigo';

    $this->icon = moneytigo_get_file( "assets/img/carte.png" );

    $this->init_form_fields();


    // Load the settings.
    $this->init_settings();
    $this->has_fields = false;

    $this->method_title = __( 'MoneyTigo', 'moneytigo' );


    // Define user set variables.
    $this->title = $this->settings[ 'title' ];
    $this->instructions = $this->get_option( 'instructions' );
    $this->method_description = __( 'Accept credit cards in less than 5 minutes. <a href="https://app.moneytigo.com/user/register">Open an account now !</a>', 'moneytigo' );
    $this->moneytigo_gateway_api_key = $this->settings[ 'moneytigo_gateway_api_key' ];
    $this->moneytigo_gateway_secret_key = $this->settings[ 'moneytigo_gateway_secret_key' ];

    // Actions.
    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( & $this, 'process_admin_options' ) );
    } else {
      add_action( 'woocommerce_update_options_payment_gateways', array( & $this, 'process_admin_options' ) );
    }
    // Add listener IPN Function
    add_action( 'woocommerce_api_wc_moneytigo', array( $this, 'moneytigo_notification' ) );
    // Add listener Customer Return after payment and IPN result !
    add_action( 'woocommerce_api_wc_moneytigo_return', array( $this, 'moneytigo_return' ) );


  }
  /* Admin Panel Options.*/
  public function admin_options() {
    ?>
<h3>
  <?php _e('MoneyTigo configuration', 'moneytigo'); ?>
</h3>
<div class="simplify-commerce-banner updated"> <img src="<?php echo moneytigo_get_file("assets/img/moneytigo.png"); ?>" />
  <p class="main"><strong><?php echo __('Accepts payments by credit card with MoneyTigo', 'moneytigo'); ?></strong></p>
  <p><?php echo __('MoneyTigo is a secure payment solution on the Internet. As a virtual POS (Electronic Payment Terminal), MoneyTigo makes it possible to cash payments made on the Internet 24 hours a day, 7 days a week. This service relieves your site of the entire payment phase; the latter takes place directly on our secure payment platform.', 'moneytigo'); ?></p>
  <p><?php echo __('For any problem or information contact: hello@moneytigo.com', 'moneytigo'); ?></p>
  <p><a href="https://www.moneytigo.com" target="_blank" class="button button-primary"><?php echo __('Get a MoneyTigo account', 'moneytigo'); ?></a> <a href="https://app.moneytigo.com/user/register" target="_blank" class="button"><?php echo __('Test free', 'moneytigo'); ?></a> <a href="https://www.moneytigo.com" target="_blank" class="button"><?php echo __('Official site', 'moneytigo'); ?></a> <a href="https://www.moneytigo.com" target="_blank" class="button"><?php echo __('Documentation', 'moneytigo'); ?></a></p>
</div>
<div class="simplify-commerce-banner error">
  <p class="main" style="color:red;"><strong> <?php echo __('If you want your customer to be automatically redirected to your site once the payment is accepted or failed, consider activating this option directly in the configuration of your website in your MoneyTigo DashBoard', 'moneytigo'); ?> </strong></p>
</div>
<table class="form-table">
  <?php $this->generate_settings_html(); ?>
</table>
<?php
}

/* API Woocommerce for IPN Request after paid MONEYTIGO */
/* @Call to POST => merchanturi/?wc... */
/* @Callback getTransactionInfo function*/
public function moneytigo_notification() {
  global $woocommerce;
  /*Validate & Immediate Securing*/
  if ( isset( $_POST[ 'TransId' ] ) ) {
    $TransactionId = sanitize_text_field( $_POST[ 'TransId' ] );
  } else {
    /* Display for DEBUG Mod */
    echo "The transaction id is not transmitted";
    error_log( 'MONEYTIGO IPN Error : The transaction id is not transmitted' );
    exit();
  }

  /* Securise Api Request */
  $Request = $this->signRequest( array(
    'ApiKey' => $this->moneytigo_gateway_api_key,
    'TransID' => $TransactionId
  ) );
  /* Validate Transaction ID */
  $result = json_decode( $this->getTransactions( $Request )[ 'body' ], true );
  if ( isset( $result[ 'ErrorCode' ] ) ) {
    /* If error return error log for mode debug and stop process */
    echo 'MONEYTIGO IPN Error : ' . esc_attr( $result[ 'ErrorCode' ] ) . '-' . esc_attr( $result[ 'ErrorDescription' ] ) . '';
    error_log( 'MONEYTIGO IPN Error : ' . $result[ 'ErrorCode' ] . '-' . $result[ 'ErrorDescription' ] . '' );
    exit();
  }

  /* MoneyTigo gives a result we check that the order exists */
  $order = new WC_Order( $result[ 'Merchant_Order_Id' ] );
  /* check if order exists */
  if ( !$order ) {
    echo 'MONEYTIGO IPN Error : Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' not found';
    error_log( 'MONEYTIGO IPN Error : Order ' . $result[ 'Merchant_Order_Id' ] . ' not found' );
    exit();
  }
  /* check that the order is not already paid */
  if ( $order->is_paid() ) {
    echo 'MONEYTIGO IPN Error : Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' already paid';
    error_log( 'MONEYTIGO IPN Error : Order ' . $result[ 'Merchant_Order_Id' ] . ' already paid' );
    exit();
  }
  /* check that the order is not completed */
  if ( $order->has_status( 'completed' ) ) {
    echo 'MONEYTIGO IPN Error : Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' is completed';
    error_log( 'MONEYTIGO IPN Error : Order ' . $result[ 'Merchant_Order_Id' ] . ' is completed' );
    exit();
  }
  /* check that the order is not already in processing */
  if ( $order->has_status( 'processing' ) ) {
    echo 'MONEYTIGO IPN Error : Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' is in processig';
    error_log( 'MONEYTIGO IPN Error : Order ' . $result[ 'Merchant_Order_Id' ] . ' is in processing' );
    exit();
  }
  /* check that the order is not refunded */
  if ( $order->has_status( 'refunded' ) ) {
    echo 'MONEYTIGO IPN Error : Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' is refunded';
    error_log( 'MONEYTIGO IPN Error : Order ' . $result[ 'Merchant_Order_Id' ] . ' is refunded' );
    exit();
  }

  if ( $result[ 'Transaction_Status' ][ 'State' ] == 2 ) {
    $order->payment_complete( $result[ 'Bank' ][ 'Internal_IPS_Id' ] );
    /* Reduction of the stock */
    wc_reduce_stock_levels( $result[ 'Merchant_Order_Id' ] );


    /* Add a note on the order to say that the order is confirmed */
    $order->add_order_note( 'Payment by MoneyTigo credit card accepted (IPN)', true );

    /* We empty the basket */
    $woocommerce->cart->empty_cart();
    echo 'Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' was successfully completed !';
    exit();
  } else {
    /* Payment declined or cancelled */
    $order->update_status( 'failed', __( 'MoneyTigo - Transaction ' . $TransactionId . ' - FAILED (' . $result[ 'Transaction_Status' ][ 'Bank_Code_Description' ] . ')', 'moneytigo' ) );
    echo 'Order ' . esc_attr( $result[ 'Merchant_Order_Id' ] ) . ' was successfully cancelled !';
    exit();
  }

  echo "Unknown error";
  exit();

}

/* return page for customer after order front return /?wc-api=wc_moneytigo_return&actionreturn=success&mtg_ord= */
/* based on orderID and status of order */
/* if ipn have validated order and order is processing this is order completed if not return to cart */
/* if not order or not order id transmitted, return to base url shop*/
/* Added Double check order validity */

public function moneytigo_return() {
  global $woocommerce;

  /*Default Url*/
  $returnUri = wc_get_checkout_url();
  /*Securing*/
  $order_id = sanitize_text_field( $_GET[ 'mtg_ord' ] );
  /*Prevalidate*/
  if ( $order_id < 1 ) {
    return;
  }
  /*Validation*/
  $WcOrder = new WC_Order( $order_id );
  if ( !$WcOrder ) {
    return;
  };

  /*Check if the payment method is MoneyTigo for this order */
  if ( $WcOrder->get_payment_method() !== "moneytigo" && $WcOrder->get_payment_method() !== "moneytigopnftwo" & $WcOrder->get_payment_method() !== "moneytigopnfthree" && $WcOrder->get_payment_method() !== "moneytigopnffour" ) {
    return;
  }


  /*Checking Order Status*/
  if ( $WcOrder->get_status() === 'pending' ) {

    /* If the order is still pending, then we call the MoneyTigo webservice to check the payment again and update the order status */
    $Request = $this->signRequest( array(
      'ApiKey' => $this->moneytigo_gateway_api_key,
      'MerchantOrderId' => $order_id
    ) );
    $checkTransaction = json_decode( $this->getPaymentByOrderID( $Request )[ 'body' ], true );

    /* If an error code is returned then we redirect the client indicating the problem */
    if ( isset( $checkTransaction[ 'ErrorCode' ] ) ) {
      error_log( 'MONEYTIGO API Error @moneytigo_return : ' . $checkTransaction[ 'ErrorCode' ] . ' : ' . $checkTransaction[ 'ErrorDescription' ] . '' );
      wc_add_notice( __( 'An internal error occurred', 'moneytigo' ), 'error' );
    }

    /* All is ok so we finish the final process */
    $transactionStatuts = $checkTransaction[ 'Transaction_Status' ][ 'State' ];
    if ( $transactionStatuts == "2" ) {
      /* transaction approved */
      /* Record the payment with the transaction number */
      $WcOrder->payment_complete( $checkTransaction[ 'Bank' ][ 'Internal_IPS_Id' ] );
      /* Reduction of the stock */
      wc_reduce_stock_levels( $order_id );
      /* Add a note on the order to say that the order is confirmed */
      $WcOrder->add_order_note( 'Payment by MoneyTigo credit card accepted', true );

      /* We empty the basket */
      $woocommerce->cart->empty_cart();
      $returnUri = $this->get_return_url( $WcOrder );
    } else {
      if ( $transactionStatuts == "6" ) {
        /* The transaction is still pending */
        /* A message is displayed to the customer asking him to be patient */
        /* We make it wait 10 seconds then we refresh the page */
        echo __( 'Please wait a few moments ...', 'moneytigo' );
        header( "Refresh:10" );
        exit();
      } else {
        /* La transaction est annulé ou refusé */
        /* The customer is redirected to the shopping cart page with the rejection message */
        /* Redirect the customer to the shopping cart and indicate that the payment is declined */
        wc_add_notice( __( 'Sorry, your payment was declined !', 'moneytigo' ), 'error' );
      }
    }


  } else {

    /* The answer from moneytigo has already arrived (IPN) */
    /* Redirect the customer to the thank you page if the order is well paid */
    if ( $WcOrder->get_status() === 'processing' ) {
      $returnUri = $this->get_return_url( $WcOrder );
    } else {
      /* Redirect the customer to the shopping cart and indicate that the payment is declined */
      wc_add_notice( __( 'Sorry, your payment was declined !', 'moneytigo' ), 'error' );
    }

  }

  /* Redirect to thank you or decline page */
  wp_redirect( $returnUri );
  exit();
}


/* Initialise Gateway Settings Form Fields for ADMIN. */
public function init_form_fields() {
  global $woocommerce;


  $this->form_fields = array(
    'enabled' => array(
      'title' => __( 'Enable / Disable', 'moneytigo' ),
      'type' => 'checkbox',
      'label' => __( 'Activate card payment with MoneyTigo', 'moneytigo' ),
      'default' => 'no'
    ),
    'title' => array(
      'title' => __( 'Method title', 'moneytigo' ),
      'type' => 'text',
      'description' => __( 'This is the name displayed on your checkout', 'moneytigo' ),
      'desc_tip' => true,
      'default' => __( 'Credit card payment', 'moneytigo' )
    ),
    'description' => array(
      'title' => __( 'Message before payment', 'moneytigo' ),
      'type' => 'textarea',
      'description' => __( 'Message that the customer sees when he chooses this payment method', 'moneytigo' ),
      'desc_tip' => true,
      'default' => __( 'You will be redirected to our secure server to make your payment', 'moneytigo' )
    ),
    'moneytigo_gateway_api_key' => array(
      'title' => __( 'Your API Key', 'moneytigo' ),
      'description' => __( 'To obtain it go to the configuration of your merchant contract (section "Merchant account").', 'moneytigo' ),
      'type' => 'text'
    ),
    'moneytigo_gateway_secret_key' => array(
      'title' => __( 'Your Secret Key', 'moneytigo' ),
      'description' => __( 'To obtain it go to the configuration of your merchant contract (section "Merchant account").', 'moneytigo' ),
      'type' => 'text'
    )
  );
}

/* Retrieve transaction details from the transaction PSP id */
private function getTransactions( $arg ) {
  $response = wp_remote_get( moneytigo_universale_params()[ 'ApiGetTransaction' ] . '?TransID=' . $arg[ "TransID" ] . '&SHA=' . $arg[ "SHA" ] . '&ApiKey=' . $arg[ "ApiKey" ] . '' );
  return $response;
}

/* Retrieve transaction details from the order ID */
private function getPaymentByOrderID( $arg ) {


  $response = wp_remote_get( moneytigo_universale_params()[ 'ApiGetTransactionByOrderId' ] . '?MerchantOrderId=' . $arg[ "MerchantOrderId" ] . '&SHA=' . $arg[ "SHA" ] . '&ApiKey=' . $arg[ "ApiKey" ] . '' );
  return $response;
}
/* Request authorization token from MoneyTigo */
/* Private function only accessible to internal execution */
private function getToken( $args ) {
  $ConstructArgs = array(
    'headers' => array(
      'Content-type: application/x-www-form-urlencoded'
    ),
    'sslverify' => false,
    'body' => $this->signRequest( $args )
  );
  $response = wp_remote_post( moneytigo_universale_params()[ 'ApiInitPayment' ], $ConstructArgs );
  return $response;
}
/* Signature of parameters with your secret key before sending to MoneyTigo */
/* Private function only accessible to internal execution */
private function signRequest( $params, $beforesign = "" ) {
  $ShaKey = $this->moneytigo_gateway_secret_key;
  foreach ( $params as $key => $value ) {
    $beforesign .= $value . "!";
  }
  $beforesign .= $ShaKey;
  $sign = hash( "sha512", base64_encode( $beforesign . "|" . $ShaKey ) );
  $params[ 'SHA' ] = $sign;
  return $params;
}


/* Payment processing and initiation
 Redirection of the client if the initiation is successful 
 Display of failures on the checkout page in case of error */
public function process_payment( $order_id ) {
  //obtain token for payment processing
  global $woocommerce;
  $order = new WC_Order( $order_id );
  $email = method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
  $custo_firstname = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
  $custo_lastname = method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;
  $the_order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
  $the_order_key = method_exists( $order, 'get_order_key' ) ? $order->get_order_key() : $order->order_key;
  $requestToken = array(
    'MerchantKey' => $this->get_option( 'moneytigo_gateway_api_key' ),
    'amount' => $order->get_total(),
    'RefOrder' => $order_id,
    'Customer_Email' => $email,
    'Customer_FirstName' => $custo_firstname,
    'Customer_LastName' => $custo_lastname,
    'urlOK' => get_site_url() . '/?wc-api=wc_moneytigo_return&mtg_ord=' . $order_id . '',
    'urlKO' => get_site_url() . '/?wc-api=wc_moneytigo_return&mtg_ord=' . $order_id . '',
    'urlIPN' => get_site_url() . '/?wc-api=wc_moneytigo',
  );
  $getToken = $this->getToken( $requestToken );
  if ( !is_wp_error( $getToken ) ) {
    $results = json_decode( $getToken[ 'body' ], true );
    if ( $getToken[ 'response' ][ 'code' ] === 200 ) {
      wc_add_notice( __( 'MoneyTigo : ' . $results[ 'Error_Code' ] . ' - ' . $results[ 'Short_Description' ] . ' - ' . $results[ 'Full_Description' ] . '', 'moneytigo' ), 'error' );
      return;
    } else if ( $getToken[ 'response' ][ 'code' ] === 400 ) {
      wc_add_notice( __( 'MoneyTigo : ' . $results[ 'ErrorCode' ] . ' - ' . $results[ 'ErrorDescription' ] . '', 'moneytigo' ), 'error' );
      return;
    } else if ( $getToken[ 'response' ][ 'code' ] === 201 ) {
      return array(
        'result' => 'success',
        'redirect' => moneytigo_universale_params()[ 'WebUriStandard' ] . $results[ 'SACS' ]
      );
    } else {
      wc_add_notice( 'MoneyTigo : Connection error', 'error' );
      return;
    }
  } else {
    wc_add_notice( 'MoneyTigo : Connection error', 'error' );
    return;
  }
}


}
?>
