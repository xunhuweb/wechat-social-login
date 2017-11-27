<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 微信接口
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Channel_Weibo extends Abstract_XH_Social_Settings_Channel{    
    /**
     * Instance
     * @var XH_Social_Channel_Weibo
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @return XH_Social_Channel_Weibo
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }
    
    /**
     * 初始化接口ID，标题等信息
     * 
     * @since 1.0.0
     */
    protected function __construct(){
        $this->id='social_weibo';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/weibo-icon.png';
        $this->title =__('Weibo', XH_SOCIAL);
        
        $this->description=__('在微博开放平台（<a href="http://open.weibo.com" target="_blank">http://open.weibo.com</a>）注册并创建应用。',XH_SOCIAL);
        $this->init_form_fields();
        
        $this->supports=array('login','share');
        $this->enabled = 'yes'==$this->get_option('enabled');
    }
  
    
    /**
     * 初始化设置项
     *
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings::init_form_fields()
     * @since 1.0.0
     */
    public function init_form_fields(){
        $fields =array(
            'enabled' => array (
                'title' => __ ( 'Enable/Disable', XH_SOCIAL ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable weibo login', XH_SOCIAL ),
                'default' => 'yes'
            ),
            'appid'=>array(
                'title' => __ ( 'App ID', XH_SOCIAL ),
                'type' => 'textbox'
            ),
            'appsecret'=>array(
                'title' => __ ( 'App Secret', XH_SOCIAL ),
                'type' => 'textbox'
            )
        );
        

        $fields['subtitle2']=array(
            'title'=>__('Cross-domain Settings',XH_SOCIAL),
            'type'=>'subtitle',
            'description'=>__('多网站共用一个微博开放平台应用进行网页授权<span style="color:red;">(需要时才开启)</span>',XH_SOCIAL)
        );
        
        $fields['enabled_cross_domain']=array(
            'title'=>__('Cross-domain',XH_SOCIAL),
            'type'=>'section',
            'options'=>array(
                'cross_domain_disabled'=>__('Disabled',XH_SOCIAL),
                'cross_domain_enabled'=>__('Enabled',XH_SOCIAL)
            )
        );
        
        $fields['cross_domain_url']=array(
            'tr_css'=>'section-enabled_cross_domain section-cross_domain_enabled',
            'title'=>__('Cross-domain Url',XH_SOCIAL),
            'type'=>'text',
            'placeholder'=>'http://other-domain.com/cross-domain-weibo.php',
            'description'=>__('如果：你需要用自己的公众号（qq，微博）作为代理，请购买：<a href="https://www.wpweixin.net/product/1211.html" target="_blank">跨域扩展</a>',XH_SOCIAL)
        );
         
        $this->form_fields=apply_filters('xh_social_channel_weibo_form_fields', $fields,$this);
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_share_link()
     */
    public function get_share_link(){
        return array(
            'link'=>"http://v.t.sina.com.cn/share/share.php?url={url}&title={title}&pic={img}&appkey=&ralateUid=&language=zh_cn&searchPic=true",
            'width'=>600,
            'height'=>350
        );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::update_wp_user_info($ext_user_id,$wp_user_id=null)
     */
    public function update_wp_user_info($ext_user_id,$wp_user_id=null){
        $ext_user_info = $this->get_ext_user_info($ext_user_id);
        if(!$ext_user_info){
            return XH_Social_Error::error_unknow();
        }
        
        global $wpdb;
        if(!$wp_user_id){
            $user_login = XH_Social::instance()->WP->generate_user_login($ext_user_info['nickname']);
            $userdata=array(
                'user_login'=>$user_login,
                'user_nicename'=>$ext_user_info['nicename'],
                'first_name'=>$ext_user_info['nickname'],
                'user_email'=>null,
                'display_name'=>$ext_user_info['nickname'],
                'nickname'=>$ext_user_info['nickname'],
                'user_pass'=>str_shuffle(time())
            );
            
            $wp_user_id = $this->wp_insert_user_Info($ext_user_id, $userdata);
            if($wp_user_id instanceof XH_Social_Error){
                return $wp_user_id;
            }
        }
        
        if($wp_user_id!=$ext_user_info['wp_user_id']){
            //若当前用户已绑定过其他微信号？那么解绑
            $wpdb->query(
            "delete from {$wpdb->prefix}xh_social_channel_weibo
             where user_id=$wp_user_id and id<>$ext_user_id; ");
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
            
            $result =$wpdb->query(
                         "update {$wpdb->prefix}xh_social_channel_weibo
                          set user_id=$wp_user_id
                          where id=$ext_user_id;");
            if(!$result||!empty($wpdb->last_error)){
                XH_Social_Log::error("update xh_social_channel_weibo failed.detail error:".$wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
        }
        
        $ext_user_info['wp_user_id']=$wp_user_id;
        
        do_action('xh_social_channel_update_wp_user_info',$ext_user_info);
        do_action('xh_social_channel_weibo_update_wp_user_info',$ext_user_info);
        
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['user_img']);
        
        return $this->get_wp_user_info($ext_user_id);
    }
    public function get_wp_user($field,$field_val){
        if(!in_array($field, array(
            'uid'
        ))){
            return null;
        }
    
        global $wpdb;
        $ext_user_info =$wpdb->get_row($wpdb->prepare(
            "select user_id
            from {$wpdb->prefix}xh_social_channel_weibo
            where $field=%s
            limit 1;", $field_val));
        if($ext_user_info&&$ext_user_info->user_id){
            return get_userdata($ext_user_info->user_id);
        }
    
        return null;
    }
    
    public function get_ext_user($field,$field_val){
        if(!in_array($field, array(
            'uid'
        ))){
            return null;
        }
    
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "select *
            from {$wpdb->prefix}xh_social_channel_weibo
            where $field=%s
            limit 1;", $field_val));
    }
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_wp_user_info($ext_user_id)
     */
    public function get_wp_user_info($ext_user_id){
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
           "select w.user_id
            from {$wpdb->prefix}xh_social_channel_weibo w
            where w.id=$ext_user_id
            limit 1;");
        if(!$user||!$user->user_id) {
            return null;
        }
        
        return get_userdata($user->user_id);
    }
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info_by_wp($wp_user_id)
     */
    public function get_ext_user_info_by_wp($wp_user_id){
        $wp_user_id = intval($wp_user_id);
        
        global $wpdb;
        $user = $wpdb->get_row(
            "select w.*
            from {$wpdb->prefix}xh_social_channel_weibo w
            where w.user_id=$wp_user_id
            limit 1;");
        
        if(!$user) {
            return null;
        }
        $guid = XH_Social_Helper_String::guid();
        return array(
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'nickname'=>$user->nickname,
                'nicename'=>$guid,
                'user_img'=>$user->img,
                'user_login'=>null,
                'user_email'=>null,
                'uid'=>$user->uid
        );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::remove_ext_user_info_by_wp($wp_user_id)
     */
    public function remove_ext_user_info_by_wp($wp_user_id){
        global $wpdb;
        $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_weibo where user_id={$wp_user_id};");
        if(!empty($wpdb->last_error)){
            return XH_Social_Error::error_custom($wpdb->last_error);
        }
        
        return XH_Social_Error::success();
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info($ext_user_id)
     */
    public function get_ext_user_info($ext_user_id){
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
                    "select w.*
                     from {$wpdb->prefix}xh_social_channel_weibo w
                     where w.id=$ext_user_id
                     limit 1;");
        if(!$user) {
            return null;
        }               
        $guid=XH_Social_Helper_String::guid();
        return array(
                'nickname'=>$user->nickname,
                'nicename'=>$guid,
                'user_login'=>null,
                'user_email'=>null,
                'user_img'=>$user->img,
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'uid'=>$user->uid
        );
    }
    
    public function process_authorization_callback($wp_user_id){
        $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');
        if(empty($login_location_uri)){
            $login_location_uri = home_url('/');
        }
        
        $userdata=array();
        
        if(isset($_POST['user_hash'])){
            $userdata = isset($_POST['userdata'])? base64_decode($_POST['userdata']):null;
            $user_hash = $_POST['user_hash'];
            $userdata =$userdata?json_decode($userdata,true):null;
            if(!$userdata){
               return $login_location_uri;
            }
        
            $ohash =XH_Social_Helper::generate_hash($userdata, $this->get_option('appsecret'));
            if($user_hash!=$ohash){
                XH_Social_Log::error(__('Please check cross-domain app secret config(equal to current website app secret)!',XH_SOCIAL));
                return $login_location_uri;
            }
        
            $userdata['last_update']=date_i18n('Y-m-d H:i');
        }else{
            if(!isset($_GET['code'])){ 
               return $login_location_uri;
            }
            
            $code = XH_Social_Helper_String::sanitize_key_ignorecase($_GET['code']);
            
            $params=array();
            $redirect_uri = XH_Social_Helper_Uri::get_uri_without_params(XH_Social_Helper_Uri::get_location_uri(),$params);
            if(isset($params['code'])) unset($params['code']);
            if(isset($params['state'])) unset($params['state']);
            $redirect_uri.="?".http_build_query($params);
            
            try {
                if(!class_exists('SaeTOAuthV2')){
                    require_once 'saetv2.ex.class.php';
                }
                
                $appid =$this->get_option('appid');
                $appkey =$this->get_option('appsecret');
                
                $api = new SaeTOAuthV2($appid,$appkey);
                $token = $api->getAccessToken( 'code', array(
                    'code'=>$code,
                    'redirect_uri'=>$redirect_uri
                )) ;
                
                if(!$token||isset($token['error'])){
                    throw new Exception(isset($token['error'])?$token['error']:XH_Social_Error::err_code(500),isset($token['error_code'])?$token['error_code']:0);
                }
                
                $uapi = new SaeTClientV2( $appid , $appkey ,$token['access_token']);
                $uid_get = $uapi->get_uid();
                if(!$uid_get||isset($uid_get['error'])){
                    throw new Exception(isset($uid_get['error'])?$uid_get['error']:XH_Social_Error::err_code(500),isset($uid_get['error_code'])?$uid_get['error_code']:0);
                }
                
                $uid = $uid_get['uid'];
                $obj = $uapi->show_user_by_id( $uid);
                if(!$obj||isset($obj['error'])){
                    throw new Exception(isset($obj['error'])?$obj['error']:XH_Social_Error::err_code(500),isset($obj['error_code'])?$obj['error_code']:0);
                }
                
                $userdata = array(
                    'uid'=>$uid,
                    'nickname'=>XH_Social_Helper_String::remove_emoji($obj['name']),
                    'location'=>$obj['location'],
                    'description'=>XH_Social_Helper_String::remove_emoji($obj['description']),
                    'city'=>$obj['city'],
                    'province'=>$obj['province'],
                    'description'=>$obj['description'],
                    'img'=>str_replace('http://', '//', isset($obj['avatar_large'])&&!empty($obj['avatar_large'])?$obj['avatar_large']:(isset($obj['profile_image_url'])?$obj['profile_image_url']:'')),
                    'gender'=>$obj['gender'],
                    'profile_url'=>$obj['profile_url'],
                    'last_update'=>date_i18n('Y-m-d H:i')
                );
            } catch (Exception $e) {
                XH_Social_Log::error($e);
                if($e->getCode()!=500){
                    $err_times = isset($_GET['err_times'])?intval($_GET['err_times']):3;
                    if($err_times>0){
                        $err_times--;
                       return $this-> _login_get_authorization_uri($wp_user_id,$err_times);
                    }
                }
                XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
                return $login_location_uri;
            }
        }
        
        if(!$userdata||empty($userdata)){
             return $login_location_uri;
        }
        
        global $wpdb;
        $ext_user_id = 0;
        $wpdb->last_error='';
        
        try {
            $ext_user_info = $wpdb->get_row(
            $wpdb->prepare(
                "select id,
                        user_id
                from {$wpdb->prefix}xh_social_channel_weibo
                where uid=%s
                limit 1;", $userdata['uid']));
        
            if($wp_user_id
                &&$wp_user_id>0
                &&$ext_user_info
                &&$ext_user_info->user_id
                &&$ext_user_info->user_id!=$wp_user_id){
                    $wp_user = get_userdata($ext_user_info->user_id);
                    if($wp_user){
                        throw new Exception(sprintf(__("对不起，您的微博已与账户(%s)绑定，请解绑后重试！",XH_SOCIAL),$wp_user->nickname));
                    }
            }
            
            if($wp_user_id>0 &&(!$ext_user_info||$ext_user_info->user_id!=$wp_user_id)){
                $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_weibo where user_id=$wp_user_id ;");
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                     throw new Exception(__($wpdb->last_error,XH_SOCIAL));
                }
            }
            
            if(!$ext_user_info){
                if($wp_user_id>0){
                    $userdata['user_id']=$wp_user_id;
                }
                
                $wpdb->insert("{$wpdb->prefix}xh_social_channel_weibo", $userdata);
                if(!empty($wpdb->last_error)){
                    throw new Exception($wpdb->last_error);
                }
        
                if($wpdb->insert_id<=0){
                    XH_Social_Log::error('insert weibo user info failed');
                    throw new Exception('insert weibo user info failed');
                }
        
                $ext_user_id=$wpdb->insert_id;
                
            } else{
                if($wp_user_id>0){
                    $userdata['user_id']=$wp_user_id;
                }
                $wpdb->update("{$wpdb->prefix}xh_social_channel_weibo", $userdata,
                array(
                    'id'=>$ext_user_info->id
                ));
        
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception($wpdb->last_error);
                }
               
                $ext_user_id=$ext_user_info->id;
            }
            
           return $this->process_login($ext_user_id);
        } catch (Exception $e) {
            XH_Social_Log::error($e);
            XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
            return $login_location_uri;
        }
    }
  
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::generate_authorization_uri()
     */
    public function generate_authorization_uri($user_ID=0,$login_location_uri=null){ 
       return $this->_login_get_authorization_uri(is_null($user_ID)?0:$user_ID,null);
    }
    
    /**
     * 获取登录授权链接
     * @param string $login_location_uri
     * @param int $error_times
     * @return string
     * @since 1.0.0
     */
    private function _login_get_authorization_uri($wp_user_id,$error_times=null){
        $api = XH_Social_Add_On_Social_Weibo::instance();
        $params=array();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(
            array(
                'tab'=>'authorization',
                'action'=>"xh_social_{$api->id}",
                'uid'=>$wp_user_id
            ),true,true
            ),$params);
         
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }
        
        $redirect_uri= $url."?".http_build_query($params);
        
        if('cross_domain_enabled'==$this->get_option('enabled_cross_domain')){
            $params = array(
                'callback'=>$redirect_uri
            );
            
            $params['hash'] = XH_Social_Helper::generate_hash($params, $this->get_option('appsecret'));
            $params_uri =array();
            $cross_domain_uri = XH_Social_Helper_Uri::get_uri_without_params($this->get_option('cross_domain_url'),$params_uri);
            
            return $cross_domain_uri."?".http_build_query(array_merge($params_uri,$params));
        }else{
            if(!class_exists('SaeTOAuthV2')){
                require_once 'saetv2.ex.class.php';
            }
            
            $api = new SaeTOAuthV2($this->get_option('appid'),$this->get_option('appsecret'));
            return $api->getAuthorizeURL($redirect_uri);
        }
    }
}
require_once XH_SOCIAL_DIR.'/includes/abstracts/abstract-xh-schema.php';
/**
* 微信接口
*
* @since 1.0.0
* @author ranj
*/
class XH_Social_Channel_Weibo_Model extends Abstract_XH_Social_Schema{
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Schema::init()
     */
    public function init(){
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}xh_social_channel_weibo` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` BIGINT(20) NULL,
                    `uid` VARCHAR(64) NULL DEFAULT NULL,
                    `last_update` DATETIME NOT NULL,
                    `nickname` VARCHAR(512) NULL DEFAULT NULL,
                    `gender` VARCHAR(16) NULL DEFAULT NULL,
                    `description` TEXT NULL DEFAULT NULL,
                    `profile_url` TEXT NULL DEFAULT NULL,
                    `location` TEXT NULL DEFAULT NULL,
                    `province` VARCHAR(64) NULL DEFAULT NULL,
                    `city` VARCHAR(64) NULL DEFAULT NULL,
                    `img` TEXT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `uid_unique` (`uid`),
                    UNIQUE INDEX `user_id_unique` (`user_id`)
                )
                $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}