<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if(defined('WSOCIAL_LOGIN')){
    return;
}

$log_on_callback_uri = XH_Social_Helper_Uri::get_location_uri();
?>
<div id="wsocial-dialog-login" style="display:none;position: fixed;z-index: 999;">
	<div class="xh-cover"></div>
	<div class="xh-regbox xh-window">
		<?php 
		 echo XH_Social::instance()->WP->requires(XH_Social_Add_On_Login::instance()->dir, 'account/__login.php',array(
		     'log_on_callback_uri'=>$log_on_callback_uri
		 ));
		?>
         <div class="xh-user-register xh-w">
           <a href="<?php echo wp_registration_url()?>"><?php echo __('Register',XH_SOCIAL)?></a>|<a href="<?php echo wp_lostpassword_url()?>"><?php echo __( 'Lost your password?',XH_SOCIAL )?></a>
        </div>
		<a class="xh-close" href="javascript:void(0);"></a>
	</div>
</div>


<script type="text/javascript">
    (function($){
    	$('#wsocial-dialog-login .xh-close,#wsocial-dialog-login .xh-cover').click(function(){
    		window.__wsocial_enable_entrl_submit=false;
    		$('#wsocial-dialog-login').hide();
    	});
    	window.wsocial_dialog_login_show=function(){
        	$('#wsocial-dialog-login').css('display','block');
    	    window.__wsocial_enable_entrl_submit=true;
			window.__modal_wsocial_login_resize();
        };
        window.__modal_wsocial_login_resize=function(){
			var $ul =$('#wsocial-dialog-login');
			var width = window.innerWidth,height = window.innerHeight;
			if (typeof width != 'number') { 
			    if (document.compatMode == 'CSS1Compat') {
			        width = document.documentElement.clientWidth;
			        height = document.docuementElement.clientHeight;
			    } else {
			        width = document.body.clientWidth;
			        height = document.body.clientHeight; 
			    }
			}
			$ul.css({
				top:((height - $ul.height()) / 2) + "px",
				left:((width - $ul.width()) / 2) + "px"
			});
		};
    	$(window).resize(function(){
    		window.__modal_wsocial_login_resize();
    	});
    })(jQuery);
</script>