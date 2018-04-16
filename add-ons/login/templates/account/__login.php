<?php
$data = XH_Social_Temp_Helper::clear('atts','templates');
$log_on_callback_uri  = $data['log_on_callback_uri'];
$api = XH_Social_Add_On_Login::instance();
$context = XH_Social_Helper::generate_unique_id();

if(!defined('WSOCIAL_LOGIN')){
    define('WSOCIAL_LOGIN', true);
}
?>
<div class="xh-title"><?php echo __('Login',XH_SOCIAL)?></div>
<form class="xh-form">
	<div id="fields-error-<?php echo $context?>"></div>
        <?php 
           $fields = $api->page_login_login_fields(); 
           echo XH_Social_Helper_Html_Form::generate_html('login_'.$context,$fields);
           
           do_action('xh_social_page_login_login_form',$context);
        ?>
        <div class="xh-form-group mt10">
            <button type="button" id="btn-login-<?php echo $context?>" onclick="window.xh_social_view_<?php echo $context?>.login();" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Log On',XH_SOCIAL)?></button>
        </div>
    	<?php 
    	$channels = XH_Social::instance()->channel->get_social_channels(array('login'));
    	if(count($channels)>0){
    	    ?>
    	    <div class="xh-form-group xh-mT20">
                <label><?php echo __('Quick Login',XH_SOCIAL)?></label>
               <div class="xh-social">
                   <?php foreach ($channels as $channel){
                       ?><a title="<?php echo esc_attr($channel->title)?>" href="<?php echo XH_Social::instance()->channel->get_authorization_redirect_uri($channel->id,$log_on_callback_uri);?>" class="xh-social-item" style="background:url(<?php echo $channel->icon?>) no-repeat transparent;"></a><?php 
                   }?>
               </div>
            </div>
    	    <?php 
    	}
    	?>
</form>
<script type="text/javascript">
	(function($){
	   $(document).keypress(function(e) {
		   if(window.__wsocial_enable_entrl_submit){
    			if (e.which == 13){
    			　　window.xh_social_view_<?php echo $context?>.login();
    			}
		   }
		});
		window.xh_social_view_<?php echo $context?>={
			loading:false,
			reset:function(){
				$('.xh-alert').empty().css('display','none');
			},
			error:function(msg){
				$('#fields-error-<?php echo $context?>').html('<div class="xh-alert xh-alert-danger" role="alert">'+msg+' </div>').css('display','block');
			},
			success:function(msg){
				$('#fields-error-<?php echo $context?>').html('<div class="xh-alert xh-alert-success" role="alert">'+msg+' </div>').css('display','block');
			},
			login:function(){
				this.reset();
				
				var data={};
				
				<?php XH_Social_Helper_Html_Form::generate_submit_data('login_'.$context, 'data');?>
				if(this.loading){
					return;
				}
				
				$('#btn-login-<?php echo $context?>').attr('disabled','disabled').text('<?php print __('loading...',XH_SOCIAL)?>');
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
		            	$('#btn-login-<?php echo $context?>').removeAttr('disabled').text('<?php print __('Log On',XH_SOCIAL)?>');
		            	window.xh_social_view_<?php echo $context?>.loading=false;
		            },
		            success: function(m) {
		            	if(m.errcode==405||m.errcode==0){
		            		window.xh_social_view_<?php echo $context?>.success('<?php print __('Log on successfully!',XH_SOCIAL);?>');   				           

		            		if (window.top&&window.top != window.self) {
			            		var $wp_dialog = jQuery('#wp-auth-check-wrap',window.top.document);
			            		if($wp_dialog.length>0){$wp_dialog.hide();return;}
		            	    }
		            		location.href='<?php echo $log_on_callback_uri?>';
							return;
						}
		            	
		            	window.xh_social_view_<?php echo $context?>.error(m.errmsg);
		            },
		            error:function(e){
		            	window.xh_social_view_<?php echo $context?>.error('<?php print __('Internal Server Error!',XH_SOCIAL);?>');
		            	console.error(e.responseText);
		            }
		         });
			}
		};
	})(jQuery);
</script>