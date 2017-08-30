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
     * 判断当前用户是否允许操作
     * @param array $roles
     * @since 1.0.0
     */
    public function capability($roles=array('administrator')){
        global $current_user;
        if(!is_user_logged_in()){
        }
         
        if(!$current_user->roles||!is_array($current_user->roles)){
            $current_user->roles=array();
        }
         
        foreach ($roles as $role){
            if(in_array($role, $current_user->roles)){
                return true;
            }
        }
        return false;
    }
    
    /**
     * 根据昵称，创建user_login
     * @param string $nickname
     * @return string
     * @since 1.0.1
     */
    public function generate_user_login($nickname){
        $nickname = XH_Social_Helper_String::remove_emoji($nickname);
        if(empty($nickname)){
            $nickname = mb_substr(str_shuffle("abcdefghigklmnopqrstuvwxyz123456") ,0,4,'utf-8');
        }
        
        if(mb_strlen($nickname)>32){
            $nickname = mb_substr($nickname, 0,32,'utf-8');
        }
        
        $pre_nickname =$nickname;
    
        $index=0;
        while (username_exists($nickname)){
            $index++;
            if($index==1){
                $nickname=$pre_nickname.'_'.time();//年+一年中的第N天
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

    public function get_plugin_settings_url(){
        return admin_url('admin.php?page=social_page_add_ons');
    }
    
    /**
     * @since 1.0.9
     * @param array $request
     * @param bool $validate_notice
     * @return bool
     */
    public function ajax_validate(array $request,$hash,$validate_notice = true){
        if(XH_Social_Helper::generate_hash($request, XH_Social::instance()->get_hash_key())!=$hash){
            return false;
        }
       
        return true;
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
    
    /**
     * @since 1.0.7
     * @param string $log_on_callback_uri
     * @return string
     */
    public function wp_loggout_html($log_on_callback_uri=null,$include_css=false,$include_header_footer=false,$include_html=false){
        XH_Social_Temp_Helper::set('atts', array(
            'log_on_callback_uri'=>$log_on_callback_uri,
            'include_css'=>$include_css,
            'include_header_footer'=>$include_header_footer,
            'include_html'=>$include_html
        ),'templete');
        
        ob_start();
        require XH_Social::instance()->WP->get_template(XH_SOCIAL_DIR, 'account/logout-content.php');
     
        return ob_get_clean();
    }
    
    /**
     * wp die
     * @param Exception|XH_Social_Error|WP_Error|string|object $err
     * @since 1.0.0
     */
    public function wp_die($err=null,$include_header_footer=true,$exit=true){
        XH_Social_Temp_Helper::set('atts', array(
            'err'=>$err,
            'include_header_footer'=>$include_header_footer
        ),'templete');
        
        ob_start();
        require XH_Social::instance()->WP->get_template(XH_SOCIAL_DIR, 'wp-die.php');
        echo ob_get_clean();
        if($exit){
        exit;
        }
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
            'type'=>function($form_id,$data_name,$settings){
                    $form_name = $data_name;
                    $name = $form_id."_".$data_name;
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
        				            url: '<?php echo XH_Social::instance()->ajax_url('xh_social_captcha',true,true)?>',
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
            			})(jQuery);
                    </script>
                <?php 
                XH_Social_Helper_Html_Form::generate_field_scripts($form_id, $data_name);
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
                
                if(strcasecmp($captcha, $code_post)!==0){
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
        $base_dirs = XH_Social::instance()->plugins_dir;
        
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

    /**
     *
     * @param string $page_templete_dir
     * @param string $page_templete
     * @return string
     * @since 1.0.8
     */
    public function get_template($page_templete_dir,$page_templete){
        if(strpos($page_templete, 'social/')===0){
            $ltemplete=substr($page_templete, 7);
        }
        
        if(file_exists(STYLESHEETPATH.'/social/'.$page_templete)){
            return STYLESHEETPATH.'/social/'.$page_templete;
        }
        
        if(file_exists(STYLESHEETPATH.'/wechat-social-login/'.$page_templete)){
            return STYLESHEETPATH.'/wechat-social-login/'.$page_templete;
        }
    
        return $page_templete_dir.'/templates/'.$page_templete;
    }
}