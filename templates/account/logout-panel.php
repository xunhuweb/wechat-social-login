<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$attdata = XH_Social_Temp_Helper::clear('atts','templete');
$log_on_callback_uri = $attdata['log_on_callback_uri'];

if(empty($log_on_callback_uri)){
    $log_on_callback_uri = home_url('/');
}

?>
<div class="xh-regbox">
	<div class="xh-title"><?php echo __('You have logged in, log out?',XH_SOCIAL)?></div>
	<div class="xh-form">
        <div class="xh-form-group" style="margin-top:25px;">
            <a href="<?php echo wp_logout_url(XH_Social_Helper_Uri::get_location_uri())?>" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Log out',XH_SOCIAL)?></a>
        </div>
		<div class="xh-form-group xh-mT20">
        <p style="text-align: center;"><a href="<?php echo $log_on_callback_uri;?>"><?php echo __('back',XH_SOCIAL)?></a></p>
    </div>
	</div>
</div>