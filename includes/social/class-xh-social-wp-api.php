<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wordpress apis
 * 
 * @author rain
 * @since 1.0.0
 */
class XH_Social_WP_Api{
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var XH_Social_WP_Api
     */
    private static $_instance = null;
    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return XH_Social - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    
    private function __construct(){}
    
    /**
     * 根据昵称，创建user_login
     * @param string $nickname
     * @return string
     * @since 1.0.1
     */
    public function generate_user_login($nickname){
        $nickname = sanitize_user(XH_Social_Helper_String::remove_emoji($nickname));
        if(empty($nickname)){
            $nickname = XH_Social_Helper_String::guid();
        }
        
        if(mb_strlen($nickname)>32){
            $nickname = mb_substr($nickname, 0,32);
        }
        
        $pre_nickname =$nickname;
    
        $index=0;
        while (username_exists($nickname)){
            $index++;
            if($index==1){
                $nickname=$pre_nickname.'_'.date('yz');//年+一年中的第N天
                continue;
            }
            
            //加随机数
            $nickname.=mt_rand(1000, 9999);
            if(strlen($nickname)>60){
                $nickname = $pre_nickname.'_'.time();
            }
            
            //尝试次数过多
            if($index>5){
                return XH_Social_Helper_String::guid();
            }
        }
    
        return $nickname;
    }
    
    /**
     * 设置错误
     * @param string $key
     * @param string $error
     * @since 1.0.5
     */
    public function set_wp_error($key,$error){
        XH_Social::instance()->session->set("error_{$key}", $error);
    }
    
    /**
     * 清除错误
     * @param string $key
     * @param string $error
     * @since 1.0.5
     */
    public function unset_wp_error($key){
        XH_Social::instance()->session->__unset("error_{$key}");
    }
    
    /**
     * 获取错误
     * @param string $key
     * @param string $error
     * @since 1.0.5
     */
    public function get_wp_error($key,$clear=true){
        $cache_key ="error_{$key}";
        $session =XH_Social::instance()->session;
        $error = $session->get($cache_key);
        if($clear){
            $this->unset_wp_error($key);
        }
        return $error;
    }
    
    public function wp_die($err=null){
        if($err){
            if($err instanceof Exception){
                $err = "errcode:{$err->getCode()},errmsg:{$err->getMessage()}";
            }
        }
        if(empty($err)){
            $err = XH_Social_Error::err_code(500)->errmsg;
        }
        ?>
        <!DOCTYPE html>
        <html>
            <head>
            	<title>抱歉，出错了!</title>
                <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0">
                <link rel="stylesheet" type="text/css" href="<?php echo XH_SOCIAL_URL.'/assets/css/weui.css'?>">
            </head>
            <body>
               <div class="weui_msg">
               <div class="weui_icon_area"><i class="weui_icon_warn weui_icon_msg"></i></div>
               <div class="weui_text_area">
               <h4 class="weui_msg_title">抱歉，出错了!</h4>
               <p class="weui_msg_desc"><?php echo $err;?></p>
               </div>
               </div>
            </body>
        </html>
        <?php 
        exit;
    }
    
    /**
     * 执行登录操作
     * @param WP_User $wp_user
     * @since 1.0.0
     */
    public function do_wp_login($wp_user){
        XH_Social::instance()->session->__unset('social_login_location_uri');
    
        $secure_cookie='';
        if ( get_user_option('use_ssl', $wp_user->ID) ) {
            $secure_cookie = true;
            force_ssl_admin(true);
        }
    
        wp_set_auth_cookie($wp_user->ID, true, $secure_cookie);
        /**
         * Fires after the user has successfully logged in.
         *
         * @since 1.5.0
         *
         * @param string  $user_login Username.
         * @param WP_User $user       WP_User object of the logged-in user.
         */
        do_action( 'wp_login', $wp_user->user_login, $wp_user );
    }
    
    public function clear_captcha(){
        XH_Social::instance()->session->__unset('social_captcha');
    }

    /**
     * 获取图片验证字段
     * @return array
     * @since 1.0.0
     */
    public function get_captcha_fields(){
        $fields['captcha']=array(
            'type'=>function($form_id,$name,$settings){
                    $form_name = $name;
                    $name = $form_id."_".$name;
                    ob_start();
                    ?>
                   <div class="xh-input-group" style="width:100%;">
                        <input name="<?php echo esc_attr($name);?>" type="text" id="<?php echo esc_attr($name);?>" maxlength="6" class="form-control" placeholder="<?php echo __('image captcha',XH_SOCIAL)?>">
                        <span class="xh-input-group-btn" style="width:96px;"><img alt="loading..." style="width:96px;height:35px;border:1px solid #ddd;background:url('<?php echo XH_SOCIAL_URL?>/assets/image/loading.gif') no-repeat center;" id="img-captcha-<?php echo esc_attr($name);?>"/></span>
                    </div>
                    
                    <script type="text/javascript">
            			(function($){
            				if(!$){return;}

                            window.captcha_<?php echo esc_attr($name);?>_load=function(){
                            	$('#img-captcha-<?php echo esc_attr($name);?>').attr('src','');
                            	$.ajax({
        				            url: '<?php echo XH_Social::instance()->ajax_url('xh_social_captcha')?>',
        				            type: 'post',
        				            timeout: 60 * 1000,
        				            async: true,
        				            cache: false,
        				            data: {},
        				            dataType: 'json',
        				            success: function(m) {
        				            	if(m.errcode==0){
        				            		$('#img-captcha-<?php echo esc_attr($name);?>').attr('src',m.data);
        								}
        				            }
        				         });
                            };
                            
            				$('#img-captcha-<?php echo esc_attr($name);?>').click(function(){
            					window.captcha_<?php echo esc_attr($name);?>_load();
            				});
            				
            				window.captcha_<?php echo esc_attr($name);?>_load();
            				window._submit_<?php echo esc_attr($form_id);?>(function(data){
            					if(!data){data={};}
            					data.<?php echo esc_attr($form_name)?>=$('#<?php echo esc_attr($name)?>').val();
            				});
            			})(jQuery);
                    </script>
                <?php 
                return ob_get_clean();
            },
            'validate'=>function($name,$datas,$settings){
                //插件未启用，那么不验证图形验证码     
                $code_post =isset($_POST[$name])?trim($_POST[$name]):'';
                if(empty($code_post)){
                    return XH_Social_Error::error_custom(__('image captcha is required!',XH_SOCIAL));
                }
                
                $captcha =XH_Social::instance()->session->get('social_captcha');
                if(empty($captcha)){
                    return XH_Social_Error::error_custom(__('Please refresh the image captcha!',XH_SOCIAL));
                }
                
                if($captcha!=$code_post){
                    return XH_Social_Error::error_custom(__('image captcha is invalid!',XH_SOCIAL));
                }
                
                XH_Social::instance()->session->__unset('social_captcha');
               
                return $datas;
            }
        );
    
        return apply_filters('xh_social_captcha_fields', $fields);
    }
    
    /**
     * 获取插件列表
     * @return NULL|Abstract_XH_Social_Add_Ons[]
     */
    public function get_plugin_list_from_system(){
        $base_dirs= array(
            WP_CONTENT_DIR.'/wechat-social-login/add-ons/',
            WP_CONTENT_DIR.'/xh-social/add-ons/',
            XH_SOCIAL_DIR.'/add-ons/',
        );
    
        $plugins = array();

        $include_files = array();
        
        foreach ($base_dirs as $base_dir){
            try {
                if(!is_dir($base_dir)){
                    continue;
                }
        
                $handle = opendir($base_dir);
                if(!$handle){
                    continue;
                }
                
                try {
                    while(($file = readdir($handle)) !== false){
                        if(empty($file)||$file=='.'||$file=='..'||$file=='index.php'){
                            continue;
                        }
        
                        if(in_array($file, $include_files)){
                            continue;
                        }
                        //排除多个插件目录相同插件重复includ的错误
                        $include_files[]=$file;
                        
                        try {
                            if(strpos($file, '.')!==false){
                                if(stripos($file, '.php')===strlen($file)-4){
                                    $file=str_replace("\\", "/",$base_dir.$file);
                                }
                            }else{
                                $file=str_replace("\\", "/",$base_dir.$file."/init.php");
                            }
        
                           
                            if(file_exists($file)){
                                $add_on=null;
                                
                                if(isset(XH_Social::instance()->plugins[$file])){
                                    //已安装
                                    $add_on=XH_Social::instance()->plugins[$file];
                                }else{
                                    //未安装
                                    $add_on = require_once $file;
                                    if($add_on&&$add_on instanceof Abstract_XH_Social_Add_Ons){
                                        $add_on->is_active=false;
                                        XH_Social::instance()->plugins[$file]=$add_on;
                                    }else{
                        	            $add_on=null;
                        	        }
                                } 
                                
                                if($add_on){
                                    $plugins[$file]=$add_on;
                                }
                            }
        
                        } catch (Exception $e) {
                        }
                    }
                } catch (Exception $e) {
                }
        
                closedir($handle);
            } catch (Exception $e) {
                
            }
        }
    
        $results = array();
        $plugin_ids=array();
        foreach ($plugins as $file=>$plugin){
            if(in_array($plugin->id, $plugin_ids)){
                continue;
            }
            
            $results[$file]=$plugin;
        }
        
        return $results;
    }
}