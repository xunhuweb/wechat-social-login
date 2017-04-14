<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 微信接口
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Channel_Wechat extends Abstract_XH_Social_Settings_Channel{    
    /**
     * Instance
     * @var XH_Social_Channel_Wechat
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @since 1.0.0
     * @static
     * @return XH_Social_Channel_Wechat
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
        $this->id='social_wechat';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/weixin-icon.png';
        $this->title =__('Wechat', XH_SOCIAL);
        $this->enabled = 'yes'==$this->get_option('enabled');
       
        $this->description="必须在开放平台里绑定公众号";
        
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
       $fields =array(
            'enabled' => array (
                'title' => __ ( 'Enable/Disable', XH_SOCIAL ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable wechat login', XH_SOCIAL ),
                'default' => 'no'
            ),
            //=====================
            'fieldset1'=>array(
                'title' => __ ( 'WeChat public platform', XH_SOCIAL ),
                'type' => 'subtitle',
                'description'=>__ ( 'Login in WeChat built-in browser,<a href="http://mp.weixin.qq.com" target="_blank">View details</a>', XH_SOCIAL )
            ),
            'mp_id'=>array(
                'title' => __ ( 'AppID', XH_SOCIAL ),
                'type' => 'textbox'
            ),
            'mp_secret'=>array(
                'title' => __ ( 'AppSecret', XH_SOCIAL ),
                'type' => 'textbox'
            ),
    
            //===========================
            'fieldset2'=>array(
                'title' => __ ( 'WeChat open platform', XH_SOCIAL ),
                'type' => 'subtitle',
                'description'=>__ ( 'In the computer side (via WeChat app scan code) to login,<a href="http://open.weixin.qq.com" target="_blank">View details</a>', XH_SOCIAL )
            ),
            'op_id'=>array(
                'title' => __ ( 'AppID', XH_SOCIAL ),
                'type' => 'textbox'
            ),
            'op_secret'=>array(
                'title' => __ ( 'AppSecret', XH_SOCIAL ),
                'type' => 'textbox'
            )
        );
       
       $this->form_fields= apply_filters('xh_social_channel_wechat_form_fields',  $fields);
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
      
            $wp_user_id =wp_insert_user($userdata);
            if(is_wp_error($wp_user_id)){
                return XH_Social_Error::wp_error($wp_user_id);
            }
        }
        
        if($wp_user_id!=$ext_user_info['wp_user_id']){
            //若当前用户已绑定过其他微信号？那么解绑
            $wpdb->query(
            "delete from  {$wpdb->prefix}xh_social_channel_wechat
             where user_id=$wp_user_id and id<>$ext_user_id; ");
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
            
            $result =$wpdb->query(
                         "update {$wpdb->prefix}xh_social_channel_wechat
                          set user_id=$wp_user_id
                          where id=$ext_user_id;");
            if(!$result||!empty($wpdb->last_error)){
                XH_Social_Log::error("update xh_social_channel_wechat failed.detail error:".$wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
        }
        
        $ext_user_info['wp_user_id']=$wp_user_id;
        
        do_action('xh_social_channel_wechat_update_wp_user_info',$ext_user_info);
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['user_img']);
        //兼容其他插件
        if(isset($ext_user_info['mp_openid'])&& !empty($ext_user_info['mp_openid'])){
            update_user_meta($wp_user_id, 'openid', $ext_user_info['mp_openid']);
        }
        
        return $this->get_wp_user_info($ext_user_id);
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_wp_user_info($ext_user_id)
     */
    public function get_wp_user_info($ext_user_id){
        $userinfo =XH_Social_Temp_Helper::get('wp_user_info', 'login:wechat');
        if($userinfo){
            return $userinfo;
        }
        
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
           "select w.user_id,
                   w.mp_openid
            from {$wpdb->prefix}xh_social_channel_wechat w
            where w.id=$ext_user_id
            limit 1;");
        if(!$user) {
            return null;
        }
        
        if($user->user_id){
            $userinfo= get_userdata($user->user_id);
            if($userinfo){
                XH_Social_Temp_Helper::set('wp_user_info', $userinfo,'login:wechat');
                return $userinfo;
            }
        }
     
        return null;
    }
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info_by_wp($wp_user_id)
     */
    public function get_ext_user_info_by_wp($wp_user_id){
        $userinfo =XH_Social_Temp_Helper::get('ext_user_info_by_wp', 'login:wechat');
        if($userinfo){
            return $userinfo;
        }
    
        $wp_user_id = intval($wp_user_id);
        
        global $wpdb;
        $user = $wpdb->get_row(
            "select w.*
            from {$wpdb->prefix}xh_social_channel_wechat w
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
                'user_img'=>$user->img,
                'user_login'=>null,
                'user_email'=>null,
                'uid'=>$user->unionid,
                'nicename'=>$guid,
                'mp_openid'=>$user->mp_openid,
                'op_openid'=>$user->op_openid,
        );
        
        XH_Social_Temp_Helper::set('ext_user_info_by_wp',$userinfo, 'login:wechat');
        return $userinfo;
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::remove_ext_user_info_by_wp($wp_user_id)
     */
    public function remove_ext_user_info_by_wp($wp_user_id){
        global $wpdb;
        $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_wechat where user_id={$wp_user_id};");
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
        $userinfo =XH_Social_Temp_Helper::get('ext_user_info', 'login:wechat');
        if($userinfo){
            return $userinfo;
        }
        
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
                    "select w.*
                     from {$wpdb->prefix}xh_social_channel_wechat w
                     where w.id=$ext_user_id
                     limit 1;");
        if(!$user) {
            return null;
        }               
        $guid = XH_Social_Helper_String::guid();
        $userinfo= array(
                'nickname'=>$user->nickname,
                'user_login'=>null,
                'user_email'=>null,
                'user_img'=>$user->img,
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'uid'=>$user->unionid,
                'nicename'=>$guid,
                'mp_openid'=>$user->mp_openid,
                'op_openid'=>$user->op_openid,
        );
        
        XH_Social_Temp_Helper::set('ext_user_info',$userinfo, 'login:wechat');
        return $userinfo;
    }
    
    public function get_wp_user($field,$field_val){
        if(!in_array($field, array(
            'mp_openid',
            'op_openid',
            'unionid'
        ))){
            return null;
        }
        
        global $wpdb;
        $ext_user_info =$wpdb->get_row($wpdb->prepare(
                        "select user_id 
                        from {$wpdb->prefix}xh_social_channel_wechat
                        where $field=%s
                        limit 1;", $field_val));
        if($ext_user_info&&$ext_user_info->user_id){
            return get_userdata($ext_user_info->user_id);
        }
        
        return null;
    }
    
    public function get_ext_user($field,$field_val){
        if(!in_array($field, array(
            'mp_openid',
            'op_openid',
            'unionid'
        ))){
            return null;
        }
    
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "select *
            from {$wpdb->prefix}xh_social_channel_wechat
            where $field=%s
            limit 1;", $field_val));
    }
    
    public function process_authorization_callback($uid){
        $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');
        if(empty($login_location_uri)){
            $login_location_uri = home_url('/');
        }
        
        $ext_user_id = $this->_process_authorization_callback($uid,$login_location_uri);
        if(is_array($ext_user_id)){
            return $ext_user_id['redirect'];
        }
        
        return $this->process_login($ext_user_id);
    }
    
    private function _process_authorization_callback($uid,$login_location_uri){  
        if(!isset($_GET['state'])){
           return array(
               'success'=>false,
               'redirect'=>$login_location_uri
           );
        }
        
        if(!isset($_GET['code'])){
            return array(
               'success'=>false,
               'redirect'=>$login_location_uri
           );
        }
        
        $code = XH_Social_Helper_String::sanitize_key_ignorecase($_GET['code']);
        $prefix=XH_Social_Helper_String::sanitize_key_ignorecase($_GET['state']);
        if(!in_array($prefix, array('op','mp'))){
            return array(
               'success'=>false,
               'redirect'=>$login_location_uri
           );
        }
        
        try {
            global $wpdb;
            $wpdb->last_error='';
            
            //获取accesstoken
            $appid = $this->get_option("{$prefix}_id");
            $appsecret = $this->get_option("{$prefix}_secret");
        
            $result = XH_Social_Helper_Http::http_get("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code");
            $response = json_decode($result,true);
            if(!$response){
                throw new Exception(__('Nothing callback when get access token!',XH_SOCIAL),500);
            }
           
            if(isset($response['errcode'])){
                throw new Exception($response['errmsg'],$response['errcode']);
            }
        
            $openid =$response['openid'];
            $access_token = $response['access_token'];
   
            $result = XH_Social_Helper_Http::http_get("https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid");
   
            $response = json_decode($result,true);
            if(!$response){    
                throw new Exception(__('Nothing callback when get user info!',XH_SOCIAL),500);
            }
            
            if(isset($response['errcode'])){
                throw new Exception($response['errmsg'],$response['errcode']);
            }
            
            if(!empty($wpdb->last_error)){
                throw new Exception($wpdb->last_error,500);
            }
           
            return $this->create_ext_user_info($prefix,$response,$uid);
        } catch (Exception $e) {
            XH_Social_Log::error($e);
            if($e->getCode()!=500){
                $err_times = isset($_GET['err_times'])?intval($_GET['err_times']):3;
               
                if($err_times>0){
                    $err_times--;
                    if(!headers_sent()){
                        return array(
                            'success'=>false,
                            'redirect'=>$this-> _login_get_authorization_uri($uid,$err_times)
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
     * 创建扩展用户信息
     * @param string $prefix  mp|op
     * @param array $response userinfo
     * @throws Exception
     * @return number
     */
    public function create_ext_user_info($prefix,$user_data,$uid){
        global $wpdb;
        //same unionid
        $user=null;
        if(!empty($user_data['unionid'])){
            $user = $wpdb->get_row($wpdb->prepare(
                "select w.id,
                        {$prefix}_openid as openid,
                        w.user_id
                from {$wpdb->prefix}xh_social_channel_wechat w
                where w.unionid=%s
                limit 1;", $user_data['unionid']));
            
            if(!empty($wpdb->last_error)){
                throw new Exception($wpdb->last_error,500);
            }
        }
        
        if($user){
            //原有openid不与当前openid一致
            if($user->openid!=$user_data['openid']){
                XH_Social_Log::error(sprintf(__('[Warnning]openid with unionid not compared.(openid:%s,unionid:%s).',XH_SOCIAL),$user_data['openid'],$user_data['unionid']));
                //删除未与union关联的老数据
                $wpdb->query($wpdb->prepare(
                   "delete
                    from {$wpdb->prefix}xh_social_channel_wechat
                    where {$prefix}_openid=%s;", $user_data['openid']));
    
                if(!empty($wpdb->last_error)){
                    throw new Exception($wpdb->last_error,500);
                }
            }
        }
    
        if(!$user){
            $user = $wpdb->get_row($wpdb->prepare(
                "select w.id,
                        {$prefix}_openid as openid,
                        w.user_id
                from {$wpdb->prefix}xh_social_channel_wechat w
                where w.{$prefix}_openid=%s
                limit 1;", $user_data['openid']));
            if(!empty($wpdb->last_error)){
                throw new Exception($wpdb->last_error,500);
            }
            //这之后，不会出现unionid相同的情况
        }
    
        //////////////////
        //不考虑uid被重复情况
        //////////////////
        $update=array(
            "{$prefix}_openid"=>$user_data['openid'],
            'uid'=>$uid,
            'last_update'=>date_i18n('Y-m-d H:i:s')
        );
        
        if(isset($user_data['nickname'])&&!empty($user_data['nickname'])){
            $update['nickname']= sanitize_user(XH_Social_Helper_String::remove_emoji($user_data['nickname']));
        }
        
        if(isset($user_data['sex'])&&!empty($user_data['sex'])){
            $update['sex']=$user_data['sex'];
        }
        
        if(isset($user_data['province'])&&!empty($user_data['province'])){
            $update['province']=$user_data['province'];
        }
        
        if(isset($user_data['city'])&&!empty($user_data['city'])){
            $update['city']=$user_data['city'];
        }
        
        if(isset($user_data['country'])&&!empty($user_data['country'])){
            $update['country']=$user_data['country'];
        }
        
        if(isset($user_data['headimgurl'])&&!empty($user_data['headimgurl'])){
            $update['img']=str_replace('http://', '//', $user_data['headimgurl']);
            if($user->user_id){
                update_user_meta($user->user_id, '_social_img', $update['img']);
            }
        }
        
        if(isset($user_data['unionid'])&&!empty($user_data['unionid'])){
            $update['unionid']=$user_data['unionid'];
        }
        
        if($user){
            //忽略更新openid，unionid：默认认为他们没有变化，且和userid|unionid 是一致的
            $wpdb->update("{$wpdb->prefix}xh_social_channel_wechat",
                $update, array(
                'id'=>$user->id
            ));
    
            if(!empty($wpdb->last_error)){
                throw new Exception($wpdb->last_error,500);
            }
    
            return $user->id;
        }else{
            //插入数据，并且跳转到绑定页面
            $wpdb->insert("{$wpdb->prefix}xh_social_channel_wechat",$update);
    
            if(!empty($wpdb->last_error)){
                //maybe openid or unionid is exists and throw errors.but checked before,so try again login process.
                throw new Exception($wpdb->last_error,500);
            }
    
            //something is wrong and data is not inserted.
            if($wpdb->insert_id<=0){
                throw new Exception(__('unknow error when insert wechat user info.',XH_SOCIAL),500);
            }
    
            return $wpdb->insert_id;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::process_generate_authorization_uri()
     */
    public function process_generate_authorization_uri($login_location_uri){ 
       $uid = XH_Social_Helper_String::guid();
       return $this->_login_get_authorization_uri($uid,null);
    }
    
    /**
     * 获取登录授权链接
     * @param string $login_location_uri
     * @param int $error_times
     * @return string
     * @since 1.0.0
     */
    private function _login_get_authorization_uri($uid,$error_times=null){
        if(XH_Social_Helper_Uri::is_wechat_app()){
           return $this->login_get_wechatclient_authorization_uri($uid,$error_times);
        }else{
           return $this->login_get_pcclient_authorization_uri($uid,$error_times);
        }
    }
    
    public function login_get_pcclient_authorization_uri($uid,$error_times=null){
        $api = XH_Social_Add_On_Social_Wechat::instance();
        
        $params=array();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params);
        $params['tab']='authorization';
        $params['action']="xh_social_{$api->id}";
        $params['notice_str']=str_shuffle(time());
        $params['uid']=$uid;
        $params['hash']=XH_Social_Helper::generate_hash($params, XH_Social::instance()->get_hash_key());
        
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }
    
        $redirect_uri=$url."?".http_build_query($params);
        
        $state="op";
        $uri=null;
        $uri = apply_filters('xh_social_channel_wechat_login_get_authorization_uri', $uri,$redirect_uri,$state,$uid);
        if(!empty($uri)){
            return $uri;
        }
    
        $params=array();
        $params["appid"] = $this->get_option('op_id');
        $params["redirect_uri"] = $redirect_uri;
        $params["response_type"] = "code";
        $params["scope"] = "snsapi_login";
        $params["state"] = $state;
        return "https://open.weixin.qq.com/connect/qrconnect?".http_build_query($params)."#wechat_redirect";
    }
    
    public function login_get_wechatclient_authorization_uri($uid,$error_times=null){
        
        $api = XH_Social_Add_On_Social_Wechat::instance();
     
        $params=array();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params);
        $params['tab']='authorization';
        $params['action']="xh_social_{$api->id}";
        $params['notice_str']=str_shuffle(time());
        $params['uid']=$uid;
        $params['hash']=XH_Social_Helper::generate_hash($params, XH_Social::instance()->get_hash_key());
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }
        
        $redirect_uri= $url."?".http_build_query($params);
        
        $state="mp";
        $uri=null;
        $uri = apply_filters('xh_social_channel_wechat_login_get_authorization_uri', $uri,$redirect_uri,$state,$uid);
        if(!empty($uri)){ 
            return $uri;
        }
     
        $params=array();
        $params["appid"] = $this->get_option('mp_id');
        $params["redirect_uri"] = $redirect_uri;
        $params["response_type"] = "code";
        $params["scope"] = "snsapi_userinfo";
        $params["state"] = $state;
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".http_build_query($params)."#wechat_redirect";
    }
}

/**
* 微信接口
*
* @since 1.0.0
* @author ranj
*/
class XH_Social_Channel_Wechat_Model extends Abstract_XH_Social_Schema{
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Schema::init()
     */
    public function init(){
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}xh_social_channel_wechat` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `user_id` BIGINT(20) NULL,
                        `op_openid` VARCHAR(64) NULL DEFAULT NULL,
                        `mp_openid` VARCHAR(64) NULL DEFAULT NULL,
                        `unionid` VARCHAR(64) NULL DEFAULT NULL,
                        `last_update` DATETIME NOT NULL,
                        `nickname` VARCHAR(512) NULL DEFAULT NULL,
                        `sex` VARCHAR(16) NULL DEFAULT NULL,
                        `province` VARCHAR(64) NULL DEFAULT NULL,
                        `city` VARCHAR(64) NULL DEFAULT NULL,
                        `country` VARCHAR(64) NULL DEFAULT NULL,
                        `img` TEXT NULL,
                        `uid` varchar(64) NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `op_openid_unique` (`op_openid`),
                    UNIQUE INDEX `user_id_unique` (`user_id`),
                    UNIQUE INDEX `mp_openid_unique` (`mp_openid`),
                    UNIQUE INDEX `unionid_unique` (`unionid`),
                    UNIQUE INDEX `uid_unique` (`uid`),
                    INDEX `op_openid_key` (`op_openid`),
                    INDEX `uid_key` (`uid`),
                    INDEX `user_id_key` (`user_id`),
                    INDEX `mp_openid_key` (`mp_openid`),
                    INDEX `unionid_key` (`unionid`)
                    )
                    $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
        
        //创建uid字段
        $this->on_version_101();
    }
    
    /**
     * v1.0.1
     * @throws Exception
     */
    public function on_version_101(){
        $DB_NAME = DB_NAME;
        
        global $wpdb;

        /**
         * @since 1.0.1
         */
        if($wpdb->get_var (
            "SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = '{$wpdb->prefix}xh_social_channel_wechat'
            AND TABLE_SCHEMA='$DB_NAME'
            AND COLUMN_NAME = 'uid'" ) != "{$wpdb->prefix}xh_social_channel_wechat"){
        
            $wpdb->query ( "ALTER TABLE `{$wpdb->prefix}xh_social_channel_wechat` ADD COLUMN `uid` varchar(64) NULL DEFAULT NULL;" );
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                throw new Exception($wpdb->last_error);
            }
        
            if($wpdb->get_var("show index from `{$wpdb->prefix}xh_social_channel_wechat` where Column_name='uid' and Key_name='uid_key'")!= "{$wpdb->prefix}xh_social_channel_wechat"){
                $wpdb->query ( "ALTER TABLE `{$wpdb->prefix}xh_social_channel_wechat` ADD INDEX uid_key(uid);" );
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception($wpdb->last_error);
                }
            }
             
            if($wpdb->get_var("show index from `{$wpdb->prefix}xh_social_channel_wechat` where Column_name='uid' and Key_name='uid_unique'")!= "{$wpdb->prefix}xh_social_channel_wechat"){
                $wpdb->query ( "ALTER TABLE `{$wpdb->prefix}xh_social_channel_wechat` ADD UNIQUE INDEX uid_unique(uid);" );
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception($wpdb->last_error);
                }
            }
        }
    }
}