<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$attdata = XH_Social_Temp_Helper::clear('atts','templete');
$atts = $attdata['atts'];
$api = XH_Social_Add_On_Login::instance();

$log_on_callback_uri=esc_url_raw(XH_Social_Shortcodes::get_attr($atts, 'redirect_to'));
if(empty($log_on_callback_uri)){
    if(isset($_GET['redirect_to'])){
        $log_on_callback_uri =esc_url_raw(urldecode($_GET['redirect_to']));
    }
}

if(empty($log_on_callback_uri)){
    $log_on_callback_uri =home_url('/');
}

if(strcasecmp(XH_Social_Helper_Uri::get_location_uri(), $log_on_callback_uri)===0){
    $log_on_callback_uri =home_url('/');
}

XH_Social::instance()->session->set('social_login_location_uri',$log_on_callback_uri);

if(is_user_logged_in()){
    if(method_exists(XH_Social::instance()->WP, 'wp_loggout_html')){
        echo XH_Social::instance()->WP->wp_loggout_html($log_on_callback_uri);
        return;
    }else{
        wp_logout();
    }
}

?>
<script type="text/javascript">
window.__wsocial_enable_entrl_submit=true;
</script>
<div class="xh-regbox">
	<?php 
	 echo XH_Social::instance()->WP->requires($api->dir, 'account/__login.php',array(
	     'log_on_callback_uri'=>$log_on_callback_uri
	 ));
	?>
</div>

