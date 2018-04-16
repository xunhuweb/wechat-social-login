<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$attdata = XH_Social_Temp_Helper::clear('atts','templete');
$log_on_callback_uri = $attdata['log_on_callback_uri'];
$include_css= $attdata['include_css'];
$include_header_footer =$attdata['include_header_footer'];
$include_html = $attdata['include_html'];

if(empty($log_on_callback_uri)){
    $log_on_callback_uri = home_url('/');
}

if($include_html){
    ?>
    <!DOCTYPE html>
<html>
	<head>
	<title><?php the_title();?></title>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
	</head>
	<body>	
    <?php 
}

if($include_css){
    ?><link media="all" type="text/css" rel="stylesheet" href="<?php print XH_SOCIAL_URL?>/assets/css/social.css" />	
      <style type="text/css">body{background:#f5f5f5;}</style>
     <?php
}

if($include_header_footer){
    ?>
    <div class="xh-reglogo"><a href="<?php echo home_url('/')?>"><img src="<?php echo XH_Social_Settings_Default_Other_Default::instance()->get_option('logo')?>"></a></div>
    <?php 
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

<?php 
if($include_header_footer){
    ?>
     <div class="xh-user-register"><a href="<?php echo home_url('/')?>"><?php echo __('Home',XH_SOCIAL)?></a>|<a href="<?php echo wp_registration_url()?>"><?php echo __('Register',XH_SOCIAL)?></a>|<a href="<?php echo wp_lostpassword_url()?>"><?php echo __( 'Lost your password?',XH_SOCIAL )?></a></div>
    <?php 
}

if($include_html){
    ?>
    </body>
    </html>
    <?php 
}
?>