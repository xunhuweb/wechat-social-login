<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$channels = XH_Social::instance()->channel->get_social_channels(array('login'));
global $current_user;
if(!is_user_logged_in()){
    return;
}
if(count($channels)==0){
    return;
}
?>
<div class="xh-regbox" style="width: 100%;">
  <h4 class="xh-title" style="margin-bottom:40px"><?php echo __('Account Binding/Unbundling',XH_SOCIAL)?></h4> 
  <div class="xh-form ">
  <?php if($channels){
            $disable_wechat =XH_Social_Helper_Uri::is_app_client()&& !XH_Social_Helper_Uri::is_wechat_app();
		    foreach ($channels as $channel){
		        if($disable_wechat&&$channel->id==='add_ons_social_wechat'){
		            continue;
		        }
		        ?>
                <div class="xh-form-group xh-mT20  xh-social clearfix">
                     <div class="xh-left"><span class="xh-text"><img src="<?php echo $channel->icon?>" style="width:25px;vertical-align:middle;"/> <?php echo $channel->title?></span></div>
                     <div class="xh-right"><?php echo $channel->bindinfo($current_user->ID)?></div>
                </div>
                <hr/>
      <?php }
  }?>    
  </div>
</div>
<?php 