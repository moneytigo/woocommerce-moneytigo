<?php
/*
        Plugin Name: MoneyTigo
        Plugin URI: https://app.moneytigo.com
        Description: Accept credit cards in less than 5 minutes
        Version: 1.0.3
        Author: IPS INTERNATIONNAL SAS
        Author URI: https://www.moneytigo.com
        License: IPS INTERNATIONNAL SAS
		Domain Path: /languages
		Text Domain: moneytigo_woocommerce
    */

/* Additional links on the plugin page */
add_filter( 'plugin_row_meta', 'moneytigo_register_plugin_links', 10, 2 );

/* Check new version plugins*/
add_action( 'admin_notices', 'checking_mtg_upgrade' );

function checking_mtg_upgrade() {
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_URL, 'https://app.moneytigo.com/checkcms/?cname=wordpress_woocommerce&v=' . get_plugin_data( __FILE__ )[ 'Version' ] . '' );
  $result = curl_exec( $ch );
  curl_close( $ch );
  if ( $result == true ) {
    echo '
    <div class="notice notice-warning" style="background-color: red; color: white">
        <p>MONEYTIGO : ' . __( 'We inform you that a new version of the plugin is available, You must perform the update within the next 48 working hours', 'moneytigo_woocommerce' ) . ' ! </p>
		
    </div>
    ';
  }
}


function moneytigo_register_plugin_links( $links, $file ) {
  $base = plugin_basename( __FILE__ );
  if ( $file == $base ) {
    $links[] = '<a href="https://app.moneytigo.com/account/plugins/documentation" target="_blank">' . __( 'Documentation', 'moneytigo_woocommerce' ) . '</a>';
  }
  return $links;
}

/* Add footer display Payment securised */

function moneytigo_in_footer() {
  echo '<div id="logomoneytigo" name="logomoneytigo" style="position:relative; bottom: 1px;"><hr><center><a href="https://www.moneytigo.com/?referrer=plugins&desc=woocommerce"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/img/footer_moneytigo_logo.png" style="margin-bottom: 5px; width: 150px !important;" alt="' . __( 'Secure payment by MoneyTigo ™', 'moneytigo_woocommerce' ) . '" title="' . __( 'Secure payment by MoneyTigo ™', 'moneytigo_woocommerce' ) . '"></a></center></div>';
}
add_action( 'wp_footer', 'moneytigo_in_footer' );


/* WooCommerce fallback notice. */
function woocommerce_ipg_fallback_notice() {
  $htmlToReturn = '<div class="error">';
  $htmlToReturn .= '<p>' . sprintf( __( 'The MoneyTigo module works from Woocommerce version %s minimum', 'moneytigo_woocommerce' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p>';
  $htmlToReturn .= '</div>';
  echo $htmlToReturn;
}

/* Loading both payment methods */
function custom_MoneyTigo_gateway_load() {
  global $woocommerce;
  if ( !class_exists( 'WC_Payment_Gateway' ) ) {
    add_action( 'admin_notices', 'woocommerce_ipg_fallback_notice' );
    return;
  }
  /* Payment classic */
  function wc_CustomMoneyTigo_add_gateway( $methods ) {
    $methods[] = 'WC_MoneyTigo';
    return $methods;
  }
  /* Payment by installments */
  function wc_CustomMoneyTigoPnf_add_gateway( $methods ) {
    $methods[] = 'WC_MoneyTigoPnf';
    return $methods;
  }
  add_filter( 'woocommerce_payment_gateways', 'wc_CustomMoneyTigo_add_gateway' );
  add_filter( 'woocommerce_payment_gateways', 'wc_CustomMoneyTigoPnf_add_gateway' );
  /* Load class for both payment methods */
  require_once plugin_dir_path( __FILE__ ) . 'class-wc-moneytigo.php';
  require_once plugin_dir_path( __FILE__ ) . 'class-wc-moneytigopnf.php';
}
add_action( 'plugins_loaded', 'custom_MoneyTigo_gateway_load', 0 );

/* Adds custom settings url in plugins page. */
function moneytigo_action_links( $links ) {
  $settings = array(
    'settings' => sprintf(
      '<a href="%s">%s</a>',
      admin_url( 'admin.php?page=wc-settings&tab=checkout' ),
      __( 'Payment Gateways', 'MoneyTigo' )
    )
  );
  return array_merge( $settings, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'moneytigo_action_links' );

/* Filtering of methods according to the amount of the basket */
add_filter( 'woocommerce_available_payment_gateways', 'es_filter_gateways', 1 );

function es_filter_gateways( $gateways ) {


  if ( $gateways[ 'moneytigo' ]->{'enabled'} == "yes" ) {
    if ( ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} == ' ' ) || ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} == ' ' ) ) {
      wc_add_notice( '<b>MoneyTigo</b> : '. __('Module not configured, API key or ENCRYPTION key missing', 'moneytigo_woocommerce').'', 'error' );
      unset( $gateways[ 'moneytigo' ] ); //Not avialable cause not settings
    }
  }
  if ( $gateways[ 'moneytigopnf' ]->{'enabled'} == "yes" ) {
    if ( ( !$gateways[ 'moneytigopnf' ]->{'moneytigopnf_gateway_api_key'} || $gateways[ 'moneytigopnf' ]->{'moneytigopnf_gateway_api_key'} == ' ' ) || ( !$gateways[ 'moneytigopnf' ]->{'moneytigopnf_gateway_secret_key'} || $gateways[ 'moneytigopnf' ]->{'moneytigopnf_gateway_secret_key'} == ' ' ) ) {
      wc_add_notice( '<b>MoneyTigo (3X)</b> : ' . __( 'Module not configured, API key or ENCRYPTION key missing', 'moneytigo_woocommerce' ) . '', 'error' );
      unset( $gateways[ 'moneytigopnf' ] ); //Not avialable cause not settings
    }
  }

  //Check first if payment module is settings	

  if ( $gateways[ 'moneytigopnf' ]->{'enabled'} == "yes" ) {
    /* Check if the amount of the basket is sufficient to display the method in several installments*/
    global $woocommerce;
    $IPSPnf = $gateways[ 'moneytigopnf' ]->{'settings'};
    if ( $woocommerce->cart->total < $IPSPnf[ 'moneytigopnf_gateway_minimum_p3f' ] ) {
      unset( $gateways[ 'moneytigopnf' ] );
    }
  }

  /* Return of available methods */
  return $gateways;
}

/* Adding translation files */
load_plugin_textdomain( 'moneytigo_woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR );