<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$attdata = XH_Social_Temp_Helper::get('atts','templete');
$atts = $attdata['atts'];
$api = XH_Social_Add_On_Login::instance();

if(is_user_logged_in()){
    if(method_exists(XH_Social::instance()->WP, 'wp_loggout_html')){
        echo XH_Social::instance()->WP->wp_loggout_html();
        return;
    }else{
        wp_logout();
    }
}

?>
<div class="xh-regbox">
	<div class="xh-title" id="form-title"><?php echo __('Find password',XH_SOCIAL)?></div>
	<form class="xh-form" id="wshop-resetpassword-container">
		<div id="fields-error"></div>
            <?php 
               $methods = $api->page_login_findpassword_methods(); 
               
               $method_options = array();
               foreach ($methods as $method=>$setting){
                   $method_options[$method]= $setting['title'];
               }
               
               echo XH_Social_Helper_Html_Form::generate_select_html('resetpassword', 'method', array(
                   'title'=>__('method',XH_SOCIAL),
                   'type'=>'select',
                   'options'=>$method_options
               ));
               
               foreach ($methods as $method=>$setting){
                   ?>
                   <div id="wsocial-findpassword-method-<?php echo $method?>" class="wsocial-findpassword-method" data-submit="<?php echo isset($setting['submit'])?esc_attr($setting['submit']):''; ?>" style="display:none;">
                   		<?php echo XH_Social_Helper_Html_Form::generate_html('resetpassword',$setting['fields']);?>
                   </div>
                   <?php 
               }
            ?>
            <div class="xh-form-group mt10">
                <button type="button" id="btn-resetpassword" onclick="window.xh_social_view.resetpassword();" class="xh-btn xh-btn-primary xh-btn-block xh-btn-lg"><?php echo __('Reset password',XH_SOCIAL)?></button>
            </div>
	</form>
</div>

<script type="text/javascript">
	(function($){
	    $(document).keypress(function(e) {
			if (e.which == 13){
			　　window.xh_social_view.resetpassword();
			}
		});
		
		window.xh_social_view={
			on_method_change:function(){
				$('.wsocial-findpassword-method').hide();
				var $selector =$('#wsocial-findpassword-method-'+$('#resetpassword_method').val());
				$selector.show();

				var txt_submit = $selector.attr('data-submit');
				if(txt_submit!=null&&txt_submit.length>0){
					$('#btn-resetpassword').text(txt_submit);
				}
			},
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
			resetpassword:function(){
				this.reset();
				
				var data={};
				
				<?php XH_Social_Helper_Html_Form::generate_submit_data('resetpassword', 'data');?>
				if(this.loading){
					return;
				}
				var pre_txt = $('#btn-resetpassword').text();
				$('#btn-resetpassword').attr('disabled','disabled').text('<?php print __('loading...',XH_SOCIAL)?>');
				this.loading=true;

				jQuery.ajax({
		            url: '<?php echo XH_Social::instance()->ajax_url(array('action'=>"xh_social_{$api->id}",'tab'=>'resetpassword'),true,true)?>',
		            type: 'post',
		            timeout: 60 * 1000,
		            async: true,
		            cache: false,
		            data: data,
		            dataType: 'json',
		            complete: function() {
		            	$('#btn-resetpassword').removeAttr('disabled').text(pre_txt);
		            	window.xh_social_view.loading=false;
		            },
		            success: function(m) {
		            	if(m.errcode==0){
		            		location.href='<?php echo wp_login_url()?>';
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
		
		window.xh_social_view.on_method_change();
		$('#resetpassword_method').change(function(){window.xh_social_view.on_method_change();});
	})(jQuery);
</script>