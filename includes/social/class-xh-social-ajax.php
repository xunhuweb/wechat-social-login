<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * XH_Social_Ajax class
 *
 * @version     2.1.0
 * @category    Class
 */
class XH_Social_Ajax {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
		    'wsocial_cron'   =>__CLASS__ . '::cron',
		    'xh_social_channel'=>__CLASS__ . '::channel',
		    'xh_social_plugin'=>__CLASS__ . '::plugin',
		    'xh_social_service'=>__CLASS__ . '::service',
		    'xh_social_captcha'=>__CLASS__ . '::captcha',
		    //'xh_social_gc'=>__CLASS__ . '::gc',
		);
		
		$shortcodes = apply_filters('xh_social_ajax', $shortcodes);
		foreach ( $shortcodes as $shortcode => $function ) {
		    add_action ( "wp_ajax_$shortcode",        $function);
		    add_action ( "wp_ajax_nopriv_$shortcode", $function);
		}
	}

	//插件定时服务
	public static function cron(){
	    header("Access-Control-Allow-Origin:*");
	    $last_execute_time = intval(get_option('wsocial_cron',0));
	    $now = time();
	     
	    //间隔30秒
	    $step =$last_execute_time-($now-60);
	    if($step>0){
	        echo 'next step:'.$step;
	        exit;
	    }
	     
	    update_option('wsocial_cron',$now,false);
	     
	    try {
	        do_action('wsocial_cron');
	    } catch (Exception $e) {
	        XH_Social_Log::error($e);
	        //ignore
	    }
	     
	    //清楚session 数据
 	    XH_Social::instance()->session->cleanup_sessions();
	  
	    $plugin_options = XH_Social_Install::instance()->get_plugin_options();
	    $version = $plugin_options&&isset($plugin_options['version'])?$plugin_options['version']:'1.0.0';
	   
	    if(version_compare($version, XH_Social::instance()->version,'<')){
	        XH_Social::instance()->on_update($version);
	        
	        XH_Social_Install::instance()->update_plugin_options(array(
	            'version'=>XH_Social::instance()->version
	        ));
	        
	    }
	    
	    echo 'hello wshop cron';
	    exit;
	}
	
	/**
	 * 验证码
	 * @since 1.0.0
	 */
	public static function captcha(){
	    require_once XH_SOCIAL_DIR.'/includes/captcha/CaptchaBuilderInterface.php';
	    require_once XH_SOCIAL_DIR.'/includes/captcha/PhraseBuilderInterface.php';
	    require_once XH_SOCIAL_DIR.'/includes/captcha/CaptchaBuilder.php';
	    require_once XH_SOCIAL_DIR.'/includes/captcha/PhraseBuilder.php';
	 
	    $action ='xh_social_captcha';
	    $params=shortcode_atts(array(
            'notice_str'=>null,
            'action'=>$action,
             $action=>null
        ), stripslashes_deep($_REQUEST));
	    
	    if(!XH_Social::instance()->WP->ajax_validate($params,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
            XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(701)->errmsg);
            exit;
	    }
	    
	    $builder = Gregwar\Captcha\CaptchaBuilder::create() ->build();
	    XH_Social::instance()->session->set('social_captcha', $builder->getPhrase());
	    
	    echo XH_Social_Error::success($builder ->inline())->to_json();
	    exit;
	}
	
	public static function channel(){  
	    $action ='xh_social_channel';
	 
	    $params=shortcode_atts(array(
	        'notice_str'=>null,
	        'action'=>$action,
	        'tab'=>null,
	        'channel_id'=>null,
	        $action=>null
	    ), stripslashes_deep($_REQUEST));
	   
	    if(!XH_Social::instance()->WP->ajax_validate($params,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(701)->errmsg);
	        exit;
	    }
	  
	    $channel_id = $params['channel_id'];
	    if(empty($channel_id)){
	        XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(404)->errmsg);
	        exit;
	    }
	   
	    $channel = XH_Social::instance()->channel->get_social_channel($channel_id);
	    if(!$channel){
	        XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(404)->errmsg);
	        exit;
	    }
	   
	    switch ($params['tab']){
	        case 'login_redirect_to_authorization_uri':
	            if(is_user_logged_in()){
	                if(isset($_GET['social_logout'])){
	                    wp_redirect(wp_logout_url(XH_Social_Helper_Uri::get_location_uri()));
                        exit;
	                }
	                
	                wp_logout();
	                $params = array();
	                $location = XH_Social_Helper_Uri::get_uri_without_params(XH_Social_Helper_Uri::get_location_uri(),$params);
	                $params['social_logout']=1;
	                wp_redirect($location."?".http_build_query($params));
	                exit;
	            }
	            
	            $login_location_uri = isset($_REQUEST['redirect_to'])&&!empty($_REQUEST['redirect_to'])?esc_url_raw(urldecode($_REQUEST['redirect_to'])):home_url('/');
	            XH_Social::instance()->session->set('social_login_location_uri', $login_location_uri);	          
	            $redirect_uri =$channel->generate_authorization_uri(0, $login_location_uri);	          
	            if(empty($redirect_uri)){
	                XH_Social::instance()->WP->wp_die(XH_Social_Error::error_unknow());
	                exit;
	            }
	            
	            wp_redirect($redirect_uri);
	            exit;
	            
            case 'bind_redirect_to_authorization_uri':
                $login_location_uri = isset($_REQUEST['redirect_to'])&&!empty($_REQUEST['redirect_to'])?esc_url_raw(urldecode($_REQUEST['redirect_to'])):home_url('/');
                global $current_user;
                if(!is_user_logged_in()){
                    wp_redirect(wp_login_url($login_location_uri));
                    exit;
                }
                
                XH_Social::instance()->session->set('social_login_location_uri', $login_location_uri);

                $redirect_uri =$channel->generate_authorization_uri($current_user->ID, $login_location_uri);
                if(empty($redirect_uri)){
                    XH_Social::instance()->WP->wp_die(XH_Social_Error::error_unknow());
                    exit;
                }
                
                wp_redirect($redirect_uri);
                exit;
                
	        case 'do_unbind':
	            $redirect_to = isset($_REQUEST['redirect_to'])&&!empty($_REQUEST['redirect_to'])?esc_url_raw(urldecode($_REQUEST['redirect_to'])):home_url('/');
	            global $current_user;
	            if(!is_user_logged_in()){
	                wp_redirect(wp_login_url($redirect_to));
	                exit;
	            }
	           
	            //判断是否允许解绑
	            $error = apply_filters('xh_social_channel_unbind_before',new WP_Error(), $channel);
	            if($error&&$error instanceof  WP_Error && $error->get_error_code()){
	                wp_redirect($redirect_to);
	                exit;
	            }
	            
	            $error = $channel->remove_ext_user_info_by_wp($current_user->ID);
	            if(!XH_Social_Error::is_valid($error)){
	                XH_Social::instance()->WP->wp_die($error->errmsg);
	                exit;
	            }
	            
	            do_action('xh_social_channel_unbind',$channel);
	            wp_redirect($redirect_to);
	            exit;
	    }
	}
	
	/**
	 * 远程服务
	 */
	public static function service(){
	    if(!XH_Social::instance()->WP->capability()){
	        echo (XH_Social_Error::err_code(501)->to_json());
	        exit;
	    }
	   
	    $action ='xh_social_service';
	    $params=shortcode_atts(array(
	        'notice_str'=>null,
	        'action'=>$action,
	        'tab'=>null,
	        $action=>null
	    ), stripslashes_deep($_REQUEST));
	    
	    if(!XH_Social::instance()->WP->ajax_validate($params,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo (XH_Social_Error::err_code(701)->to_json());
	        exit;
	    }
	    
	    switch ($params['tab']){

	        //第三方扩展
	        case 'extensions':
	            $page_index = isset($_REQUEST['pageIndex'])?intval($_REQUEST['pageIndex']):1;
	            if($page_index<1){
	                $page_index=1;
	            }
	             
	            $keywords = isset($_REQUEST['keywords'])?($_REQUEST['keywords']):'';
	             
	            if(empty($keywords)){
	                $info = get_option('xh-social-ajax:service:extensions:'.$page_index);
	                if(!$info||!is_array($info)){
	                    $info = array();
	                }
	                
	                if(isset($info['last_cache_time'])&&$info['last_cache_time']>time()){
	                    echo XH_Social_Error::success($info)->to_json();
	                    exit;
	                }
	            }
	            
	            $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-v3.php';
	            $params = array();
	            
	            $params['pageIndex']=$page_index;
	            $params['keywords']=$keywords;
	            $params['action']='extensions';
	            $params['license_id'] =XH_Social::license_id;
	            
	            $request =wp_remote_post($api,array(
	                'timeout'=>10,
	                'body'=>$params
	            ));
	             
	            if(is_wp_error( $request )){
	                echo (XH_Social_Error::err_code(1000)->to_json());
	                exit;
	            }
	      
	            $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	            if(!$info||!is_array($info)){
	                echo (XH_Social_Error::err_code(1000)->to_json());
	                exit;
	            } 
	            if(empty($keywords)){
    	            $info['last_cache_time'] =time()+24*60*60;
    	            wp_cache_delete('xh-social-ajax:service:extensions:'.$page_index,'options');
    	            update_option('xh-social-ajax:service:extensions:'.$page_index,$info,false);
	            }
	            echo (XH_Social_Error::success($info)->to_json());

	            exit;
	        case 'plugins':
	            $page_index = isset($_REQUEST['pageIndex'])?intval($_REQUEST['pageIndex']):1;
	            if($page_index<1){
	                $page_index=1;
	            }
	            $category_id=isset($_REQUEST['category_id'])?intval($_REQUEST['category_id']):0;
	            $keywords = isset($_REQUEST['keywords'])?($_REQUEST['keywords']):'';
	            if(empty($keywords)){
	                $info = get_option("xh-social-ajax:service:plugins:{$category_id}:{$page_index}");
	                if(!$info||!is_array($info)){
	                    $info = array();
	                }
	                 
	                if(isset($info['last_cache_time'])&&$info['last_cache_time']>time()){
	                    echo XH_Social_Error::success($info)->to_json();
	                    exit;
	                }
	            }
	            $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-v3.php';
	            $params = array();
	             
	            $params['pageIndex']=$page_index;
	            $params['keywords']=$keywords;
	            $params['action']='plugins';
	            $params['category_id'] =$category_id;
	            
	            $request =wp_remote_post($api,array(
	                'timeout'=>10,
	                'body'=>$params
	            ));
	            
	            if(is_wp_error( $request )){
	                echo (XH_Social_Error::err_code(1000)->to_json());
	                exit;
	            }
	            
	            $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	            if(!$info||!is_array($info)){
	                echo (XH_Social_Error::err_code(1000)->to_json());
	                exit;
	            }
	            if(empty($keywords)){
    	            $info['last_cache_time'] =time()+24*60*60;
    	            wp_cache_delete("xh-social-ajax:service:plugins:{$category_id}:{$page_index}",'options');
    	            update_option("xh-social-ajax:service:plugins:{$category_id}:{$page_index}",$info,false);
	            }
	            echo (XH_Social_Error::success($info)->to_json());
	            
	            exit;
	    }
	}
	/**
	 * 管理员对插件的操作
	 */
	public static function plugin(){
	    
	   if(!XH_Social::instance()->WP->capability()){
	        echo (XH_Social_Error::err_code(501)->to_json());
	        exit;
	    }
	    
	    $action ='xh_social_plugin';
	  
	    $params=shortcode_atts(array(
	        'notice_str'=>null,
	        'action'=>$action,
	        'tab'=>null,
	        'plugin_id'=>null,
	        $action=>null
	    ), stripslashes_deep($_REQUEST));
	    
	    if(!XH_Social::instance()->WP->ajax_validate($params,isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo (XH_Social_Error::err_code(701)->to_json());
	        exit;
	    }
	    
	    $plugins =XH_Social::instance()->WP->get_plugin_list_from_system();
	    if(!$plugins){
	        echo (XH_Social_Error::err_code(404)->to_json());
	        exit;
	    }
	    
	    $add_on =null;
	    $add_on_file='';
	    foreach ($plugins as $file=>$plugin){
	        if($plugin->id==$params['plugin_id']){
	            $add_on_file = $file;
	            $add_on=$plugin;
	            break;
	        }
	    }
	    
        if(!$add_on){
            echo (XH_Social_Error::err_code(404)->to_json());
            exit;
        }
        
	    $cache_time = 2*60*60; 
	    switch ($params['tab']){
	        //插件安装
	        case 'install':
	            $installed = get_option('xh_social_plugins_installed',array());
	            if(!$installed||!is_array($installed)){
	                $installed =array();
	            }
	            $has = false;
	            foreach ($installed as $item){
	                if($item==$add_on_file){
	                    $has=true;break;
	                }
	            }
	           
	            if(!$has){
	                $installed[]=$add_on_file;
	                
	                try {
	                    if($add_on->depends){
	                        foreach ($add_on->depends as $id=> $depend){
	                           $contains = false;
	                           foreach (XH_Social::instance()->plugins as $plugin){
	                               if(!$plugin->is_active){
	                                   continue;
	                               }
	                               
	                               if($plugin->id==$id){
	                                   $contains=true;
	                                   break;
	                               }
	                           }
	                           
	                           if(!$contains){//依赖第三方插件
	                               echo (XH_Social_Error::error_custom(sprintf(__('Current add-on is relies on %s!',XH_SOCIAL),$depend['title']))->to_json());
	                               exit;
	                           }
	                        }
	                    }
	                    
	                    if(!empty($add_on->min_core_version)){
    	                    if(version_compare(XH_Social::instance()->version,$add_on->min_core_version, '<')){
    	                        echo (XH_Social_Error::error_custom(sprintf(__('Core version must greater than or equal to %s!',XH_SOCIAL),$add_on->min_core_version))->to_json());
    	                        exit;
    	                    }
	                    }
	                    $add_on->on_load();
	                    $add_on->on_install();
	                    do_action('wsocial_flush_rewrite_rules');
	                    ini_set('memory_limit','128M');
	                    flush_rewrite_rules();
	                } catch (Exception $e) {
	                    echo (XH_Social_Error::error_custom($e)->to_json());
	                    exit;
	                }
	            }
	           
	            $plugins_find = XH_Social::instance()->WP->get_plugin_list_from_system();
	            if(!$plugins_find||!is_array($plugins_find)){
	                $plugins_find=array();
	            }
	             
	            $options = array();
	            foreach ($installed as $item){
	                $has = false;
	                foreach ($plugins_find as $file=>$plugin){
	                    if($item==$file){
	                        $has =true;
	                        break;
	                    }
	                }
	                if($has){
	                    $options[]=$file;
	                }
	            }
	            
	           wp_cache_delete("xh_social_plugins_installed",'options');
	           update_option('xh_social_plugins_installed', $options,true);
	           
	           echo (XH_Social_Error::success()->to_json());
	           exit;
	        //插件卸载   
	        case 'uninstall':
	            $installed = get_option('xh_social_plugins_installed',array());
	         
	            if(!$installed||!is_array($installed)){
	                $installed =array();
	            }
	            
	            $new_values = array();
	            foreach ($installed as $item){
	                if($item!=$add_on_file){
	                    $new_values[]=$item;
	                }
	            }
	           
	            try {
	                foreach (XH_Social::instance()->plugins as $plugin){
	                    if(!$plugin->is_active){
	                        continue;
	                    }
	                    
	                    if(!$plugin->depends){
	                        continue;
	                    }
	                    
	                    foreach ($plugin->depends as $id=>$depend){
	                        if($id==$add_on->id){
	                            echo (XH_Social_Error::error_custom(sprintf(__('"%s" is relies on current add-on!',XH_SOCIAL),$plugin->title))->to_json());
	                            exit;
	                        }
	                    }
	                }
	                
	                $add_on->on_uninstall();
	            } catch (Exception $e) {
	                echo (XH_Social_Error::error_custom($e)->to_json());
	                exit;
	            }
	            
	            $plugins_find = XH_Social::instance()->WP->get_plugin_list_from_system();
	            if(!$plugins_find||!is_array($plugins_find)){
	                $plugins_find=array();
	            }
	            
	            $options = array();
	            foreach ($new_values as $item){
	                $has = false;
	                foreach ($plugins_find as $file=>$plugin){
	                    if($item==$file){
	                        $has =true;
	                        break;
	                    }
	                }
	                if($has){
	                    $options[]=$file;
	                }
	            }
	            
	            wp_cache_delete('xh_social_plugins_installed', 'options');
	            $update =update_option('xh_social_plugins_installed', $options,true);
	            echo (XH_Social_Error::success()->to_json());
	            exit;
	        //插件更新
	        case 'update':
	        case 'update_admin_options':
	        case 'update_plugin_list':
	           $info =get_option("xh-social-ajax:plugin:update:{$add_on->id}");
	           if(!$info||!is_array($info)){
	               $info=array();
	           }
	          
	           if(!isset($info['_last_cache_time'])||$info['_last_cache_time']<time()){
	               $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-add-ons.php';
	               $params = array(
	                   'l'=>$add_on->id,
	                   's'=>get_option('siteurl'),
	                   'v'=>$add_on->version,
	                   'a'=>'update'
	               );
	               //插件为非授权插件
	                $license =null;
	                $info =XH_Social_Install::instance()->get_plugin_options();
	                if($info){
	                    if(isset($info[$add_on->id])){
	                        $license=$info[$add_on->id];
	                    }
	                    
	                    if(empty($license)){
	                        $license = isset($info['license'])?$info['license']:null;
	                    }
	                }
	                if(empty($license)){
	                    echo XH_Social_Error::error_unknow()->to_json();
	                    exit;
	                }
	                
	               $params['c']=$license;
	                
	               $request =wp_remote_post($api,array(
	                   'timeout'=>10,
	                   'body'=>$params
	               ));
	              
	               if(is_wp_error( $request )){
	                   echo (XH_Social_Error::error_custom($request)->to_json());
	                   exit;
	               }
	               
	               $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	               if(!$info||!is_array($info)){
	                   echo (XH_Social_Error::error_unknow()->to_json());
	                   exit;
	               }
	               
	               //缓存30分钟
	               $info['_last_cache_time'] = time()+$cache_time;
	               update_option("xh-social-ajax:plugin:update:{$add_on->id}", $info,false);
	           }
	            
	           $msg =XH_Social_Error::success();
	           switch($params['tab']){
	               case 'update_admin_options':
	                   $txt =sprintf(__('There is a new version of %s - %s. <a href="%s" target="_blank">View version %s details</a> or <a href="%s" target="_blank">download now</a>.',XH_SOCIAL),
	                       $info['name'],
	                       $info['upgrade_notice'],
	                       $info['homepage'],
	                       $info['version'],
	                       $info['download_link']
	                       );
	                   $msg = new XH_Social_Error(0, version_compare($add_on->version,  $info['version'],'<')?$txt:'');
	                   break;
	               case 'update_plugin_list':
	                   $txt =sprintf(__('<tr class="plugin-update-tr active">
	                       <td colspan="3" class="plugin-update colspanchange">
	                       <div class="notice inline notice-warning notice-alt">
	                       <p>There is a new version of %s available.<a href="%s"> View version %s details</a> or <a href="%s" class="update-link">download now</a>.</p>
	                       <div class="">%s</div>
	                       </div></td></tr>',XH_SOCIAL),
	                       $info['name'],
	                       $info['homepage'],
	                       $info['version'],
	                       $info['download_link'],
	                       $info['upgrade_notice']
	                   );
	                   $msg = new XH_Social_Error(0, version_compare($add_on->version,  $info['version'],'<')?$txt:'');
	                   break; 
	           }
	           
	           echo $msg->to_json();
	           exit;
	    }
	}
}
