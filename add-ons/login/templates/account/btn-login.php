<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data = XH_Social_Temp_Helper::clear('atts','templates');

$atts = is_array($data['atts'])?$data['atts']:array();
$style = isset($atts['style'])?$atts['style']:null;
$class = isset($atts['class'])?$atts['class']:'xh-btn xh-btn-danger';
if(is_user_logged_in()){
    echo apply_filters('wsocial_btn_is_user_logged_in', null);
    return;
}

ob_start();
if(XH_Social_Helper_Uri::is_app_client()){
    ?><a class="<?php echo $class;?>" style="<?php echo $style;?>" href="<?php echo wp_login_url(XH_Social_Helper_Uri::get_location_uri())?>" ><?php echo __('Log On',XH_SOCIAL)?></a><?php
}else{
    ?><a class="<?php echo $class;?>" style="<?php echo $style;?>" href="javascript:void(0);" onclick="window.wsocial_dialog_login_show();"><?php echo __('Log On',XH_SOCIAL)?></a><?php
}

echo apply_filters('wsocial_btn_is_not_user_logged_in', ob_get_clean(),$atts);
