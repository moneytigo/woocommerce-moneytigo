<?php
class WC_MoneyTigo extends WC_Payment_Gateway {
  /**
   * Construction of the classical method
   *
   * @return void
   */
  public function __construct() {
    global $woocommerce;
    $this->version = '1.0.3';
    $this->id = 'moneytigo';
    $this->icon = apply_filters( 'woocommerce_moneytigo_icon', get_site_url() . '/wp-content/plugins/woocommerce-moneytigo/assets/img/carte.png' );
    $this->has_fields = false;
    $this->method_title = __( 'MoneyTigo', 'moneytigo_woocommerce' );

    // Load the form fields.
    $this->init_form_fields();

    // Load the settings.
    $this->init_settings();

    // Define user set variables.
    $this->title = $this->settings[ 'title' ];
    $this->description = $this->settings[ 'description' ];
    $this->instructions = $this->get_option( 'instructions' );
    $this->method_description = __( 'Accept credit cards in less than 5 minutes. <a href="https://app.moneytigo.com/user/register">Open an account now !</a>', 'moneytigo_woocommerce' );
    $this->moneytigo_gateway_api_key = $this->settings[ 'moneytigo_gateway_api_key' ];
    $this->moneytigo_gateway_secret_key = $this->settings[ 'moneytigo_gateway_secret_key' ];
    $this->moneytigo_gateway_api_uri = "https://payment.moneytigo.com";
	$this->moneytigo_gateway_get_transaction_uri = "https://payment.moneytigo.com/transactions/";
	$this->moneytigo_gateway_library_uri = "https://payment.moneytigo.com/6598874bb8d7bfdb56df4b5d6f4b56d/js/IPSSDK-Cms.js";
	$this->moneytigo_gateway_create_transaction_uri = "https://payment.moneytigo.com/init_transactions/";

    // Actions.
    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( & $this, 'process_admin_options' ) );
    } else {
      add_action( 'woocommerce_update_options_payment_gateways', array( & $this, 'process_admin_options' ) );
    }
	
	// Add Payment generation form with Iframe embedded
    add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	// Add listener IPN Function
	add_action('woocommerce_api_wc_moneytigo', array($this, 'moneytigo_notification'));
	// Add listener Customer Return after payment and IPN result !
	add_action('woocommerce_api_wc_moneytigo_return', array($this, 'moneytigo_return'));
	
	
	
 

  }
  /* Admin Panel Options.*/
  public function admin_options() {
    ?>
<h3>
  <?php _e('MoneyTigo configuration', 'moneytigo_woocommerce'); ?>
</h3>
<div class="simplify-commerce-banner updated"> <img src="<?php echo get_site_url().'/wp-content/plugins/woocommerce-moneytigo/assets/img/moneytigo.png'; ?>" />
  <p class="main"><strong><?php echo __('Accepts payments by credit card with MoneyTigo', 'moneytigo_woocommerce'); ?></strong></p>
  <p><?php echo __('MoneyTigo is a secure payment solution on the Internet. As a virtual POS (Electronic Payment Terminal), MoneyTigo makes it possible to cash payments made on the Internet 24 hours a day, 7 days a week. This service relieves your site of the entire payment phase; the latter takes place directly on our secure payment platform.', 'moneytigo_woocommerce'); ?></p>
  <p><?php echo __('For any problem or information contact: hello@moneytigo.com', 'moneytigo_woocommerce'); ?></p>
  <p><a href="https://www.moneytigo.com" target="_blank" class="button button-primary"><?php echo __('Get a MoneyTigo account', 'moneytigo_woocommerce'); ?></a> <a href="https://app.moneytigo.com/user/register" target="_blank" class="button"><?php echo __('Test free', 'moneytigo_woocommerce'); ?></a> <a href="https://www.moneytigo.com" target="_blank" class="button"><?php echo __('Official site', 'moneytigo_woocommerce'); ?></a> <a href="https://www.moneytigo.com" target="_blank" class="button"><?php echo __('Documentation', 'moneytigo_woocommerce'); ?></a></p>
</div>
<div class="simplify-commerce-banner error">
<p class="main" style="color:red;"><strong>	
<?php echo __('If you want your customer to be automatically redirected to your site once the payment is accepted or failed, consider activating this option directly in the configuration of your website in your MoneyTigo DashBoard', 'moneytigo_woocommerce'); ?>
	</strong></p>
</div>
<table class="form-table">
  <?php $this->generate_settings_html(); ?>
</table>
<?php
}

 

/* return page for customer after order front return /?wc-api=wc_moneytigo_return&actionreturn=success&mtg_ord= */
/* based on orderID and status of order */
/* if ipn have validated order and order is processing this is order completed if not return to cart */
/* if not order or not order id transmitted, return to base url shop*/
/* Added Double check order validity */
public function moneytigo_return()
{
	global $woocommerce;
	if($_GET['mtg_ord']) { 
	$orderIDMTG = $_GET['mtg_ord'];
	$order = new WC_Order($orderIDMTG);
	if(!$order) {
	header("Location: ".get_site_url());
	}
	}
	else
	{
	 header("Location: ".get_site_url());
	}
	if($order->{'data'}['status'] == "processing")
{
	/* redirect to official complete order page */
	header("Location: ".$order->get_checkout_order_received_url());
}
else
{
	/* order not processing = order failed */
	/* add message declined payment */
	wc_add_notice(__('Sorry, your payment was declined !', 'moneytigo_woocommerce'), 'error');
	/* redirect to checkout cart url */
	header("Location: ".wc_get_cart_url());
}
}
/* Transaction info from MoneyTigo API */
/* @Call to GET => transactions */
/* @Vars MerchantKey ++ TransID ++ EncryptionKey*/
public function getTransactionInfo($TransID)
{
$MerchantKey = $this->get_option('moneytigo_gateway_api_key');
$ShaKey = $this->get_option('moneytigo_gateway_secret_key');

$beforesign = $MerchantKey."!".$TransID."!".$ShaKey;
$sign = hash("sha512", base64_encode($beforesign."|".$ShaKey));
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, ''.$this->moneytigo_gateway_get_transaction_uri.'?TransID='.$TransID.'&ApiKey='.$MerchantKey.'&SHA='.$sign.'');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$headers = array();
$headers[] = 'Accept: application/json';
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
if (curl_errno($ch)) {
	echo 'Error:' . curl_error($ch);
}
curl_close($ch);
$result = json_decode($result);	
return $result;
}
/* API Woocommerce for IPN Request after paid MONEYTIGO */
/* @Call to POST => merchanturi/?wc... */
/* @Callback getTransactionInfo function*/
public function moneytigo_notification()
{
global $woocommerce;

/* IPS Sent in POST Vars Transaction ID */
$TransactionId = $_POST['TransId'];

/* Stop process if no TransID */
if(!$TransactionId) { die("Invalid call, Transaction ID is not transmitted."); }
$result = $this->getTransactionInfo($TransactionId);
 

/* Check if good answer from IPS Api */
if(!$result->{'ErrorCode'}) {
	/* IPS Api give answer */
	 
	$order = new WC_Order($result->{'Merchant_Order_Id'});	
	if($result->{'Transaction_Status'}->{'State'} == 2)
	{
		/* Payment was approved */
		/* order already paid stop process */
		if ($order->is_paid()) {
			return;
		}
		
		/* order refunded stop process */
		if ($order->has_status('refunded')) {
			return;
		}
		/* change order status to processing */
		$order->update_status('processing', __('MoneyTigo - Transaction '.$TransactionId.' - SUCCESS ('.$result->{'Transaction_Status'}->{'Bank_Code_Description'}.')', 'moneytigo_woocommerce'));
		/* reduce stock */
		$order->reduce_order_stock();
		/* delete cart */
		$woocommerce->cart->empty_cart();
		echo "200";
	}
	else
	{
		/* Payment was not approved */
		/* change order state to failed */
		$order->update_status('failed', __('MoneyTigo - Transaction '.$TransactionId.' - FAILED ('.$result->{'Transaction_Status'}->{'Bank_Code_Description'}.')', 'moneytigo_woocommerce'));
		echo "200";
		/* if order approved after can still change status when IPS answer approved */
	}
}
else {
	/* return 400 code http cause bad request */
	http_response_code(400);
}
	
}
/* Initialise Gateway Settings Form Fields for ADMIN. */
public function init_form_fields() {
  global $woocommerce;
	
	 
  $this->form_fields = array(
	  'enabled' => array(
      'title' => __( 'Enable / Disable', 'moneytigo_woocommerce' ),
      'type' => 'text',
      'label' => __( 'Activate card payment with MoneyTigo', 'moneytigo_woocommerce' ),
 
    ),
    'enabled' => array(
      'title' => __( 'Enable / Disable', 'moneytigo_woocommerce' ),
      'type' => 'checkbox',
      'label' => __( 'Activate card payment with MoneyTigo', 'moneytigo_woocommerce' ),
      'default' => 'no'
    ),
	'integrated' => array(
      'title' => __( 'Active iframe integration', 'moneytigo_woocommerce' ),
      'type' => 'checkbox',
      'label' => __( 'Activate the integrated mode', 'moneytigo_woocommerce' ),
      'default' => 'no',
	'description' => __( 'If this option is checked in this case your customer will make his payment directly on your website, otherwise the customer will be redirected to the secure payment page MoneyTigo.<br><b>Remember to activate the integrated mode in the configuration of your contract on your MoneyTigo dashboard</b>', 'moneytigo_woocommerce' ),
    ),
    'title' => array(
      'title' => __( 'Method title', 'moneytigo_woocommerce' ),
      'type' => 'text',
      'description' => __( 'This is the name displayed on your checkout', 'moneytigo_woocommerce' ),
      'desc_tip' => true,
      'default' => __( 'Credit card payment', 'moneytigo_woocommerce' )
    ),
    'description' => array(
      'title' => __( 'Message before payment', 'moneytigo_woocommerce' ),
      'type' => 'textarea',
      'description' => __( 'Message that the customer sees when he chooses this payment method', 'moneytigo_woocommerce' ),
      'desc_tip' => true,
      'default' => __( 'You will be redirected to our secure server to make your payment', 'moneytigo_woocommerce' )
    ),
    'moneytigo_gateway_api_key' => array(
      'title' => __( 'Your API Key', 'moneytigo_woocommerce' ),
      'description' => __( 'To obtain it go to the configuration of your merchant contract (section "Merchant account").', 'moneytigo_woocommerce' ),
      'type' => 'text'
    ),
    'moneytigo_gateway_secret_key' => array(
      'title' => __( 'Your Secret Key', 'moneytigo_woocommerce' ),
      'description' => __( 'To obtain it go to the configuration of your merchant contract (section "Merchant account").', 'moneytigo_woocommerce' ),
      'type' => 'text'
    )
  );
}
/* Construction of the payment link */
public function Construct_Link_Payment( $params, $ShaKey ) {
  $beforesign = "";
  foreach ( $params as $key => $value ) {
    $beforesign .= $value . "!";
  }
  $beforesign .= $ShaKey;
  $sign = hash( "sha512", base64_encode( $beforesign . "|" . $ShaKey ) );
  $params[ 'SHA' ] = $sign;
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, ''.$this->moneytigo_gateway_create_transaction_uri.'' );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
  curl_setopt( $ch, CURLOPT_POST, 1 );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
  $headers = array();
  $headers[] = 'Content-Type: application/x-www-form-urlencoded';
  curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
  $result = curl_exec( $ch );
  if ( curl_errno( $ch ) ) {
    echo 'Error:' . curl_error( $ch );
  }
  curl_close( $ch );
  return $result;
}


/* Construction of payment parameters */
/* Construct Payment redirection */
	
public function generate_Redirect_Form ($MoneyTigoParameters)
{
	$UriToRedirect = $this->moneytigo_gateway_create_transaction_uri."?SACS=".$MoneyTigoParameters[Token];
	echo "<center><h1>";
	echo __( 'One moment please we redirect you to our secure payment server', 'moneytigo_woocommerce' );
	echo "</h1></center>";
	echo "<center><a href='".$UriToRedirect."'><button>";
	echo __( 'Click on this button if the redirection does not work', 'moneytigo_woocommerce' ); 
	echo "</button></a></center>";
	header('Location: '.$UriToRedirect.'');
	exit();
 
}
/* Generate and lauch Payment form with Iframe Hosted Page */
public function generate_Payment_Form( $MoneyTigoParameters ) {
  //Charge MoneyTigo Library
  wp_enqueue_script( 'wc_MoneyTigo', ''.$this->moneytigo_gateway_library_uri.'', array( 'jquery' ), '1.0.0', true );

  //Customize render and remove superflux information insert before MoneyTigo Library
  wp_add_inline_script( 'wc_MoneyTigo', 'jQuery(".order_details").append("<div id=credit-card-iframe></div><button id=submit-credit-card-button style=\'display:none;\'></button>")
		    jQuery( ".order" ).remove();
			jQuery( "#logomoneytigo" ).hide();
			jQuery( ".method" ).remove();
			var iframe_container_id = "credit-card-iframe";
			var btn_container_id = "submit-credit-card-button";
					
					', 'before' );

  //Insert after library call back script for lauch payment page
  wp_add_inline_script( 'wc_MoneyTigo', '<script type="application/javascript">var creditCard = Ips.createCreditCardIframe(btn_container_id,iframe_container_id,PaymentParameter);
document.getElementById(btn_container_id).addEventListener("click", function (event) {


            creditCard.Execute(function (response) { 
                var response = JSON.parse(response);

                if (response.error){
                  //If IPS returns an error you can customize the process here.
                }else {
                    if(response.Status == "SUCCESS")
                        {

                     

                        }
                    else if(response.Status == "FAILED")
                    {
              
                    }
                }
            });

});
</script> 

', 'after' );


  //Sent paramater to MoneyTigo and library
  wp_localize_script( 'wc_MoneyTigo', 'PaymentParameter', $MoneyTigoParameters );
}



/* Generate process payment and return to @order-pay uri for integrated Payment */
public function process_payment($order_id)
	{
			$order = new WC_Order($order_id);
            return [
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true) //true important for define is in checkout role
            ];
	} 

public function payment_scripts() {

  if ( !is_checkout_pay_page() ) {
    return;
  }
  
   
  if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {
	  
	  
	  
    $order_key = urldecode( $_GET[ 'key' ] );
    $order_id = absint( get_query_var( 'order-pay' ) );
    $order = wc_get_order( $order_id );
	
	//Important check if payment method is selected for display payment script
	//If not made conflict with PNF section.
	if($order->get_payment_method() !== 'moneytigo')
	{
	 return;
	}
	 
	
    $email = method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
    $custo_firstname = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
    $custo_lastname = method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;
    $the_order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
    $the_order_key = method_exists( $order, 'get_order_key' ) ? $order->get_order_key() : $order->order_key;


    if ( $the_order_id == $order_id && $the_order_key == $order_key ) {
      $moneytigo_params[ 'MerchantKey' ] = $this->get_option( 'moneytigo_gateway_api_key' );
      $moneytigo_params[ 'amount' ] = $order->get_total();
      $moneytigo_params[ 'RefOrder' ] = $order_id;
      $moneytigo_params[ 'Customer_Email' ] = $email;
      $moneytigo_params[ 'Customer_FirstName' ] = $custo_firstname;
      $moneytigo_params[ 'Customer_LastName' ] = $custo_lastname;
      $moneytigo_params[ 'urlOK' ] = get_site_url() . '/?wc-api=wc_moneytigo_return&actionreturn=success&mtg_ord=' . $order_id . '';
      $moneytigo_params[ 'urlKO' ] = get_site_url() . '/?wc-api=wc_moneytigo_return&actionreturn=failed&mtg_ord=' . $order_id . '';
      $moneytigo_params[ 'urlIPN' ] = get_site_url() . '/?wc-api=wc_moneytigo';
	  if($this->get_option( 'integrated' ) == "yes")
	  {
      $moneytigo_params[ 'crossorigine' ] = "embededadvanced";
      $moneytigo_params[ 'cmsembed' ] = "true";
	  }
      $getToken = $this->Construct_Link_Payment( $moneytigo_params, $this->get_option( 'moneytigo_gateway_secret_key' ) );
      $getToken = json_decode( $getToken )->{'SACS'};
      $moneytigo_tokenizer[ 'Token' ] = $getToken;
     
		
	  if($this->get_option( 'integrated' ) == "yes")
	  {
	  $this->generate_Payment_Form( $moneytigo_tokenizer );
	  }
	else {
		
		//redirecting
		//echo "je redirige";
		//echo $this->moneytigo_gateway_create_transaction_uri."?SACS=".$getToken;
		//exit;
		$this->generate_Redirect_Form( $moneytigo_tokenizer );
	}
	  }
		

  } //@end if checkout page


}


}
?>