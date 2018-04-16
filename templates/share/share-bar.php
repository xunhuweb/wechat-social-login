<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$channel_share_enableds = XH_Social_Settings_Default_Other_Share::instance()->get_option('share');
if(count($channel_share_enableds)<=0||!is_array($channel_share_enableds)){
    return;
}
if(XH_Social_Helper_Uri::is_wechat_app()&&isset($channel_share_enableds['social_wechat'])){
    unset($channel_share_enableds['social_wechat']);
}
ob_start();
?>
<script type="text/javascript">
	if(typeof window.xh_social_share!='function'){
		window.xh_social_share=function(settings){
			if(!settings||typeof settings!='object'){return;}
			var url = encodeURIComponent(location.href);
			var title = encodeURIComponent(document.title);
			var summary = document.querySelector('.entry-content') || document.querySelector('article') || document.querySelector('main') || document.querySelector('body') || '';
			var pic = '';
			
			if(summary){
				var index = 0;
				[].forEach.call(summary.querySelectorAll('img'), function(a){
					 if(index++>=3){
						return false;
					 }
					 pic += (pic?'||':'') + encodeURIComponent(a.src); 
				});
				
				summary = encodeURIComponent(summary.innerText.replace(/\r|\n|\t/g,'').replace(/ +/g,' ').replace(/<!--(.*)\/\/-->/g,'').substr(0,80));
			}
			<?php 
			global $wp_query;
			$img_url = $wp_query->post? get_the_post_thumbnail_url($wp_query->post,array(300,300)):null;
			if($img_url){
			    ?>
			    var single_img ='<?php echo esc_url($img_url)?>';
			    pic = pic.replace(single_img+'||','');
			    pic = pic.replace(single_img,'');
			    
			    pic = single_img+(pic?'||':'')+pic;
			    
			    <?php 
			}
			?>
			var link =typeof settings.link!='undefined'?settings.link:'';
			if(!link||link.length<=0){return;}
			if(!/summary/.test(link) && summary) {
				title = title + ': ' + summary + '.. ';
			}
			
			link = link.replace("{url}",url).replace("{title}",title).replace("{summary}",summary).replace("{img}",pic);
			var iWidth=typeof settings.width!='undefined'?settings.width: 450;
			var	iHeight=typeof settings.height!='undefined'?settings.height: 450;
			var iTop = (window.screen.height-30-iHeight)/2; //获得窗口的垂直位置;
			var iLeft = (window.screen.width-10-iWidth)/2; //获得窗口的水平位置;
			window.open(link, 'share', 'height='+iHeight+',innerHeight='+iHeight+',width='+iWidth+',innerWidth='+iWidth+',top='+iTop+',left='+iLeft+',menubar=0,scrollbars=1,resizable=1,status=1,titlebar=0,toolbar=0,location=1');
		}
	}
</script>
<?php 
$scripts = apply_filters('xh_social_share_scripts', ob_get_clean());

ob_start();
?>
<div class="xh_social_box" style="clear:both;">
   <?php 
    foreach ($channel_share_enableds as $channel_id){
        $channel = XH_Social::instance()->channel->get_social_channel($channel_id,array('share'));
        if(!$channel){continue;}
        ?>
        <a href="javascript:void(0);" onclick="window.xh_social_share(<?php echo esc_attr(json_encode($channel->get_share_link()));?>);" rel="noflow" style="background:url(<?php echo $channel->icon?>) no-repeat transparent; background-size:24px 24px;height:24px;width:24px;" class="xh_social_login_bar" title="<?php echo $channel->title;?>"></a>
        <?php 
    }?>
</div>
<?php 
echo apply_filters('xh_social_share_content',$scripts. ob_get_clean());