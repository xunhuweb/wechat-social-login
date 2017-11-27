<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class XH_Social_Settings_WP_Open_Default extends Abstract_XH_Social_Settings{
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;

    const SECRET='xh2017';
    const DOMAIN='https://www.weixinsocial.com';
    //注意：公众号的已放到weixinnet.com
    const APP_ID_QQ='101390507';
    const APP_SECRET_QQ='9ba1b8440a5ae5a4c7fc87f1ab6cf583';
    
    const APP_ID_WEIBO='1395788534';
    const APP_SECRET_WEIBO='53177994de6110977df354f249a127e4';
    
    const APP_ID_WECHAT_MP='wx0f031682e7eba20b';
    const APP_SECRET_WECHAT_MP='5e9544d1bd5a28a88f381972cf79d7a4';
    
    const APP_ID_WECHAT_OP='wx8b181ce7c14f7741';
    const APP_SECRET_WECHAT_OP='f32adcb839c6ea7589d8504d25dc8186';
    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }

    private function __construct(){
        $this->id='settings_wp_open_default';
        $this->title=__('代理登录',XH_SOCIAL);

        $this->init_form_fields();
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'pwd'=>array(
                    'title'=>__('Password',XH_SOCIAL),
                    'type'=>'text'
            )
        );
    }
    
    public function admin_form_start(){
        if( isset($_POST['notice-'.$this->id])){
            if(wp_verify_nonce($_POST['notice-'.$this->id], XH_Social::instance()->session->get_notice('admin:form:'.$this->id,true))){
                $pwd =isset($_POST['form-wp-open-password'])?$_POST['form-wp-open-password']:'';
                if($pwd==self::SECRET){
                    
                    if(class_exists('XH_Social_Channel_QQ')){
                        $qq = XH_Social_Channel_QQ::instance();
                        if($qq)
                        $qq->update_option_array(array(
                                'enabled'=>'yes',
                                'enabled_cross_domain'=>'cross_domain_enabled',
                                'cross_domain_url'=>self::DOMAIN.'/cross-domain-qq.php',
                                'appid'=>self::APP_ID_QQ,
                                'appsecret'=>self::APP_SECRET_QQ
                        ));
                    }
                    
                    if(class_exists('XH_Social_Channel_Weibo')){
                        $weibo = XH_Social_Channel_Weibo::instance();
                        if($weibo)
                        $weibo->update_option_array(array(
                            'enabled'=>'yes',
                            'enabled_cross_domain'=>'cross_domain_enabled',
                            'cross_domain_url'=>self::DOMAIN.'/cross-domain-weibo.php',
                            'appid'=>self::APP_ID_WEIBO,
                            'appsecret'=>self::APP_SECRET_WEIBO
                        ));
                    }
                    
                    if(class_exists('XH_Social_Channel_Wechat')){
                        $wechat =XH_Social_Channel_Wechat::instance();
                        if($wechat)
                        $wechat->update_option_array(array(
                            'enabled'=>'yes',
                            'mp_enabled_cross_domain'=>'mp_cross_domain_enabled',
                            'mp_cross_domain_url'=>self::DOMAIN.'/cross-domain-wechat-mp.php',
                            'mp_id'=>self::APP_ID_WECHAT_MP,
                            'mp_secret'=>self::APP_SECRET_WECHAT_MP,
                            
                            'op_enabled_cross_domain'=>'op_cross_domain_enabled',
                            'op_cross_domain_url'=>self::DOMAIN.'/cross-domain-wechat-op.php',
                            'op_id'=>self::APP_ID_WECHAT_OP,
                            'op_secret'=>self::APP_SECRET_WECHAT_OP
                        ));
                    }
                    $this->update_option('pwd', $pwd);
                }else{
                    $this->errors[]=__('Invalid password!',XH_SOCIAL);
                }
            }else{
                $this->errors[]=XH_Social_Error::err_code(701)->errmsg;
            }
        }
        
        $this->display_errors();
    }
   
    public function admin_options(){
        $pwd = $this->get_option('pwd');
        
        ?>
        <div>因微信、QQ、微博登录申请要求高，个人用户很难申请到，因此我们提供代理登录服务。</div>
        <div>
        <img src="https://www.wpweixin.net/wp-content/uploads/2016/12/xunhuma.jpg"><br>
        <p>输入密码获取微信、QQ、微博代理登录</p>
        </div>
        <form id="form-main" method="POST" action="">
        	<input type="hidden" name="notice-<?php print $this->id?>" value="<?php print wp_create_nonce ( XH_Social::instance()->session->get_notice('admin:form:'.$this->id));?>"/>
            <input class="input-text regular-input " type="text" value="<?php echo esc_attr($pwd)?>" name="form-wp-open-password" style="min-width:200px;margin-right:10px;"  placeholder="输入密码">
            <?php if($pwd==self::SECRET){
                ?>
                <input type="submit" value="重置" class="button-primary">
                <?php 
            }else{
                ?>
                <input type="submit" value="确认" class="button-primary">
                <?php 
            }?>
            
           <div class="description">扫描二维码关注【WordPress中文插件】，发送  <b>代理登录</b> 获取密码</div>
        </form>
        <script type="text/javascript">
			(function($){
				$('#form-main').submit(function(){
					return confirm('微信/微博/QQ等APP ID、APP SECRET、跨域设置将被重置，确认执行？');
				});
			})(jQuery);
		</script>
		<?php
    }
    
    public function admin_form_end(){}
    
}
?>