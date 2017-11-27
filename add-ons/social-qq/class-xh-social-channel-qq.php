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
        $this->description=__('在QQ互联<a href="http://connect.qq.com" target="_blank">http://connect.qq.com</a>注册并创建应用。',XH_SOCIAL);
        $this->init_form_fields(); 
        
        $this->supports=array('login','share');
        $this->enabled ='yes'== $this->get_option('enabled');
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
                'label' => __ ( 'Enable QQ connect', XH_SOCIAL ),
                'default' => 'yes'
            ),
            'appid'=>array(
                'title' => __ ( 'App ID', XH_SOCIAL ),
                'type' => 'textbox',
                'description'=>sprintf(__('QQ互联中，请设置网站回调域为:%s',XH_SOCIAL),admin_url('admin-ajax.php'))
            ),
            'appsecret'=>array(
                'title' => __ ( 'APP Key', XH_SOCIAL ),
                'type' => 'textbox'
            )
        );
        
        $fields['subtitle2']=array(
            'title'=>__('Cross-domain Settings',XH_SOCIAL),
            'type'=>'subtitle',
            'description'=>__('多网站共用一个QQ互联平台应用进行网页授权<span style="color:red;">(需要时才开启)</span>',XH_SOCIAL)
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
            'placeholder'=>'http://other-domain.com/cross-domain-qq.php',
            'description'=>__('如果：你需要用自己的公众号（qq，微博）作为代理，请购买：<a href="https://www.wpweixin.net/product/1211.html" target="_blank">跨域扩展</a>',XH_SOCIAL)
        );
           
        $this->form_fields=apply_filters('xh_social_channel_qq_form_fields', $fields,$this);
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
        
        $ext_user_info['wp_user_id']=$wp_user_id;
        
        do_action('xh_social_channel_update_wp_user_info',$ext_user_info);
        do_action('xh_social_channel_qq_update_wp_user_info',$ext_user_info);
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['user_img']);
        return $this->get_wp_user_info($ext_user_id);
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
            from {$wpdb->prefix}xh_social_channel_qq w
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
            from {$wpdb->prefix}xh_social_channel_qq w
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
                'user-img'=>$user->img,
                'user_img'=>$user->img,
                'user_login'=>null,
                'user_email'=>null,
                'nicename'=>$guid,
                'uid'=>$user->openid
        );
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_share_link()
     */
    public function get_share_link(){
        return array(
            'link'=>"http://connect.qq.com/widget/shareqq/index.html?url={url}&title={title}&summary={summary}&pics={img}",
            'width'=>770,
            'height'=>580
        );
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
        return  array(
                'nickname'=>$user->nickname,
                'user_login'=>null,
                'user_email'=>null,
                'user_img'=>$user->img,
                'wp_user_id'=>$user->user_id,
                'ext_user_id'=>$user->id,
                'nicename'=>$guid,
                'uid'=>$user->openid
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
            $code =XH_Social_Helper_String::sanitize_key_ignorecase($_GET['code']);
             
            try {
                //获取accesstoken
                $appid = $this->get_option("appid");
                $appsecret = $this->get_option("appsecret");
                $params=array();
                $redirect_uri = XH_Social_Helper_Uri::get_uri_without_params(XH_Social_Helper_Uri::get_location_uri(),$params);
                if(isset($params['code'])) unset($params['code']);
                if(isset($params['state'])) unset($params['state']);
                $redirect_uri.="?".http_build_query($params);
            
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
                
                $img =null;
                if(isset($obj['figureurl_qq_2'])&&!empty($obj['figureurl_qq_2'])){
                    $img =$obj['figureurl_qq_2'];
                }else if(isset($obj['figureurl_qq_1'])&&!empty($obj['figureurl_qq_1'])){
                    $img =$obj['figureurl_qq_1'];
                }else{
                    $img =$obj['figureurl_2'];
                }
                $userdata=array(
                    'openid'=>$openid,
                    'nickname'=>XH_Social_Helper_String::remove_emoji($obj['nickname']),
                    'gender'=>$obj['gender'],
                    'province'=>$obj['province'],
                    'city'=>$obj['city'],
                    'img'=>str_replace('http://', '//', $img),
                    'last_update'=>date_i18n('Y-m-d H:i')
                );
            } catch (Exception $e) {
                XH_Social_Log::error($e);
                $err_times = isset($_GET['err_times'])?intval($_GET['err_times']):3;
                 
                if($err_times>0){
                    $err_times--;
                    return $this->_login_get_authorization_uri($wp_user_id, $err_times);
                }
                
                XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
                return $login_location_uri;
            }
        }
        
        if(!$userdata||empty($userdata)){
           return $login_location_uri;
        }
        
        //获取到用户信息，存储且跳转
        global $wpdb;
        $ext_user_id = 0;
        $wpdb->last_error='';
        
        try {
            $ext_user_info = $wpdb->get_row(
            $wpdb->prepare(
               "select id,
                        user_id
                from {$wpdb->prefix}xh_social_channel_qq
                where openid=%s
                limit 1;", $userdata['openid']));
        
            if($wp_user_id
                &&$wp_user_id>0
                &&$ext_user_info
                &&$ext_user_info->user_id
                &&$ext_user_info->user_id!=$wp_user_id){
                    $wp_user = get_userdata($ext_user_info->user_id);
                    if($wp_user){
                        throw new Exception(sprintf(__("对不起，您的QQ已与账户(%s)绑定，请解绑后重试！",XH_SOCIAL),$wp_user->nickname));
                    }
            }
            
            if($wp_user_id>0
                &&(!$ext_user_info||$ext_user_info->user_id<>$wp_user_id)){
                
                $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_qq where user_id=$wp_user_id ;");
                if(!empty($wpdb->last_error)){
                    XH_Social_Log::error($wpdb->last_error);
                    throw new Exception(__($wpdb->last_error,XH_SOCIAL));
                }
            }
            
            if(!$ext_user_info){
                if($wp_user_id>0){
                    $userdata['user_id']=$wp_user_id;
                }
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
                //user_id
                if($wp_user_id>0){
                    $userdata['user_id'] =$wp_user_id;
                }
                
                $wpdb->update("{$wpdb->prefix}xh_social_channel_qq", $userdata,
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
    
    public function get_wp_user($field,$field_val){
        if(!in_array($field, array(
            'openid'
        ))){
            return null;
        }
    
        global $wpdb;
        $ext_user_info =$wpdb->get_row($wpdb->prepare(
            "select user_id
            from {$wpdb->prefix}xh_social_channel_qq
            where $field=%s
            limit 1;", $field_val));
        if($ext_user_info&&$ext_user_info->user_id){
            return get_userdata($ext_user_info->user_id);
        }
    
        return null;
    }
    
    public function get_ext_user($field,$field_val){
        if(!in_array($field, array(
            'openid'
        ))){
            return null;
        }
    
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "select *
            from {$wpdb->prefix}xh_social_channel_qq
            where $field=%s
            limit 1;", $field_val));
    }

    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::generate_authorization_uri()
     */
    public function generate_authorization_uri($user_ID=0,$login_location_uri=null){
       return $this->_login_get_authorization_uri(is_null($user_ID)?0:$user_ID,0);
    }
    
    /**
     * 获取登录授权链接
     * @param string $login_location_uri
     * @param int $error_times
     * @return string
     * @since 1.0.0
     */
    private function _login_get_authorization_uri($user_ID=0,$error_times=null){
        $params=array();
        $api = XH_Social_Add_On_Social_QQ::instance();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(
            array(
                'tab'=>'authorization',
                'action'=>"xh_social_{$api->id}",
                'uid'=>is_null($user_ID)?0:$user_ID
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
}
require_once XH_SOCIAL_DIR.'/includes/abstracts/abstract-xh-schema.php';
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
            UNIQUE INDEX `user_id_unique` (`user_id`)
        )
        $collate;");
    
        if(!empty($wpdb->last_error)){
            XH_Social_Log::error($wpdb->last_error);
            throw new Exception($wpdb->last_error);
        }
    }
}