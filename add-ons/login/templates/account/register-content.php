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
do_action('xh_social_page_register_before');

$action = apply_filters('xh_social_page_register_before', null);
if(!empty($action)){
    echo $action;
    return;
}

if(!get_option('users_can_register')){
    ?>
    <div class="xh-regbox">
    <div class="xh-title" id="form-title"><?php echo __('Register',XH_SOCIAL)?></div>
    	<div class="xh-form">  
    		<div class="xh-alert xh-alert-danger">对不起，管理员已关闭了网站注册！</div>     
    		 <div class="xh-form-group mt10">
                   <a href="<?php echo home_url('/')?>" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg">返回首页</a>
               </div>
    	</div>
    </div>
    <?php 
    return;
}

if(is_user_logged_in()){
    if(method_exists(XH_Social::instance()->WP, 'wp_loggout_html')){
        echo XH_Social::instance()->WP->wp_loggout_html($log_on_callback_uri);
        return;
    }else{
        wp_logout();
    }
}
?>
<div class="xh-regbox">
	<div class="xh-title" id="form-title"><?php echo __('Register',XH_SOCIAL)?></div>
	<form class="xh-form">
		<div id="fields-error"></div>
       <?php 
           $fields = $api->page_login_register_fields();
           echo XH_Social_Helper_Html_Form::generate_html('register',$fields);
           do_action('xh_social_page_login_register_form');
       ?>
       <div class="xh-form-group mt10">
           <button type="button" id="btn-register" onclick="window.xh_social_view.register();" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Log In',XH_SOCIAL)?></button>
       </div>
	</form>
</div>

<script type="text/javascript">
	(function($){
		$(document).keypress(function(e) {
			if (e.which == 13){
			　　window.xh_social_view.register();
			}
		});
		window.xh_social_view={
			loading:false,
			reset:function(){
				$('.xh-alert').empty().css('display','none');
			},
			error:function(msg){
				$('#fields-error').html('<div class="xh-alert xh-alert-danger" role="alert">'+msg+' </div>').css('display','block');
			},
			warning:function(msg){
				$('#fields-error').html('<div class="xh-alert xh-alert-warning" role="alert">'+msg+' </div>').css('display','block');
			},
			success:function(msg){
				$('#fields-error').html('<div class="xh-alert xh-alert-success" role="alert">'+msg+' </div>').css('display','block');
			},
			register:function(){
				this.reset();
				
				var data={};
				<?php XH_Social_Helper_Html_Form::generate_submit_data('register', 'data');?>
				
				if(this.loading){
					return;
				}
				
				$('#btn-register').attr('disabled','disabled').text('<?php print __('loading...',XH_SOCIAL)?>');
				this.loading=true;

				jQuery.ajax({
		            url: '<?php echo XH_Social::instance()->ajax_url(array(
		                'action'=>"xh_social_{$api->id}",
		                'tab'=>'register'
		            ),true,true)?>',
		            type: 'post',
		            timeout: 60 * 1000,
		            async: true,
		            cache: false,
		            data: data,
		            dataType: 'json',
		            complete: function() {
		            	$('#btn-register').removeAttr('disabled').text('<?php print __('Log In',XH_SOCIAL)?>');
		            	window.xh_social_view.loading=false;
		            },
		            success: function(m) {
		            	if(m.errcode==0){
		            		window.xh_social_view.success('<?php print __('Registered successfully!',XH_SOCIAL);?>');
							location.href='<?php echo $log_on_callback_uri?>';
							return;
						}

						if(m.errcode==1001){
							window.xh_social_view.warning(m.errmsg);
							location.href='<?php echo admin_url('/')?>';
							return;
						}
		            	
		            	window.xh_social_view.error(m.errmsg);
		            },
		            error:function(e){
		            	window.xh_social_view.error('<?php print __('Internal Server Error!',XH_SOCIAL);?>');
		            	console.error(e.responseText);
		            }
		         });
			}
		};
	})(jQuery);
</script>