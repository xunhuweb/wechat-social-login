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
     * @return XH_Social_Channel_Wechat
     */
    protected function __construct(){
        $this->id='social_wechat';
        
        $this->icon =XH_SOCIAL_URL.'/assets/image/weixin-icon.png';
        $this->title =__('Wechat', XH_SOCIAL);

        $this->description="微信内置浏览器：公众平台api登录；移动|PC浏览器：开放平台api登录。";
        
        $this->init_form_fields();
        
        if(!XH_Social_Helper_Uri::is_wechat_app()){
            $this->supports=array('login','share');
        }
        
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
                'label' => __ ( 'Enable wechat login', XH_SOCIAL ),
                'default' => 'yes'
            ),
           'client'=>array(
               'type'=>'tabs',
               'options'=>array(
                   'mp'=>__('微信端',XH_SOCIAL),
                   'op'=>__('移动端+PC端',XH_SOCIAL),
               )
           ),
           
           
            //=====================
            'fieldset1'=>array(
                'title' => __ ( 'WeChat public platform', XH_SOCIAL ),
                'type' => 'subtitle',
                'dividing'=>false,
                'tr_css'=>'tab-client tab-mp',
                'description'=>__ ( '在微信内置浏览器中自动登录，<a href="http://mp.weixin.qq.com" target="_blank">查看详情</a>。(支持移动端+PC端登录,关注公众号，请启用<a href="https://www.wpweixin.net/product/1135.html" target="_blank">微信登录高级扩展</a>)', XH_SOCIAL )
            ),
            'mp_id'=>array(
                'title' => __ ( 'AppID', XH_SOCIAL ),
                'type' => 'textbox',
                 'tr_css'=>'tab-client tab-mp',
            ),
            'mp_secret'=>array(
                'title' => __ ( 'AppSecret', XH_SOCIAL ),
                'type' => 'textbox',
                 'tr_css'=>'tab-client tab-mp',
            ));
       
           $fields['subtitle2']=array(
               'title'=>__('Cross-domain Settings',XH_SOCIAL),
               'type'=>'subtitle',
               'dividing'=>false,
               'tr_css'=>'tab-client tab-mp',
               'description'=>__('多网站共用一个微信服务号进行微信网页授权(OAuth2.0)<span style="color:red;">(需要时才开启)</span>',XH_SOCIAL)
           );
           
           $fields['mp_enabled_cross_domain']=array(
               'title'=>__('Cross-domain',XH_SOCIAL),
               'type'=>'section',
               'tr_css'=>'tab-client tab-mp',
               'options'=>array(
                   'mp_cross_domain_disabled'=>__('Disabled',XH_SOCIAL),
                   'mp_cross_domain_enabled'=>__('Enabled',XH_SOCIAL)
               )
           );
            
           $fields['mp_cross_domain_url']=array(
               'title'=>__('Cross-domain Url',XH_SOCIAL),
               'tr_css'=>'tab-client tab-mp section-mp_enabled_cross_domain section-mp_cross_domain_enabled',
               'placeholder'=>'http://other-domain.com/cross-domain-wechat-mp.php',
               'type'=>'text',
               'description'=>__('如果：你需要用自己的公众号（qq，微博）作为代理，请购买：<a href="https://www.wpweixin.net/product/1211.html" target="_blank">跨域扩展</a>',XH_SOCIAL)
           );
       
           
            $fields2=array(
            //===========================
                'fieldset2'=>array(
                    'title' => __ ( 'WeChat open platform', XH_SOCIAL ),
                    'type' => 'subtitle',
                    'dividing'=>false,
                    'tr_css'=>'tab-client tab-op',
                    'description'=>__ ( '使用微信扫码登录,<a href="http://open.weixin.qq.com" target="_blank">查看详情</a>。(开放平台内必须关联公众号，否则同个微信号会创建两个账户)', XH_SOCIAL )
                ),
                'op_id'=>array(
                    'title' => __ ( 'AppID', XH_SOCIAL ),
                    'tr_css'=>'tab-client tab-op',
                    'type' => 'textbox'
                ),
                'op_secret'=>array(
                    'title' => __ ( 'AppSecret', XH_SOCIAL ),
                    'tr_css'=>'tab-client tab-op',
                    'type' => 'textbox'
                )
            );
            
            $fields2['subtitle3']=array(
                'title'=>__('Cross-domain Settings',XH_SOCIAL),
                'type'=>'subtitle',
                'dividing'=>false,
                'tr_css'=>'tab-client tab-op',
                'description'=>__('多网站共用一个微信开放平台应用进行扫码登录<span style="color:red;">(需要时才开启)</span>',XH_SOCIAL)
            );
            
            $fields2['op_enabled_cross_domain']=array(
                'title'=>__('Cross-domain',XH_SOCIAL),
                'type'=>'section',
                'tr_css'=>'tab-client tab-op',
                'options'=>array(
                    'op_cross_domain_disabled'=>__('Disabled',XH_SOCIAL),
                    'op_cross_domain_enabled'=>__('Enabled',XH_SOCIAL)
                )
            );
            
            $fields2['op_cross_domain_url']=array(
                'section'=>'op_cross_domain_enabled',
                'title'=>__('Cross-domain Url',XH_SOCIAL),
                'tr_css'=>'tab-client tab-op section-op_enabled_cross_domain section-op_cross_domain_enabled',
                'placeholder'=>'http://other-domain.com/cross-domain-wechat-op.php',
                'type'=>'text',
                'description'=>__('如果：你需要用自己的公众号（qq，微博）作为代理，请购买：<a href="https://www.wpweixin.net/product/1211.html" target="_blank">跨域扩展</a>',XH_SOCIAL)
            );
       $this->form_fields= apply_filters('xh_social_channel_wechat_form_fields',  array_merge($fields,$fields2));
    }
    
    /**
     * 获取openid
     * @return string|exit
     * @since 1.0.8
     */
    public function get_openid(){
        $openid=null;
        if(is_user_logged_in()){
            $ext_user_info = $this->get_ext_user_info_by_wp(get_current_user_id());
            if($ext_user_info){
                $openid=isset($ext_user_info['mp_openid'])?$ext_user_info['mp_openid']:null;
            }
           
        }
        
        //重新获取openid
        if(empty($openid)){
            if(is_user_logged_in()){
                //仅能在微信端，获取mp openid(避免无限循环)
                if(!XH_Social_Helper_Uri::is_wechat_app()){
                    XH_Social::instance()->WP->wp_die(__('请在微信客户端打开链接',XH_SOCIAL),true,true);
                    exit;
                }
                wp_redirect(XH_Social::instance()->channel->get_do_bind_redirect_uri($this->id,XH_Social_Helper_Uri::get_location_uri()));
            }else{
                wp_redirect(XH_Social::instance()->channel->get_authorization_redirect_uri($this->id,XH_Social_Helper_Uri::get_location_uri()));
            }
            exit;
        }
        
        return $openid;
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_share_link()
     */
    public function get_share_link(){
        $api =XH_Social_Add_On_Social_Wechat::instance();
        $params = array();
        $ajax_url =XH_Social::instance()->ajax_url(array(
            'action'=>"xh_social_{$api->id}",
            'tab'=>'share_qrcode'
        ),true,true);
        
        return array(
            'link'=>$ajax_url."&url=".urlencode(XH_Social_Helper_Uri::get_location_uri()),
            'width'=>450,
            'height'=>400
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
        
        do_action('xh_social_channel_update_wp_user_info',$ext_user_info);
        do_action('xh_social_channel_wechat_update_wp_user_info',$ext_user_info);
        update_user_meta($wp_user_id, '_social_img', $ext_user_info['user_img']);
        //兼容其他插件
        if(isset($ext_user_info['mp_openid'])&& !empty($ext_user_info['mp_openid'])){
            update_user_meta($wp_user_id, 'openid', $ext_user_info['mp_openid']);
        }
        
        return $this->get_wp_user_info($ext_user_id);
    }
    
    public function get_mp_cross_domain_url(){
        if('mp_cross_domain_enabled'==$this->get_option('mp_enabled_cross_domain')){
            return $this->get_option('mp_cross_domain_url');
        }
        return null;
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Social_Settings_Channel::get_wp_user_info($ext_user_id)
     */
    public function get_wp_user_info($ext_user_id){
        $ext_user_id = intval($ext_user_id);
        global $wpdb;
        $user = $wpdb->get_row(
           "select w.user_id,
                   w.mp_openid
            from {$wpdb->prefix}xh_social_channel_wechat w
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
            from {$wpdb->prefix}xh_social_channel_wechat w
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
                'user_img'=>$user->img,
                'user_login'=>null,
                'user_email'=>null,
                'uid'=>$user->unionid,
                'nicename'=>$guid,
                'mp_openid'=>$user->mp_openid,
                'op_openid'=>$user->op_openid,
        );
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
        return array(
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
    }
    
    public function get_wp_user($field,$field_val){
        if(!in_array($field, array(
            'mp_openid',
            'op_openid',
            'unionid',
            'uid'
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
            'unionid',
            'uid'
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
    
    public function process_authorization_callback($wp_user_id,$uid){
        $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');
        if(empty($login_location_uri)){
            $login_location_uri = home_url('/');
        }
        
        if(!isset($_GET['state'])||!isset($_GET['code'])){
            return $login_location_uri;
        }
        
        $code = XH_Social_Helper_String::sanitize_key_ignorecase($_GET['code']);
        $prefix=XH_Social_Helper_String::sanitize_key_ignorecase($_GET['state']);
        if(!in_array($prefix, array('op','mp'))){
           return $login_location_uri;
        }
        
        $response=array();
        
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
           
           
        } catch (Exception $e) {
            XH_Social_Log::error($e);
            if($e->getCode()!=500){
                $err_times = isset($_GET['err_times'])?intval($_GET['err_times']):3;
               
                if($err_times>0){
                    $err_times--;
                    return $this-> _login_get_authorization_uri($wp_user_id,$uid,$err_times);
                }
            }
            
            XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
            return $login_location_uri;
        }
        
       try {
           $ext_user_id =$this->create_ext_user_info($prefix,$response,$wp_user_id,$uid);
           return $this->process_login($ext_user_id);
       } catch (Exception $e) {
           XH_Social_Log::error($e);
           XH_Social::instance()->WP->set_wp_error($login_location_uri, $e->getMessage());
           return $login_location_uri;
       }
    }
  
    /**
     * 创建扩展用户信息
     * @param string $prefix  mp|op
     * @param array $response userinfo
     * @throws Exception
     * @return number
     */
    public function create_ext_user_info($prefix,$user_data,$wp_user_id,$uid){
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
            $update['nickname']= XH_Social_Helper_String::remove_emoji($user_data['nickname']);
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
        }
        
        if(isset($user_data['unionid'])&&!empty($user_data['unionid'])){
            $update['unionid']=$user_data['unionid'];
        }
        

        if($wp_user_id
            &&$wp_user_id>0
            &&$user
            &&$user->user_id
            &&$user->user_id!=$wp_user_id){
                $wp_user = get_userdata($user->user_id);
                if($wp_user){
                     throw new Exception(sprintf(__("对不起，您的微信已与账户(%s)绑定，请解绑后重试！",XH_SOCIAL),$wp_user->nickname));
                }
        }
        
        if($wp_user_id>0&&(!$user||$user->user_id!=$wp_user_id)){
            $wpdb->query("delete from {$wpdb->prefix}xh_social_channel_wechat where user_id=$wp_user_id ;");
            if(!empty($wpdb->last_error)){
                XH_Social_Log::error($wpdb->last_error);
                throw new Exception(__($wpdb->last_error,XH_SOCIAL));
            }
        }
        
        if($user){
            if($wp_user_id>0){
                $update['user_id']=$wp_user_id;
            }
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
            if($wp_user_id>0){
                $update['user_id']=$wp_user_id;
            }
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
     * @see Abstract_XH_Social_Settings_Channel::generate_authorization_uri()
     */
    public function generate_authorization_uri($user_ID=0,$login_location_uri=null){ 
       $uid = XH_Social_Helper_String::guid();
       return $this->_login_get_authorization_uri(is_null($user_ID)?0:$user_ID,$uid,null);
    }
    
    /**
     * 获取登录授权链接
     * @param string $login_location_uri
     * @param int $error_times
     * @return string
     * @since 1.0.0
     */
    public function _login_get_authorization_uri($user_ID=0,$uid=null,$error_times=null){
        if(XH_Social_Helper_Uri::is_wechat_app()){
           return $this->login_get_wechatclient_authorization_uri(is_null($user_ID)?0:$user_ID,$uid,$error_times);
        }else{
           return $this->login_get_pcclient_authorization_uri(is_null($user_ID)?0:$user_ID,$uid,$error_times);
        }
    }
    
    public function login_get_pcclient_authorization_uri($user_ID=0,$uid=null,$error_times=null){
        if(empty($uid)){
            $uid = XH_Social_Helper_String::guid();
        }
        $state="op";
        $api = XH_Social_Add_On_Social_Wechat::instance();
        $params=array();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params);
        $params['tab']='authorization';
        $params['action']="xh_social_{$api->id}";
        $params["xh_social_{$api->id}"]=wp_create_nonce("xh_social_{$api->id}");
        $params['s'] =$state;
        $params['uuid']=is_null($user_ID)?0:$user_ID;
        $params['notice_str']=str_shuffle(time());
        $params['uid']=$uid;
        $params['hash']=XH_Social_Helper::generate_hash($params, XH_Social::instance()->get_hash_key());
        
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }
        
        $redirect_uri=$url."?".http_build_query($params);
        
        $uri=null;
        $uri = apply_filters('xh_social_channel_wechat_login_get_authorization_uri', $uri,$redirect_uri,$state,$uid,is_null($user_ID)?0:$user_ID,$error_times);
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
    
    public function login_get_wechatclient_authorization_uri($user_ID=0,$uid=null,$error_times=null){
        if(empty($uid)){
            $uid = XH_Social_Helper_String::guid();
        }else{
            $wp_user = $this->get_wp_user('uid', $uid);
            if($wp_user){
                XH_Social::instance()->WP->wp_die(__('Please refresh login page and try scan qrcode again!',XH_SOCIAL));
                exit;
            }
        }
        
        $state="mp";
        $api = XH_Social_Add_On_Social_Wechat::instance();
     
        $params=array();
        $url=XH_Social_Helper_Uri::get_uri_without_params(XH_Social::instance()->ajax_url(),$params);
        $params['tab']='authorization';
        $params['action']="xh_social_{$api->id}";
        $params["xh_social_{$api->id}"]=wp_create_nonce("xh_social_{$api->id}");
        $params['uuid']=is_null($user_ID)?0:$user_ID;
        $params['s'] =$state;
        $params['notice_str']=str_shuffle(time());
        $params['uid']=$uid;
        $params['hash']=XH_Social_Helper::generate_hash($params, XH_Social::instance()->get_hash_key());
        if(!is_null($error_times)){
            $params['err_times']=$error_times;
        }

        $redirect_uri= $url."?".http_build_query($params);
        
        $uri=null;
        $uri = apply_filters('xh_social_channel_wechat_login_get_authorization_uri', $uri,$redirect_uri,$state,$uid,is_null($user_ID)?0:$user_ID,$error_times);
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

require_once XH_SOCIAL_DIR.'/includes/abstracts/abstract-xh-schema.php';
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
                    UNIQUE INDEX `uid_unique` (`uid`)
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
        //remove useless codes 
        //@date 2017年7月20日 15:19:18
        //@by ranj
//             if($wpdb->get_var("show index from `{$wpdb->prefix}xh_social_channel_wechat` where Column_name='uid' and Key_name='uid_key'")!= "{$wpdb->prefix}xh_social_channel_wechat"){
//                 $wpdb->query ( "ALTER TABLE `{$wpdb->prefix}xh_social_channel_wechat` ADD INDEX uid_key(uid);" );
//                 if(!empty($wpdb->last_error)){
//                     XH_Social_Log::error($wpdb->last_error);
//                     throw new Exception($wpdb->last_error);
//                 }
//             }
             
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