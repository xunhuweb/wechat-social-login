<?php 
/*
 Template Name: Social - Register
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html>
	<head>
	<title><?php echo the_title();?></title>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
		<link media="all" type="text/css" rel="stylesheet" href="<?php print XH_SOCIAL_URL?>/assets/css/social.css">	
		<?php
		global $wp_scripts;
		if(isset($wp_scripts->registered['jquery-core'])&&isset($wp_scripts->registered['jquery-core']->src)){
		    ?>
		    <script src="<?php echo $wp_scripts->base_url.$wp_scripts->registered['jquery-core']->src; ?>"></script>
		    <?php 
		}else{
		    ?><script src="https://code.jquery.com/jquery-3.2.1.min.js"></script><?php 
		}
		?>
		<style type="text/css">body{background:#f5f5f5;}</style>
	</head>
	<body>	
	<div class="xh-reglogo"><a href="<?php echo home_url('/')?>"><img src="<?php echo XH_Social_Settings_Default_Other_Default::instance()->get_option('logo')?>"></a></div>
	 <?php
	    while ( have_posts() ) : 
	       the_post();
	       the_content();
		// End the loop.
		endwhile;
	 ?>
	  <div class="xh-user-register"><a href="<?php echo home_url('/')?>"><?php echo __('Home',XH_SOCIAL)?></a>|<a href="<?php echo wp_login_url(isset($_GET['redirect_to'])?urldecode($_GET['redirect_to']):'')?>"><?php echo __('Login',XH_SOCIAL)?></a></div>
	</body>
</html>