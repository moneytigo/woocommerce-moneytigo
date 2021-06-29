<?php
/*
        Plugin Name: MoneyTigo
        Plugin URI: https://app.moneytigo.com
        Description: Accept credit cards in less than 5 minutes
        Version: 1.0.9
        Author: IPS INTERNATIONNAL SAS
        Author URI: https://www.moneytigo.com
        License: IPS INTERNATIONNAL SAS
		Domain Path: /languages
		Text Domain: moneytigo
    */

  define( 'MoneyTigoVersion', "1.0.9" );

/* Additional links on the plugin page */
add_filter( 'plugin_row_meta', 'moneytigo_register_plugin_links', 10, 2 );

/* Auto update plugins */
function moneytigo_update_auto_plins ( $update, $item ) {
    // Array of plugin slugs to always auto-update
    $plugins = array (
        'moneytigo'
    );
    if ( in_array( $item->slug, $plugins ) ) {
        return true;
    } else {
        return $update;
    }
}
add_filter( 'auto_update_plugin', 'moneytigo_update_auto_plins', 10, 2 );

/* Check new version plugins*/
add_action( 'admin_notices', 'checking_mtg_upgrade' );

/* Securing file calls by taking into account specific installations */
function moneytigo_get_file( $namefiles = "" ) {
  $plugin_url = plugin_dir_url( __FILE__ );
  return $plugin_url . $namefiles;
}
/* Add styles Css */
function moneytigo_load_plugin_css() {
  $plugin_url = plugin_dir_url( __FILE__ );
  wp_enqueue_style( 'moneytigo', $plugin_url . 'assets/css/styles.css' );

}
add_action( 'wp_enqueue_scripts', 'moneytigo_load_plugin_css' );
/* Function for universal calling in the payment sub-modules */
function moneytigo_universale_params() {
  $baseUriMoneyTigoWEB = "https://checkout.moneytigo.com";
  $baseUriMoneyTigoAPI = "https://payment.moneytigo.com";

  $config = array(
    'Version' => "1.0.9",
    'ApiInitPayment' => $baseUriMoneyTigoAPI . "/init_transactions/",
    'ApiGetTransaction' => $baseUriMoneyTigoAPI . "/transactions/",
    'CheckCmsUri' => 'https://app.moneytigo.com/checkcms/?cname=wordpress_woocommerce&v=' . MoneyTigoVersion . '',
    'ApiGetTransactionByOrderId' => "https://payment.moneytigo.com/transactions_by_merchantid/",
    'WebUriStandard' => $baseUriMoneyTigoWEB . "/pay/standard/token/",
    'WebUriInstallment' => $baseUriMoneyTigoWEB . "/pay/installment/token/",
    'WebUriSubscription' => $baseUriMoneyTigoWEB . "/pay/subscription/token/",

  );
  return $config;
}

function checking_mtg_upgrade() {
  $response = wp_remote_get( moneytigo_universale_params()[ 'CheckCmsUri' ] );
  if ( $response[ 'body' ] == true ) {
    echo '
    <div class="notice notice-warning" style="background-color: red; color: white">
        <p>MONEYTIGO : ' . __( 'We inform you that a new version of the plugin is available, You must perform the update within the next 48 working hours', 'moneytigo' ) . ' ! </p>
		
    </div>
    ';
  }
}


function moneytigo_register_plugin_links( $links, $file ) {
  $base = plugin_basename( __FILE__ );
  if ( $file == $base ) {
    $links[] = '<a href="https://app.moneytigo.com/account/plugins/documentation" target="_blank">' . __( 'Documentation', 'moneytigo' ) . '</a>';
  }
  return $links;
}

/* Add footer display Payment securised */

function moneytigo_in_footer() {
  echo '<div id="logomoneytigo" name="logomoneytigo" class="mtgfooter" style="position:relative; bottom: 1px;"><hr><center><a href="https://www.moneytigo.com/?referrer=plugins&desc=woocommerce"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/img/footer_moneytigo_logo.png" style="margin-bottom: 5px; width: 150px !important;" alt="' . __( 'Payment solution for WooCommerce Moneytigo', 'moneytigo' ) . '" title="' . __( 'Payment solution for WooCommerce Moneytigo', 'moneytigo' ) . '"></a></center></div>';
}
add_action( 'wp_footer', 'moneytigo_in_footer' );


/* WooCommerce fallback notice. */
function moneytigo_ipg_fallback_notice() {
  $htmlToReturn = '<div class="error">';
  $htmlToReturn .= '<p>' . sprintf( __( 'The MoneyTigo module works from Woocommerce version %s minimum', 'moneytigo' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p>';
  $htmlToReturn .= '</div>';
  echo $htmlToReturn;
}

/* Loading both payment methods */
function custom_MoneyTigo_gateway_load() {
  global $woocommerce;
  if ( !class_exists( 'WC_Payment_Gateway' ) ) {
    add_action( 'admin_notices', 'moneytigo_ipg_fallback_notice' );
    return;
  }
  /* Payment classic */
  function wc_CustomMoneyTigo_add_gateway( $methods ) {
    $methods[] = 'WC_MoneyTigo';
    return $methods;
  }
  /* Payment by installments */
  function wc_CustomMoneyTigoPnfTwo_add_gateway( $methods ) {
    $methods[] = 'WC_MoneyTigoPnfTwo';
    return $methods;
  }

  function wc_CustomMoneyTigoPnfThree_add_gateway( $methods ) {
    $methods[] = 'WC_MoneyTigoPnfThree';
    return $methods;
  }

  function wc_CustomMoneyTigoPnfFour_add_gateway( $methods ) {
    $methods[] = 'WC_MoneyTigoPnfFour';
    return $methods;
  }
  add_filter( 'woocommerce_payment_gateways', 'wc_CustomMoneyTigo_add_gateway' );
  add_filter( 'woocommerce_payment_gateways', 'wc_CustomMoneyTigoPnfTwo_add_gateway' );
  add_filter( 'woocommerce_payment_gateways', 'wc_CustomMoneyTigoPnfThree_add_gateway' );
  add_filter( 'woocommerce_payment_gateways', 'wc_CustomMoneyTigoPnfFour_add_gateway' );
  /* Load class for both payment methods */
  require_once plugin_dir_path( __FILE__ ) . 'class-wc-moneytigo.php';
  require_once plugin_dir_path( __FILE__ ) . 'class-wc-moneytigopnf-two.php';
  require_once plugin_dir_path( __FILE__ ) . 'class-wc-moneytigopnf-three.php';
  require_once plugin_dir_path( __FILE__ ) . 'class-wc-moneytigopnf-four.php';
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
add_filter( 'woocommerce_available_payment_gateways', 'moneytigo_payment_method_filters', 1 );

function moneytigo_payment_method_filters( $gateways ) {

  if ( isset( $gateways[ 'moneytigo' ] ) ) {
    if ( $gateways[ 'moneytigo' ]->{'enabled'} == "yes" ) {
      if ( ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} == ' ' ) || ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} == ' ' ) ) {
        wc_add_notice( '<b>MoneyTigo</b> : ' . __( 'Module not configured, API key or ENCRYPTION key missing', 'moneytigo' ) . '', 'error' );
        unset( $gateways[ 'moneytigo' ] ); //Not avialable cause not settings
      }
    }
  }
  if ( isset( $gateways[ 'moneytigopnftwo' ] ) ) {
    if ( $gateways[ 'moneytigopnftwo' ]->{'enabled'} == "yes" ) {
      if ( ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} == ' ' ) || ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} == ' ' ) ) {
        wc_add_notice( '<b>MoneyTigo (2X)</b> : ' . __( 'Module not configured, API key or ENCRYPTION key missing', 'moneytigo' ) . '', 'error' );
        unset( $gateways[ 'moneytigopnftwo' ] ); //Not avialable cause not settings
      }
    }
  }

  if ( isset( $gateways[ 'moneytigopnfthree' ] ) ) {
    if ( $gateways[ 'moneytigopnfthree' ]->{'enabled'} == "yes" ) {
      if ( ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} == ' ' ) || ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} == ' ' ) ) {
        wc_add_notice( '<b>MoneyTigo (3X)</b> : ' . __( 'Module not configured, API key or ENCRYPTION key missing', 'moneytigo' ) . '', 'error' );
        unset( $gateways[ wc_CustomMoneyTigoPnfThree_add_gateway() ] ); //Not avialable cause not settings
      }
    }
  }

  if ( isset( $gateways[ 'moneytigopnffour' ] ) ) {
    if ( $gateways[ 'moneytigopnffour' ]->{'enabled'} == "yes" ) {
      if ( ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_api_key'} == ' ' ) || ( !$gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} || $gateways[ 'moneytigo' ]->{'moneytigo_gateway_secret_key'} == ' ' ) ) {
        wc_add_notice( '<b>MoneyTigo (4X)</b> : ' . __( 'Module not configured, API key or ENCRYPTION key missing', 'moneytigo' ) . '', 'error' );
        unset( $gateways[ wc_CustomMoneyTigoPnfThree_add_gateway() ] ); //Not avialable cause not settings
      }
    }
  }

  //Check first if payment module is settings	
  if ( isset( $gateways[ 'moneytigopnftwo' ] ) ) {
    if ( $gateways[ 'moneytigopnftwo' ]->{'enabled'} == "yes" ) {
      /* Check if the amount of the basket is sufficient to display the method in several installments*/
      global $woocommerce;

      $IPSPnf = $gateways[ 'moneytigopnftwo' ]->{'settings'};

      if ( isset( $woocommerce->cart->total ) && $woocommerce->cart->total < $IPSPnf[ 'seuil' ] ) {
        unset( $gateways[ 'moneytigopnftwo' ] );
      }
    }
  }
  if ( isset( $gateways[ 'moneytigopnfthree' ] ) ) {
    if ( $gateways[ 'moneytigopnfthree' ]->{'enabled'} == "yes" ) {
      /* Check if the amount of the basket is sufficient to display the method in several installments*/
      global $woocommerce;

      $IPSPnf = $gateways[ 'moneytigopnfthree' ]->{'settings'};

      if ( isset( $woocommerce->cart->total ) && $woocommerce->cart->total < $IPSPnf[ 'seuil' ] ) {
        unset( $gateways[ 'moneytigopnfthree' ] );
      }
    }
  }
  if ( isset( $gateways[ 'moneytigopnffour' ] ) ) {
    if ( $gateways[ 'moneytigopnffour' ]->{'enabled'} == "yes" ) {
      /* Check if the amount of the basket is sufficient to display the method in several installments*/
      global $woocommerce;

      $IPSPnf = $gateways[ 'moneytigopnffour' ]->{'settings'};

      if ( isset( $woocommerce->cart->total ) && $woocommerce->cart->total < $IPSPnf[ 'seuil' ] ) {
        unset( $gateways[ 'moneytigopnffour' ] );
      }
    }
  }
  /* Return of available methods */
  return $gateways;
}

/* Adding translation files */
load_plugin_textdomain( 'moneytigo', false, dirname( plugin_basename( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR );