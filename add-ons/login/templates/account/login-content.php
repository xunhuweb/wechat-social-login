<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$attdata = XH_Social_Temp_Helper::get('atts','templete');
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
 
do_action('xh_social_page_login_before');

$action = apply_filters('xh_social_page_login_before', null);
if(!empty($action)){
    echo $action;
    return;
}

if(is_user_logged_in()){
    echo XH_Social::instance()->WP->wp_loggout_html($log_on_callback_uri);
    return;
}

?>
<div class="xh-regbox">
	<div class="xh-title" id="form-title"><?php echo __('Login',XH_SOCIAL)?></div>
	<form class="xh-form">
		<div id="fields-error"></div>
            <?php 
               $fields = $api->page_login_login_fields(); 
               echo XH_Social_Helper_Html_Form::generate_html('login',$fields);
               
               do_action('xh_social_page_login_login_form');
            ?>
            <div class="xh-form-group mt10">
                <button type="button" id="btn-login" onclick="window.xh_social_view.login();" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Log On',XH_SOCIAL)?></button>
            </div>
        	<?php 
        	$channels = XH_Social::instance()->channel->get_social_channels(array('login'));
        	if(count($channels)>0){
        	    ?>
        	    <div class="xh-form-group xh-mT20">
                    <label><?php echo __('Quick Login',XH_SOCIAL)?></label>
                   <div class="xh-social">
                       <?php foreach ($channels as $channel){
                           ?><a href="<?php echo XH_Social::instance()->channel->get_authorization_redirect_uri($channel->id,$log_on_callback_uri);?>" class="xh-social-item" style="background:url(<?php echo $channel->icon?>) no-repeat transparent;"></a><?php 
                       }?>
                   </div>
                </div>
        	    <?php 
        	}
        	?>
	</form>
</div>

<script type="text/javascript">
	(function($){
		window.xh_social_view={
			loading:false,
			reset:function(){
				$('.xh-alert').empty().css('display','none');
			},
			error:function(msg){
				$('#fields-error').html('<div class="xh-alert xh-alert-danger" role="alert">'+msg+' </div>').css('display','block');
			},
			success:function(msg){
				$('#fields-error').html('<div class="xh-alert xh-alert-success" role="alert">'+msg+' </div>').css('display','block');
			},
			login:function(){
				this.reset();
				
				var data={};
				
				<?php XH_Social_Helper_Html_Form::generate_submit_data('login', 'data');?>
				if(this.loading){
					return;
				}
				
				$('#btn-login').attr('disabled','disabled').text('<?php print __('loading...',XH_SOCIAL)?>')
				this.loading=true;

				jQuery.ajax({
		            url: '<?php echo XH_Social::instance()->ajax_url(array(
		                'action'=>"xh_social_{$api->id}",
		                'tab'=>'login'
		            ),true,true)?>',
		            type: 'post',
		            timeout: 60 * 1000,
		            async: true,
		            cache: false,
		            data: data,
		            dataType: 'json',
		            complete: function() {
		            	$('#btn-login').removeAttr('disabled').text('<?php print __('Log On',XH_SOCIAL)?>')
		            	window.xh_social_view.loading=false;
		            },
		            success: function(m) {
		            	if(m.errcode==405||m.errcode==0){
		            		window.xh_social_view.success('<?php print __('Congratulations, log on successfully!',XH_SOCIAL);?>');   				           
		            		location.href='<?php echo $log_on_callback_uri?>';
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