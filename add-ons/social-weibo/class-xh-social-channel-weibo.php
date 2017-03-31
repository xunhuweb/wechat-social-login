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
        $this->enabled = 'yes'==$this->get_option('enabled');
        $this->description=__('在微博开放平台（<a href="http://open.weibo.com" target="_blank">http://open.weibo.com</a>）注册并创建应用。',XH_SOCIAL);
        $this->init_form_fields();
    }
  
    
    /**
     * 初始化设置项
     *
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings::init_form_fields()
     * @since 1.0.0
     */
    public function init_form_fields(){
        $this->form_fields =array(
            'enabled' => array (
                'title' => __ ( 'Enable/Disable', XH_SOCIAL ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable weibo login', XH_SOCIAL ),
                'default' => 'no'
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
            if(empty($user_login)){
                XH_Social_Log::error("user login created failed,nickname:{$ext_user_info['nickname']}");
                return XH_Social_Error::error_unknow();
            }
            $userdata=array(
                'user_login'=>$user_login,
                'user_nicename'=>$ext_user_info['nicename'],
                'first_name '=>$ext_user_info['nickname'],
                'user_email'=>null,
                'display_name'=>$ext_user_info['nickname'],
                'nickname'=>$ext_user_info['nickname']
            );
            
            $wp_user_id =wp_insert_user($userdata);
            if(is_wp_error($wp_user_id)){
                return XH_Social_Error::wp_error($wp_user_id);
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
        
        do_action('xh_social_channel_weibo_update_wp_user_info',$ext_user_info);
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['user_img']);
        return $this->get_wp_user_info($ext_user_id);
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_wp_user_info($ext_user_id)
     */
    public function get_wp_user_info($ext_user_id){
        $userinfo =XH_Social_Temp_Helper::get('wp_user_info', 'login:weibo');
        if($userinfo){
            return $userinfo;
        }
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
           "select w.user_id
            from {$wpdb->prefix}xh_social_channel_weibo w
            where w.id=$ext_user_id
            limit 1;");
        if(!$user) {
            return null;
        }
        
        $userinfo= get_userdata($user->user_id);
        if($userinfo){
            XH_Social_Temp_Helper::set('wp_user_info', $userinfo,'login:weibo');
        }
        return $userinfo;
    }
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info_by_wp($wp_user_id)
     */
    public function get_ext_user_info_by_wp($wp_user_id){
        $userinfo =XH_Social_Temp_Helper::get('ext_user_info_by_wp', 'login:weibo');
        if($userinfo){
            return $userinfo;
        }
    
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
        $userinfo=array(
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'nickname'=>$user->nickname,
                'nicename'=>$guid,
                'user_img'=>$user->img,
                'user_login'=>null,
                'user_email'=>null,
                'uid'=>$user->uid
        );
        
        XH_Social_Temp_Helper::set('ext_user_info_by_wp',$userinfo, 'login:weibo');
        return $userinfo;
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
        $userinfo =XH_Social_Temp_Helper::get('ext_user_info', 'login:weibo');
        if($userinfo){
            return $userinfo;
        }
        
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
        $userinfo= array(
                'nickname'=>$user->nickname,
            'nicename'=>$guid,
                'user_login'=>null,
                'user_email'=>null,
                'user_img'=>$user->img,
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'uid'=>$user->uid
        );
        
        XH_Social_Temp_Helper::set('ext_user_info',$userinfo, 'login:weibo');
        return $userinfo;
    }
    
    public function process_authorization_callback(){
        $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');
        if(empty($login_location_uri)){
            $login_location_uri = home_url('/');
        }
        
        $ext_user_id = $this->_process_authorization_callback($login_location_uri);
        if(is_array($ext_user_id)){
            return $ext_user_id['redirect'];
        }
        
        return $this->process_login($ext_user_id);
    }
    
    private function _process_authorization_callback($login_location_uri){  
       
        if(!isset($_GET['code'])){ 
            return array(
               'success'=>false,
               'redirect'=>$login_location_uri
           );
        }
        
        $code = XH_Social_Helper_String::sanitize_key_ignorecase($_GET['code']);
       
        try {
            global $wpdb;
            $wpdb->last_error='';
            
            //获取accesstoken
            $appid = $this->get_option("appid");
            $appsecret = $this->get_option("appsecret");
            $redirect_uri = XH_Social::instance()->session->get('social_login_weibo_redirect_uri');
           
            if(empty($redirect_uri)){
                return array(
                    'success'=>false,
                    'redirect'=>$login_location_uri
                );
            }
            
            $params=array(
                'grant_type'=>'authorization_code',
                'code'=>$code,
                'client_id'=>$appid,
                'client_secret'=>$appsecret,
                'redirect_uri'=>$redirect_uri
            );
            $response  = XH_Social_Helper_Http::http_post('https://api.weibo.com/oauth2/access_token',$params);
            $obj = json_decode($response,true);
            if(!$obj){
                throw new Exception(__("Unknow weibo callback data,detail:$response",XH_SOCIAL),500);
            }
           
            //{"error":"HTTP METHOD is not suported for this request!","error_code":10021,"request":"/oauth2/access_token"}
            if(isset($obj['error'])){
                throw new Exception($obj['error'],isset($obj['error_code'])?$obj['error_code']:0);
            }
            
            //{"access_token":"2.00swX4HCHHkC2Dfc20b81d5505TXPp","remind_in":"157679999","expires_in":157679999,"uid":"1941312062"}
            $access_token = isset($obj['access_token'])?$obj['access_token']:'';
            if(empty($access_token)){
                throw new Exception(__("Unknow weibo callback data,detail:$response",XH_SOCIAL),500);
            }
           
            $uid =$obj['uid'];
            $response =  XH_Social_Helper_Http::http_get("https://api.weibo.com/2/users/show.json?access_token=$access_token&uid=$uid");
            $obj = json_decode($response,true);
            if(!$obj){
                throw new Exception(__("Unknow weibo callback data,detail:$response",XH_SOCIAL),500);
            }
           
            //{"error":"HTTP METHOD is not suported for this request!","error_code":10021,"request":"/oauth2/access_token"}
            if(isset($obj['error'])){
                throw new Exception($obj['error'],isset($obj['error_code'])?$obj['error_code']:0);
            }
            
            $userdata = array(
                'uid'=>$uid,
                'nickname'=>sanitize_user(XH_Social_Helper_String::remove_emoji($obj['name'])),
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
            
            global $wpdb;
            $ext_user_id = 0;
            
            $ext_user_info = $wpdb->get_row(
                $wpdb->prepare(
                "select id,
                        user_id
                from {$wpdb->prefix}xh_social_channel_weibo 
                where uid=%s 
                limit 1;", $userdata['uid']));
                
            if(!$ext_user_info){
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
                $wpdb->update("{$wpdb->prefix}xh_social_channel_weibo", $userdata, 
                array(
                    'id'=>$ext_user_info->id
                ));
                
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                   throw new Exception($wpdb->last_error);
                }
                if($ext_user_info->user_id){
                    update_user_meta($ext_user_info->user_id, '_social_img', $userdata['img']);
                }
                $ext_user_id=$ext_user_info->id;
            }  
            
            return $ext_user_id;
            
        } catch (Exception $e) {
            XH_Social_Log::error($e);
            if($e->getCode()!=500){
                $err_times = isset($_GET['err_times'])?intval($_GET['err_times']):3;
                if($err_times>0){
                    $err_times--;
                    if(!headers_sent()){
                        return array(
                            'success'=>false,
                            'redirect'=>$this-> _login_get_authorization_uri($err_times)
                        );
                    }
                }
            }
        }
        
       return array(
           'success'=>false,
           'redirect'=>$login_location_uri
       );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::process_generate_authorization_uri()
     */
    public function process_generate_authorization_uri($login_location_uri){ 
       return $this->_login_get_authorization_uri(null);
    }
    
    /**
     * 获取登录授权链接
     * @param string $login_location_uri
     * @param int $error_times
     * @return string
     * @since 1.0.0
     */
    private function _login_get_authorization_uri($error_times=null){
        $params=array();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params);
        $api = XH_Social_Add_On_Social_Weibo::instance();
        
        $params['tab']='authorization';
        $params['action']="xh_social_{$api->id}";
        $params['notice_str']=str_shuffle(time());
        $params['hash']=XH_Social_Helper::generate_hash($params, XH_Social::instance()->get_hash_key());
        
        $redirect_uri= $url."?".http_build_query($params);
        
        $params = array();
        $redirect_uri= XH_Social_Helper_Uri::get_uri_without_params($redirect_uri,$params);
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }
        $redirect_uri.="?".http_build_query($params);
        unset($params);
        XH_Social::instance()->session->set('social_login_weibo_redirect_uri', $redirect_uri);
        $params=array(
            'response_type'=>'code',
            'client_id'=>$this->get_option('appid'),
            'redirect_uri'=>$redirect_uri,
            'state'=>str_shuffle(time())
        );
        
        return 'https://api.weibo.com/oauth2/authorize?'.http_build_query($params);
    }
}

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
                    UNIQUE INDEX `user_id_unique` (`user_id`),
                    INDEX `uid_key` (`uid`),
                    INDEX `user_id_key` (`user_id`)
                )
                $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}