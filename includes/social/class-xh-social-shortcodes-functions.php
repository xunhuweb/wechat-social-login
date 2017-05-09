<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!function_exists('xh_social_loginbar')){
    function xh_social_loginbar($redirect_uri='',$echo =true){
        if($echo)
        echo XH_Social_Hooks::show_loginbar($redirect_uri);
        return XH_Social_Hooks::show_loginbar($redirect_uri);
    }
}

if(!function_exists('xh_social_accountbind')){
    function xh_social_accountbind($echo =true){
        if($echo)
        echo XH_Social_Hooks::accountbind();
        return XH_Social_Hooks::accountbind();
    }
}

if(!function_exists('xh_social_share')){
    function xh_social_share($echo =true){
        $channels = XH_Social::instance()->channel->get_social_channels(array('share'));
        if(count($channels)<=0){
            return '';
        }
        
        $channel_share_enableds = XH_Social_Settings_Default_Other_Default::instance()->get_option('share');
        if(count($channel_share_enableds)<=0||!is_array($channel_share_enableds)){
            return '';
        }
       
        $options = array();
        foreach ($channels as $channel){
            if(in_array($channel->id, $channel_share_enableds)){
                $options[]=$channel;
            }
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
						[].forEach.call(summary.querySelectorAll('img'), function(a){ pic += (pic?'||':'') + encodeURIComponent(a.src); });
						summary = encodeURIComponent(summary.innerText.replace(/\r|\n|\t/g,'').replace(/ +/g,' ').replace(/<!--(.*)\/\/-->/g,'').substr(0,80));
					}

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
	    <div class="xh_social_box" style="clear:both;">
    	   <?php 
	        foreach ($options as $channel){
    	        ?>
    	        <a href="javascript:void(0);" onclick="window.xh_social_share(<?php echo esc_attr(json_encode($channel->get_share_link()));?>);" rel="noflow" style="background:url(<?php echo $channel->icon?>) no-repeat transparent; background-size:24px 24px;height:24px;width:24px;" class="xh_social_login_bar" title="<?php echo $channel->title;?>"></a>
    	        <?php 
    	    }?>
	    </div><?php 
	    
	    if($echo){
	        echo ob_get_clean();
	    }else{
	        return ob_get_clean();
	    }
       
    }
}
?>