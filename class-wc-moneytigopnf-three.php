<?php
class WC_MoneyTigoPnfThree extends WC_Payment_Gateway {
  /**
   * Construction of MoneyTigo three-step payment method
   *
   * @return void
   */
  public function __construct() {
    global $woocommerce;
    $this->version = moneytigo_universale_params()['Version'];
    $this->id = 'moneytigopnfthree';
    $this->icon = moneytigo_get_file("assets/img/carte.png");
    $this->init_form_fields();

    // Load the settings.
    $this->init_settings();
    $this->has_fields = false;
    // Load parent configuration
    $parentConfiguration = get_option( 'woocommerce_moneytigo_settings' );
    $this->method_title = __( 'MoneyTigo - Payment in three instalments', 'moneytigo_woocommerce' );

    // Define user set variables.
    $this->title = $this->settings[ 'title' ];
    $this->instructions = $this->get_option( 'instructions' );
    $this->method_description = __( 'Accept payment in three instalments! <a href="https://app.moneytigo.com/user/register">Open an account now !</a>', 'moneytigo_woocommerce' );
    $this->moneytigo_gateway_api_key = $parentConfiguration[ 'moneytigo_gateway_api_key' ];
    $this->moneytigo_gateway_secret_key = $parentConfiguration[ 'moneytigo_gateway_secret_key' ];

    // Actions.
    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( & $this, 'process_admin_options' ) );
    } else {
      add_action( 'woocommerce_update_options_payment_gateways', array( & $this, 'process_admin_options' ) );
    }

  }
  /* Admin Panel Options.*/
  public function admin_options() {
    ?>
<h3>
  <?php _e('MoneyTigo configuration', 'moneytigo_woocommerce'); ?>
</h3>
<div class="simplify-commerce-banner updated"> <img src="<?php echo moneytigo_get_file("assets/img/moneytigo.png"); ?>" />
  <p class="main"><strong><?php echo __('Accepts payments by credit card with MoneyTigo', 'moneytigo_woocommerce'); ?></strong></p>
  <p><?php echo __('MoneyTigo is a secure payment solution on the Internet. As a virtual POS (Electronic Payment Terminal), MoneyTigo makes it possible to cash payments made on the Internet 24 hours a day, 7 days a week. This service relieves your site of the entire payment phase; the latter takes place directly on our secure payment platform.', 'moneytigo_woocommerce'); ?></p>
  <p><?php echo __('For any problem or information contact: hello@moneytigo.com', 'moneytigo_woocommerce'); ?></p>
  <p><a href="https://www.moneytigo.com" target="_blank" class="button button-primary"><?php echo __('Get a MoneyTigo account', 'moneytigo_woocommerce'); ?></a> <a href="https://app.moneytigo.com/user/register" target="_blank" class="button"><?php echo __('Test free', 'moneytigo_woocommerce'); ?></a> <a href="https://www.moneytigo.com" target="_blank" class="button"><?php echo __('Official site', 'moneytigo_woocommerce'); ?></a> <a href="https://www.moneytigo.com" target="_blank" class="button"><?php echo __('Documentation', 'moneytigo_woocommerce'); ?></a></p>
</div>
<div class="simplify-commerce-banner error">
  <p class="main" style="color:red;"><strong> <?php echo __('If you want your customer to be automatically redirected to your site once the payment is accepted or failed, consider activating this option directly in the configuration of your website in your MoneyTigo DashBoard', 'moneytigo_woocommerce'); ?> </strong></p>
</div>
<table class="form-table">
  <?php $this->generate_settings_html(); ?>
</table>
<?php
}


/* Initialise Gateway Settings Form Fields for ADMIN. */
public function init_form_fields() {
  global $woocommerce;


  $this->form_fields = array(
    'enabled' => array(
      'title' => __( 'Enable / Disable', 'moneytigo_woocommerce' ),
      'type' => 'checkbox',
      'label' => __( 'Activate payment in three instalments', 'moneytigo_woocommerce' ),

    ),
    'seuil' => array(
      'title' => __( 'Minimum amount', 'moneytigo_woocommerce' ),
      'type' => 'text',
      'label' => __( 'Indicate the minimum amount of the cart to be able to use this payment method (Minimum 50 EUR)', 'moneytigo_woocommerce' ),
      'desc_tip' => __( 'Define from which amount you want your customer to be able to use this payment method.', 'moneytigo_woocommerce' ),
      'default' => '50'
    ),
    'title' => array(
      'title' => __( 'Method title', 'moneytigo_woocommerce' ),
      'type' => 'text',
      'description' => __( 'This is the name displayed on your checkout', 'moneytigo_woocommerce' ),
      'desc_tip' => true,
      'default' => __( 'Credit card in three instalments', 'moneytigo_woocommerce' )
    ),
    'description' => array(
      'title' => __( 'Message before payment', 'moneytigo_woocommerce' ),
      'type' => 'textarea',
      'description' => __( 'Message that the customer sees when he chooses this payment method', 'moneytigo_woocommerce' ),
      'desc_tip' => true,
      'default' => __( 'You will be redirected to our secure server to make your payment', 'moneytigo_woocommerce' )
    )
  );
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
    'MerchantKey' => $this->moneytigo_gateway_api_key,
    'amount' => $order->get_total(),
    'RefOrder' => $order_id,
    'Customer_Email' => $email,
    'Customer_FirstName' => $custo_firstname,
    'Customer_LastName' => $custo_lastname,
    'Lease' => '3',
    'urlOK' => get_site_url() . '/?wc-api=wc_moneytigo_return&mtg_ord=' . $order_id . '',
    'urlKO' => get_site_url() . '/?wc-api=wc_moneytigo_return&mtg_ord=' . $order_id . '',
    'urlIPN' => get_site_url() . '/?wc-api=wc_moneytigo',
  );
  $getToken = $this->getToken( $requestToken );
  if ( !is_wp_error( $getToken ) ) {
    $results = json_decode( $getToken[ 'body' ], true );
    if ( $getToken[ 'response' ][ 'code' ] === 200 ) {
      wc_add_notice( __( 'MoneyTigo : ' . $results[ 'Error_Code' ] . ' - ' . $results[ 'Short_Description' ] . ' - ' . $results[ 'Full_Description' ] . '', 'moneytigo_woocommerce' ), 'error' );
      return;
    } else if ( $getToken[ 'response' ][ 'code' ] === 400 ) {
      wc_add_notice( __( 'MoneyTigo : ' . $results[ 'ErrorCode' ] . ' - ' . $results[ 'ErrorDescription' ] . '', 'moneytigo_woocommerce' ), 'error' );
      return;
    } else if ( $getToken[ 'response' ][ 'code' ] === 201 ) {
      return array(
        'result' => 'success',
        'redirect' => moneytigo_universale_params()[ 'WebUriInstallment' ] . $results[ 'SACS' ]
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

