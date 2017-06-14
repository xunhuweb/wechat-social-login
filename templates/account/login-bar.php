<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$data = XH_Social_Temp_Helper::get('atts','templates');
$redirect=$data['redirect'];
$channels =XH_Social::instance()->channel->get_social_channels(array('login'));    
?>
<div class="xh_social_box" style="clear:both;">
   <?php 
    foreach ($channels as $channel){
        ?>
        <a href="<?php echo XH_Social::instance()->channel->get_authorization_redirect_uri($channel->id,$redirect);?>" rel="noflow" style="background:url(<?php echo $channel->icon?>) no-repeat transparent;" class="xh_social_login_bar" title="<?php echo $channel->title;?>"></a>
        <?php 
    }?>
</div><?php 