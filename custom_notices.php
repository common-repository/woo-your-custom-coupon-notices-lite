<?php
/*
* Plugin Name: Woo Your Custom Coupon Notices Lite
* Plugin URI: http://www.w34all.de
* Version: 1.4
* Description: Customized Coupon Notices for WooCommerce 
* Author: Martin Stemberg
* Author URI: w34all.de
*/

class Woo_Custom_Coupon_Notices
{


const NONCE = 'woo-coupon-notices-admin-nonce';

    //INIT
    public static function wp_init()
    {
global $wpdb;
 $results = $wpdb->get_results(
    "
    SELECT DISTINCT post_id
    FROM {$wpdb->prefix}postmeta 
    WHERE meta_key 
    LIKE 'your_custom_notice_%'
    ",
    ARRAY_A
);

$insgesamt=count($results);
$counter=0;
for($count = 0; $count < $insgesamt; $count++)
{
  //echo $results[$count][post_id]."<BR>";
if (get_post_status($results[$count][post_id])=="publish") {

++$counter;
}
}
//echo "ANZAHL2: ".$counter;

        
        if(!get_option('your_notices_options')) {
            $op = array(
            'not_exist' => 'Es tut uns leid, ein Gutschein \'%s\' existiert nicht.',
            'please_enter'=>'Bitte Gutschein-Code eingeben.',
        );
            add_option('your_notices_options', $op);
            }
       
        $css = plugins_url() .
            "/woo_your_custom_coupon_notices_lite/css/admin_stylesheet.css";

        wp_register_style('customnoticesStylesheet', $css);
        wp_enqueue_style('customnoticesStylesheet');
        define('CUSTOMNOTICE_DOMAIN', 'custom_notice');
        register_activation_hook(DIR_PATH,array('nssUser_creator','nss_plug_install'));
        load_plugin_textdomain( 'custom_notice', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' ); 

$GLOBALS['counter']=$counter;

        add_filter('woocommerce_coupon_data_tabs', array(__class__,
                'woocommerce_your_custom_notices'));


if ($counter<=1) {
add_action('admin_notices', array(__class__,'coupon_custom_admin_notice1'));
}
else if ($counter==2) {
add_action('admin_notices', array(__class__,'coupon_custom_admin_notice2'));
} else if ($counter>=3) {
add_action('admin_notices', array(__class__,'coupon_custom_admin_notice3'));
}
        add_action('woocommerce_coupon_options', array(__class__,
                'woocommerce_your_custom_notice_options'));
        add_action('woocommerce_process_shop_coupon_meta', array(__class__,
                'woocommerce_process_your_notices_coupon_meta'), 10, 2);
        add_filter('woocommerce_coupon_message', array(__class__,
                'woocommerce_coupon_notice'), 10, 3);
        add_filter('woocommerce_coupon_error', array(__class__,
                'woocommerce_coupon_error'), 10, 3);
        add_filter('gettext', array(__class__, 'coupon_remove'), 10, 3);
        
        add_action( 'admin_menu', array( __CLASS__, 'notices_admin_menu' ), 40 );
        $notice_codes = array('200', '201');

    }

public static function coupon_custom_admin_notice1() { ?>
	
	<div class="notice notice-success">
		<p><?php _e('You installed "Woo Your Custom Coupon Notices Lite". You can customize notices for up to three coupons. If you want more, visit <a href="http://www.w34all.de/" target="_blank">w34all.de</a> to get the full version.', 'CUSTOMNOTICE_DOMAIN'); ?></p>
	</div>
	
<?php }

public static function coupon_custom_admin_notice2() { ?>
	
	<div class="notice notice-warning">
		<p><?php _e('You customized notices for two coupons. Only one coupon remaining. If you want more, visit <a href="http://www.w34all.de/" target="_blank">w34all.de</a> to get the full version.', 'CUSTOMNOTICE_DOMAIN'); ?></p>
	</div>
	
<?php }

public static function coupon_custom_admin_notice3() { ?>
	
	<div class="notice notice-error is-dismissible">
		<p><?php _e('You customized notices for all three coupons possible with this Lite version. If you want more, visit <a href="http://www.w34all.de/" target="_blank">w34all.de</a> to get the full version.', 'CUSTOMNOTICE_DOMAIN'); ?></p>
	</div>
	
<?php }

public static function notices_admin_menu() {
		$admin_page = add_submenu_page(
			'woocommerce',
			__( 'Coupon Notices' ),
			__( 'Coupon Notices' ),
			'manage_woocommerce',
			'woocommerce_coupon_notices',
			array( __CLASS__, 'woocommerce_coupon_notices' )
		);
}

public static function woocommerce_coupon_notices() {
    	if ( !current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'Access denied.', CUSTOMNOTICE_DOMAIN ) );
		}
        
        

        self::filter_off();
        $coupon = new WC_Coupon( null );
        if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], 'set' ) ) {
			 $op = array(
            'not_exist' => trim( stripslashes( wp_filter_kses($_POST['not_exist']))),
            'please_enter'=> trim( stripslashes( wp_filter_kses($_POST['please_enter'])))
        );
            update_option('your_notices_options', $op);
             }
             }
        echo '<div class="woocommerce-coupon-notices">'; // umschlieﬂender DIV

		echo '<h2>' . __( 'Woo Your Custom Coupon Notices', CUSTOMNOTICE_DOMAIN ) . '</h2>';
        
        echo '<p>';
		echo __( 'Here you can change Standard-Notices as \'Coupon "xxxxx" does not exist!\'.', CUSTOMNOTICE_DOMAIN );
		echo '</p>';
        echo '<form action="" name="options" method="post">';
        echo "<div>"; //EINLEITENDER DIV
       $options = get_option( 'your_notices_options');
        echo "<div>".$coupon->get_coupon_error( '105' )."</div>";
        
        printf( '<div><textarea placeholder="%s" style="font-family:monospace;width:85%%;height:5em;" name="%s">%s</textarea></div>', __( 'Use the default notice', CUSTOMNOTICE_DOMAIN ), "not_exist", stripslashes( esc_textarea( $options['not_exist'] ) ) );
        echo '<div style="font-size:90%;">'.__("%s is a wildcard for the name of the coupon. You might enter multiple notices <strong>separated by \"|\"</strong>, plugin will shuffle them and display one. So notices will change from time to time.",CUSTOMNOTICE_DOMAIN).'</div>';
        
        echo '<div style="margin-top:15px;">'; //EINLEITENDER DIV
        echo "<div>".__( 'Please enter a coupon code.', 'woocommerce' )."</div>";
        printf( '<div><textarea placeholder="%s" style="font-family:monospace;width:85%%;height:5em;" name="%s">%s</textarea></div>', __( 'Use the default notice', CUSTOMNOTICE_DOMAIN ), "please_enter", stripslashes( esc_textarea( $options['please_enter'] ) ) );
        echo '<div style="font-size:90%;">'.__("Again, you might enter multiple notices <strong>separated by \"|\"</strong>.",CUSTOMNOTICE_DOMAIN).'</div>';
        echo "</div>"; //EINLEITENDER DIV
        echo "</div>"; // umschlieﬂender DIV
        
        echo wp_nonce_field( 'set', self::NONCE, true, false );
		echo '<input style="margin-top:10px;" class="button" type="submit" name="submit" value="' . __( 'Save', CUSTOMNOTICE_DOMAIN ) . '"/>';
        echo "</form>";
self::filter_on();
    }
    //DATA TABS
    public static function woocommerce_your_custom_notices($tabs)
    {
        $tabs['notices'] = array(
            'label' => __('Notices', CUSTOMNOTICE_DOMAIN),
            'target' => 'custom_coupon_notices',
            'class' => 'coupon-notices');
        return $tabs;
    }


    public static function woocommerce_your_custom_notice_options()
    {
        global $wpdb, $post, $woocommerce;
        $coupon = new WC_Coupon(isset($post->post_title) ? $post->post_title : null);

        echo '<div id="custom_coupon_notices" class="panel woocommerce_options_panel">';
wp_nonce_field(basename(__FILE__), "meta-box-nonce-your-notices");
        self::filter_off();
     

        echo '<div class="options_group">'; //Options Group

        echo '<h4>';
        echo __('Custom Notices', CUSTOMNOTICE_DOMAIN);
        echo '</h4>';
        $placeholder = __("Use&nbsp;default&nbsp;notice", CUSTOMNOTICE_DOMAIN);
        $notice_codes = array('200', '201');
        foreach ($notice_codes as $code) {
            $textareaname = "your_custom_notice_" . $code;
            echo '<div class="noticeheading">';
            echo "<strong>" . __('Default:', CUSTOMNOTICE_DOMAIN) . "</strong> " . $coupon->
                get_coupon_message($code);
            echo '</div>';
            echo '<textarea name="' . $textareaname .
                '" class="successnotice" placeholder="' . $placeholder . '">';
            $notice = get_post_meta($coupon->id, "your_custom_notice_" . $code, true);
            if (!empty($notice)) {
                echo $notice;
            }
            echo '</textarea>';
            echo '<div class="spacer">&nbsp;</div>';
        }


        echo "</div>"; //Options Group


        $error_codes = array(
            '100',
            '101',
            '103',
            '104',
            //'105',
            '106',
            '107',
            '108',
            '109',
            '110',
            '112');

        echo '<div class="options_group">'; //Options Group

        echo '<h4>';
        echo __('Custom Error Notices', CUSTOMNOTICE_DOMAIN);
        echo '</h4>';
        $placeholder = __("Use&nbsp;default&nbsp;notice", CUSTOMNOTICE_DOMAIN);

        foreach ($error_codes as $code2) {
            $textareaname = "your_custom_notice_" . $code2;
            echo '<div class="noticeheading">';
            echo "<strong>" . __('Default:', CUSTOMNOTICE_DOMAIN) . "</strong> " . $coupon->
                get_coupon_error($code2);
            echo '</div>';
            echo '<textarea name="' . $textareaname . '" class="errornotice" placeholder="' .
                $placeholder . '">';
            $notice = get_post_meta($coupon->id, "your_custom_notice_" . $code2, true);
            if (!empty($notice)) {
                echo $notice;
            }
            echo '</textarea>';
            echo '<div class="spacer">&nbsp;</div>';
        }


        echo "</div>"; //Options Group



        echo '<div class="options_group">'; //PLACEHOLDERS

        echo '<h4>';
        echo __('Possible Tokens', CUSTOMNOTICE_DOMAIN);
        echo '</h4>';


        $variablen = self::make_vars($coupon);

        if (count($variablen) > 0) {
            echo '<ul>';
            foreach ($variablen as $var => $value) {

                echo '<li>';
                echo "[" . $var . "]";
                if (!empty($value)) {
                    echo ' : ' . $value;
                }

                echo '</li>';

            }
            echo "</UL>";
        
        echo "</div>"; //PLACEHOLDERS

}
        echo "</div>";
        echo '<script type="text/javascript">';
        echo 'if (typeof jQuery !== "undefined"){';
        echo 'jQuery(document).ready(function(){';
        echo 'jQuery("#custom_coupon_notices").insertAfter(jQuery(".woocommerce_options_panel").last());';
        echo '});';
        echo '}';
        echo '</script>';
        self::filter_on();
    }

    //OPTIONS

    public static function woocommerce_process_your_notices_coupon_meta($post_id, $post)
    {
if($GLOBALS['counter']+1>15/5) {
return;
}

 if (!isset($_POST["meta-box-nonce-your-notices"]) || !wp_verify_nonce($_POST["meta-box-nonce-your-notices"], basename(__FILE__))) {
        return $post_id;
}

if ($post->post_type=="shop_coupon") {


        $notice_codes = array('200', '201');
        $error_codes = array(
            '100',
            '101',
            '103',
            '104',
            //'105',
            '106',
            '107',
            '108',
            '109',
            '110',
            '112');

        foreach ($notice_codes as $code) { //Function save meta foreach
            $key = "your_custom_notice_" . $code;
            delete_post_meta($post_id, $key);

            if (!empty($_POST[$key])) { // if !empty
                $notice = trim(stripslashes(wp_filter_kses($_POST[$key])));
                if (strlen($notice) > 0) { //strlen
                    add_post_meta($post_id, $key, $notice);

                } //strlen

            } //if empty
        } //Function save meta foreach

        foreach ($error_codes as $code2) { //Function save meta foreach
            $key = "your_custom_notice_" . $code2;
            delete_post_meta($post_id, $key);

            if (!empty($_POST[$key])) { // if !empty
                $notice = trim(stripslashes(wp_filter_kses($_POST[$key])));
                if (strlen($notice) > 0) { //strlen
                    add_post_meta($post_id, $key, $notice);

                } //strlen

            } //if empty
        } //Function save meta foreach

}
    }

    public static function woocommerce_coupon_notice($msg, $msg_code, $coupon)
    {

        if ($coupon && isset($coupon->id)) {
            $new_msg = get_post_meta($coupon->id, "your_custom_notice_" . $msg_code, true);
        }
        if (!empty($new_msg)) {
            $msg = $new_msg;
        }
        $msg = self::replace_placeholders($msg, $coupon);
        return $msg;
    }

    public static function woocommerce_coupon_error($err, $err_code, $coupon)
    {
        if ($coupon && isset($coupon->id)) {
            $new_msg = get_post_meta($coupon->id, "your_custom_notice_" . $err_code, true);
        }
        if (!empty($new_msg)) {
            $err = $new_msg;
        }
        $err = self::replace_placeholders($err, $coupon);
        return $err;
    }


    public static function replace_placeholders($message, $coupon)
    {
        global $woocommerce;
        $variablen = self::make_vars($coupon);
        //var_dump($variablen);



        foreach ($variablen as $var => $value) {
           
            if (is_string($value) || is_numeric($value)) {

                $message = str_replace("[" . $var . "]", $value, $message);
            }
        }


        return $message;
    }
    public static function coupon_remove($translated_text, $text, $domain)
    {

        if ($text == "Coupon has been removed.") { //if text==

            $couponpost = get_page_by_title($_POST[coupon], object, 'shop_coupon');


            $coupon = new WC_Coupon(isset($couponpost->post_title) ? $couponpost->
                post_title : null);


            $new_msg = get_post_meta($couponpost->ID, "your_custom_notice_201", true);


            if (!empty($new_msg)) {
                $translated_text = $new_msg;
            }
            $translated_text = self::replace_placeholders($translated_text, $coupon);
        } //if text==
        
        if ((strpos($text,"Coupon")!==false) && ((strpos($text,"does not exist")!==false))) {
            
            if (get_option( 'your_notices_options' )!=FALSE) {
            $couponname=str_replace("Coupon \"","", $text);
            $couponname=str_replace("\" does not exist!","", $couponname);
            $options=get_option('your_notices_options');
           if (!empty($options['not_exist'])) {
            $options['not_exist']=explode("|",$options['not_exist']);
            shuffle( $options['not_exist']);
            
                        $translated_text=sprintf($options[not_exist][0],$couponname);
                        }
            }
            
          
            }
            
              if ($text=="Please enter a coupon code.") {
                if (get_option( 'your_notices_options' )!=FALSE) {
                    $options=get_option('your_notices_options');
                    if (!empty($options['please_enter'])) {
                      $options['please_enter']=explode("|",$options['please_enter']);
            shuffle( $options['please_enter']); 
            $translated_text=$options['please_enter'][0];
                        }
                    }
            }
        return $translated_text;
    }

    public static function filter_off()
    {
        remove_filter('woocommerce_coupon_message', array(__class__,
                'woocommerce_coupon_notice'));
        remove_filter('woocommerce_coupon_error', array(__class__,
                'woocommerce_coupon_error'));
        remove_filter('gettext', array(__class__, 'coupon_remove'));
    }

    public static function filter_on()
    {
        add_filter('woocommerce_coupon_message', array(__class__,
                'woocommerce_coupon_notice'), 10, 3);
        add_filter('woocommerce_coupon_error', array(__class__,
                'woocommerce_coupon_error'), 10, 3);
        add_filter('gettext', array(__class__, 'coupon_remove'), 10, 3);
    }

    public static function make_vars($coupon)
    {
        global $woocommerce;
        $new_array = array();


        if (!function_exists('wc_get_coupon_types')) {

            require_once '../woocommerce/includes/wc-coupon-functions.php';

            $mytypes = wc_get_coupon_types();
        } else {

            $mytypes = wc_get_coupon_types();

        }


        if ($vars = get_object_vars($coupon)) {


            if (strpos($vars['discount_type'], "percent") !== false) {
                $sign = "%";
                $vars['coupon_amount']=$vars['coupon_amount'].$sign;
            } else {
            $vars['coupon_amount']=wc_price($vars['coupon_amount']);
            
            }

           
            if (!empty($mytypes)) {
                foreach ($mytypes as $key2 => $value2) {

                    if ($key2 == $vars['discount_type']) {
                        $vars['discount_type'] = $value2;
                    }
                }
            }
            
          


            if ($vars['expiry_date']) {
                $dateformat = get_option('date_format');
                $vars['expires'] = date_i18n($dateformat, $vars['expiry_date'], $gmt);
            }
            $vars['currency'] = get_option('woocommerce_currency', 'EUR');
            $vars['currencysign'] = get_woocommerce_currency_symbol($vars['currency']);
            $new_keys = array(
                'code' => 'Name',
                'discount_type' => 'Type',
                'coupon_amount' => 'Discount',
                'id' => 'ID',
                'currency' => 'Currency',
                'currencysign' => 'CurrencySymbol',
                'total' => 'TotalPrice',
                'minimum_amount' => 'MinimumRaw',
               
                'maximum_amount' => 'MaximumRaw',
               
                'expires' => 'Expiry');
            foreach ($vars as $key => $value) {
                $new_array[$new_keys[$key]] = $value;
            }
            $new_array = array_filter($new_array);

if (function_exists('wc_price') && key_exists('MinimumRaw', $new_array)) {

                $new_array['MinimumCart'] = wc_price($new_array[MinimumRaw]);

            }

if (function_exists('wc_price') && key_exists('MaximumRaw', $new_array)) {

                $new_array['MaximumCart'] = wc_price($new_array[MaximumRaw]);

            }


            //var_dump($new_array);
            
        }
        return $new_array;
    }


} //ENDE KLASSE

Woo_Custom_Coupon_Notices::wp_init();
?>