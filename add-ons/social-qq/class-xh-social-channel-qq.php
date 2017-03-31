<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * 微信接口
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Channel_QQ extends Abstract_XH_Social_Settings_Channel{    
    /**
     * Instance
     * @var XH_Social_Channel_QQ
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * @return XH_Social_Channel_QQ
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
        $this->id='social_qq';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/qq-icon.png';
        $this->title =__('QQ', XH_SOCIAL);
        $this->enabled ='yes'== $this->get_option('enabled');
        $this->description=__('在QQ互联<a href="http://connect.qq.com" target="_blank">http://connect.qq.com</a>注册并创建应用。',XH_SOCIAL);
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
                'label' => __ ( 'Enable QQ connect', XH_SOCIAL ),
                'default' => 'no'
            ),
            'appid'=>array(
                'title' => __ ( 'App ID', XH_SOCIAL ),
                'type' => 'textbox',
                'description'=>sprintf(__('网站回调域:%s',XH_SOCIAL),admin_url('admin-ajax.php'))
            ),
            'appsecret'=>array(
                'title' => __ ( 'APP Key', XH_SOCIAL ),
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
            "delete from  {$wpdb->prefix}xh_social_channel_qq
             where user_id=$wp_user_id and id<>$ext_user_id; ");
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
            
            $result =$wpdb->query(
                         "update {$wpdb->prefix}xh_social_channel_qq
                          set user_id=$wp_user_id
                          where id=$ext_user_id;");
            if(!$result||!empty($wpdb->last_error)){
                XH_Social_Log::error("update xh_social_channel_qq failed.detail error:".$wpdb->last_error);
                return XH_Social_Error::err_code(500);
            }
        }
        
        do_action('xh_social_channel_qq_update_wp_user_info',$ext_user_info);
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['user_img']);
        
        return $this->get_wp_user_info($ext_user_id);
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_wp_user_info($ext_user_id)
     */
    public function get_wp_user_info($ext_user_id){
        $userinfo =XH_Social_Temp_Helper::get('wp_user_info', 'login:qq');
        if($userinfo){
            return $userinfo;
        }
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
           "select w.user_id
            from {$wpdb->prefix}xh_social_channel_qq w
            where w.id=$ext_user_id
            limit 1;");
        if(!$user) {
            return null;
        }
        
        $userinfo= get_userdata($user->user_id);
        if($userinfo){
            XH_Social_Temp_Helper::set('wp_user_info', $userinfo,'login:qq');
        }
        return $userinfo;
    }
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_ext_user_info_by_wp($wp_user_id)
     */
    public function get_ext_user_info_by_wp($wp_user_id){
        $userinfo =XH_Social_Temp_Helper::get('ext_user_info_by_wp', 'login:qq');
        if($userinfo){
            return $userinfo;
        }
    
        $wp_user_id = intval($wp_user_id);
        
        global $wpdb;
        $user = $wpdb->get_row(
            "select w.*
            from {$wpdb->prefix}xh_social_channel_qq w
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
                'user-img'=>$user->img,
                'user_login'=>null,
                'user_email'=>null,
                'nicename'=>$guid,
                'uid'=>$user->openid
        );
        
        XH_Social_Temp_Helper::set('ext_user_info_by_wp',$userinfo, 'login:qq');
        return $userinfo;
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::remove_ext_user_info_by_wp($wp_user_id)
     */
    public function remove_ext_user_info_by_wp($wp_user_id){
        global $wpdb;
        $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_qq where user_id={$wp_user_id};");
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
        $userinfo =XH_Social_Temp_Helper::get('ext_user_info', 'login:qq');
        if($userinfo){
            return $userinfo;
        }
        
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
                    "select w.*
                     from {$wpdb->prefix}xh_social_channel_qq w
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
                'nicename'=>$guid,
                'uid'=>$user->openid
        );
        
        XH_Social_Temp_Helper::set('ext_user_info',$userinfo, 'login:qq');
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
        $code =XH_Social_Helper_String::sanitize_key_ignorecase($_GET['code']);
       
        try {
            global $wpdb;
            $wpdb->last_error='';
            
            //获取accesstoken
            $appid = $this->get_option("appid");
            $appsecret = $this->get_option("appsecret");
            $redirect_uri=XH_Social::instance()->session->get('social_login_qq_redirect_uri');
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
           
            $response = XH_Social_Helper_Http::http_get('https://graph.qq.com/oauth2.0/token?'.http_build_query($params));
            if(!$response){
                throw new Exception(__('Nothing callback when get user info!',XH_SOCIAL),500);
            }
            
            if(strpos($response, "callback")!==false){
                throw new Exception($response,500); 
            }
            //access_token=0030C012FB1355A1976846C544AE5DF9&expires_in=7776000&refresh_token=1985788C72B58754385737A919CD0E50
            $access_token='';
            $results =explode('&', $response);
            foreach ($results as $param){
                $params = explode('=', $param);
                if(count($params)!==2){
                    continue;
                }
                
                if($params[0]==='access_token'){
                    $access_token=$params[1];
                    break;
                }
            }
           
            if(empty($access_token)){
                throw new Exception("error callback,details:$response",500);
            }
            
            $response = XH_Social_Helper_Http::http_get("https://graph.qq.com/oauth2.0/me?access_token={$access_token}");
         //callback( {"client_id":"101321683","openid":"A34E43E9A2E01B1BE9147A5C1B033BF7"} );
            $openid='';
            if(strpos($response, "callback")!==false){
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                $msg = json_decode($response);
            
                if(isset($msg->error)){
                    throw new Exception("error:{$msg->error},desc:{$msg->error_description}",500);
                }
                
                if(isset($msg->openid)){
                    $openid=$msg->openid;
                }
            }
           
            if(empty($openid)){
                throw new Exception("get openid error callback,details:$response",500);
            }
            
            $response = XH_Social_Helper_Http::http_get("https://graph.qq.com/user/get_user_info?access_token={$access_token}&oauth_consumer_key={$appid}&openid={$openid}");
            if(strpos($response, "callback")!==false){
                throw new Exception($response,500);
            }
            
            $obj = json_decode($response,true);
           
            $userdata=array(
                    'openid'=>$openid,
                    'nickname'=>sanitize_user(XH_Social_Helper_String::remove_emoji($obj['nickname'])),
                    'gender'=>$obj['gender'],
                    'province'=>$obj['province'],
                    'city'=>$obj['city'],
                    'img'=>str_replace('http://', '//', $obj['figureurl_2']),
                    'last_update'=>date_i18n('Y-m-d H:i')
            );
            
            global $wpdb;
            $ext_user_id = 0;
            
            $ext_user_info = $wpdb->get_row(
                $wpdb->prepare(
                    "select id,
                            user_id
                    from {$wpdb->prefix}xh_social_channel_qq
                    where openid=%s
                    limit 1;", $userdata['openid']));
            
            if(!$ext_user_info){
                $wpdb->insert("{$wpdb->prefix}xh_social_channel_qq", $userdata);
                if(!empty($wpdb->last_error)){
                    throw new Exception($wpdb->last_error);
                }
        
                if($wpdb->insert_id<=0){
                    XH_Social_Log::error('insert qq user info failed');
                    throw new Exception('insert qq user info failed');
                }
        
                $ext_user_id=$wpdb->insert_id;
            } else{
                $wpdb->update("{$wpdb->prefix}xh_social_channel_qq", $userdata,
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
                        wp_redirect($this-> _login_get_authorization_uri($err_times));
                    }
                }
            }
        }
        
        return null;
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
        $api = XH_Social_Add_On_Social_QQ::instance();
        
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
        
        XH_Social::instance()->session->set('social_login_qq_redirect_uri', $redirect_uri);
       
        $params=array(
            'response_type'=>'code',
            'client_id'=>$this->get_option('appid'),
            'scope'=>'get_user_info',
            'redirect_uri'=>$redirect_uri,
            'state'=>str_shuffle(time())
        );
        
        return 'https://graph.qq.com/oauth2.0/authorize?'.http_build_query($params);
    }
}

/**
* 微信接口
*
* @since 1.0.0
* @author ranj
*/
class XH_Social_Channel_QQ_Model extends Abstract_XH_Social_Schema{

    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Schema::init()
     */
    public function init(){
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}xh_social_channel_qq` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT(20) NULL,
            `openid` VARCHAR(64) NULL DEFAULT NULL,
            
            `last_update` DATETIME NOT NULL,
            `nickname` VARCHAR(512) NULL DEFAULT NULL,
            `gender` VARCHAR(16) NULL DEFAULT NULL,
            `province` VARCHAR(64) NULL DEFAULT NULL,
            `city` VARCHAR(64) NULL DEFAULT NULL,
            `img` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `openid_unique` (`openid`),
            UNIQUE INDEX `user_id_unique` (`user_id`),
            INDEX `openid_key` (`openid`),
            INDEX `user_id_key` (`user_id`)
        )
        $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}