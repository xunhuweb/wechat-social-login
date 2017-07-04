<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
require_once 'abstract-xh-settings.php';	
/**
 * 社会化登录工具接口
 *
 * @since 1.0.0
 * @author ranj
 */
abstract class Abstract_XH_Social_Settings_Channel extends Abstract_XH_Social_Settings{   
    /**
     * 图标地址
     * @var string
     * @since 1.0.0
     */
    public $icon;
   
    /**
     * 声明支持的接口：login/share/register
     * 
     * @var array
     */
    public $supports = array( 'login' );

    /**
     * 判断是否启用
     * 
     * @param array $actions 申明支持接口,必须每个接口都存在，否则返回false
     * @return bool 
     * @since 1.0.0
     */
    public function is_available($action_includes = array()) {
        if(!$this->enabled){
            return false;
        }
        
        if(!$action_includes||count($action_includes)==0){
            //接口约束
            return true;
        }
        
        foreach ($action_includes as $action){
            if(!in_array($action, $this->supports)){
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 获取登录跳转地址
     * @param string $login_location_uri
     * @return string
     * @since 1.0.0
     */
    public function process_generate_authorization_uri($login_location_uri=null){
        return $this->generate_authorization_uri(0,$login_location_uri);
    }
    
    /**
     * 获取登录跳转地址
     * @param int|NULL $user_ID
     * @param string $login_location_uri
     * @return string
     * @since 1.0.6
     */
    public function generate_authorization_uri($user_ID=0,$login_location_uri=null){
        //执行登录跳转
        return '';
    }
    
    /**
     * 获取分享链接
     * @return array 
     * @since 1.0.7
     */
    public function get_share_link(){
        return array(
            'link'=>'',
            'width'=>450,
            'height'=>'450'
        );
    }
    
    /**
     * 更新wp用户与扩展用户之间的关联
     * @param int $ext_user_id
     * @param int $wp_user_id                 
     * @return WP_User|XH_Social_Error
     * @since 1.0.0
     */
    public function update_wp_user_info($ext_user_id,$wp_user_id=null){
        return XH_Social_Error::success();
    }
    
    /**
     * 获取扩展用户信息
     * @param int $ext_user_id 扩展用户ID
     * @return array
     * @since 1.0.0
     */
    public function get_ext_user_info($ext_user_id){
        return null;
    }
    
    /**
     * 获取wp用户信息
     * @param string $field
     * @param string $field_val
     * @return WP_User
     * @since 1.0.2
     */
    public function get_wp_user($field,$field_val){
        return null;
    }
    
    /**
     * 获取wp用户信息
     * @param string $field
     * @param string $field_val
     * @return object
     * @since 1.0.2
     */
    public function get_ext_user($field,$field_val){
        return null;
    }
    
    /**
     * 获取扩展用户信息
     * @param int $wp_user_id wp用户ID
     * @return array
     * @since 1.0.0
     */
    public function get_ext_user_info_by_wp($wp_user_id){
        return null;
    }
   
    /**
     * 移除扩展信息
     * @param int $wp_user_id
     * @return XH_Social_Error
     */
    public function remove_ext_user_info_by_wp($wp_user_id){
        return XH_Social_Error::success();
    }
    
    /**
     * 绑定信息
     * @param int $wp_user_id
     * @return string
     * @since 1.0.0
     */
    public function bindinfo($wp_user_id){
        if($wp_user_id instanceof WP_User){
            $wp_user_id=$wp_user_id->ID;
        }
        ob_start();
        if($this->get_ext_user_info_by_wp($wp_user_id)){
            ?>
            <span class="xh-text"><?php echo __('Is binding',XH_SOCIAL)?></span> <a href="<?php echo XH_Social::instance()->channel->get_do_unbind_uri($this->id,XH_Social_Helper_Uri::get_location_uri());?>" class="xh-btn xh-btn-warring xh-btn-sm"><?php echo __('Unbind',XH_SOCIAL)?></a><?php 
        }else{
            ?>
            <span class="xh-text"><?php echo __('Unbound',XH_SOCIAL)?></span> <a href="<?php echo XH_Social::instance()->channel->get_do_bind_redirect_uri($this->id,XH_Social_Helper_Uri::get_location_uri())?>" class="xh-btn xh-btn-primary xh-btn-sm"><?php echo __('Bind',XH_SOCIAL)?></a>
            <?php 
        }
        
        return ob_get_clean();
    }
    
    /**
     * 执行登录
     * @param int $ext_user_id 扩展用户ID
     * @return string 回调地址
     * @since 1.0.0
     */
    public function process_login($ext_user_id,$skip=false){
        $login_location_uri=XH_Social::instance()->session->get('social_login_location_uri');
        if(empty($login_location_uri)){
            $login_location_uri = home_url('/');
        }
        
        //清除上次错误数据
        XH_Social::instance()->WP->unset_wp_error($login_location_uri);
        
        if(!$ext_user_id||$ext_user_id<=0){
            //未知错误！
            XH_Social::instance()->WP->set_wp_error($login_location_uri, sprintf(__("对不起，%s用户绑定失败！错误：%s",XH_SOCIAL),$this->title,XH_Social_Error::err_code(500)->errmsg));
            return $login_location_uri;
        }
       
        $wp_user =$this->get_wp_user_info($ext_user_id);
        if($wp_user){
            if(is_user_logged_in()&&get_current_user_id()!=$wp_user->ID){
                XH_Social::instance()->WP->set_wp_error($login_location_uri,__("对不起，不允许在已登录的情况下登录其它账户!",XH_SOCIAL));
                return $login_location_uri;
            }
            
            $wp_user = $this->update_wp_user_info($ext_user_id,$wp_user->ID);
            if($wp_user instanceof  XH_Social_Error){
                XH_Social::instance()->WP->set_wp_error($login_location_uri,  sprintf(__("对不起，%s用户绑定失败！错误：%s",XH_SOCIAL),$this->title,$wp_user->errmsg));
                return $login_location_uri;
            }
            
            XH_Social::instance()->WP->do_wp_login($wp_user);
            return $login_location_uri;
        }
        
        //在此处，不可能已存在已登录的用户了
        if(is_user_logged_in()){
            XH_Social::instance()->WP->set_wp_error($login_location_uri,__("对不起，请刷新后重试!",XH_SOCIAL));
            return $login_location_uri;
        }
        
        //兼容其他插件老用用户
        $wp_user = apply_filters('xh_social_process_login_new_user',null, $this,$ext_user_id);   
        if($wp_user){
            $wp_user = $this->update_wp_user_info($ext_user_id,$wp_user->ID);
            if($wp_user instanceof  XH_Social_Error){
                XH_Social::instance()->WP->set_wp_error($login_location_uri,  sprintf(__("对不起，%s用户绑定失败！错误：%s",XH_SOCIAL),$this->title,$wp_user->errmsg));
                return $login_location_uri;
            }
            
            XH_Social::instance()->WP->do_wp_login($wp_user);
            return $login_location_uri;
        }
        
        if(!$skip){ 
            $other_login_location_uri='';
            $other_login_location_uri =apply_filters('xh_social_process_login',$other_login_location_uri,$this,$ext_user_id,$login_location_uri);        
            if(!empty($other_login_location_uri)){
                return $other_login_location_uri;
            } 
        }
       
        //直接创建用户并登录跳转
        $wp_user = $this->update_wp_user_info($ext_user_id,null);
        if($wp_user instanceof  XH_Social_Error){
            XH_Social::instance()->WP->set_wp_error($login_location_uri,  sprintf(__("对不起，%s用户绑定失败！错误：%s",XH_SOCIAL),$this->title,$wp_user->errmsg));
            return $login_location_uri;
        }
       
        XH_Social::instance()->WP->do_wp_login($wp_user);
        return $login_location_uri;
    }
   
    /**
     * 获取wp用户信息
     * @param int $ext_user_id 扩展用户ID
     * @return WP_User
     * @since 1.0.0
     */
    public function get_wp_user_info($ext_user_id){
        return null;
    }
}