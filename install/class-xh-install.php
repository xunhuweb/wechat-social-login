<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

if (! class_exists('Abstract_XH_Install')) {
require_once 'abstract-xh-install.php';
}

class XH_Social_Install extends Abstract_XH_Install{
    /**
     * @since 1.0.0
     * @var XH_Social_Install
     */
    private static $_instance = null;
    
    /**
     * @since 1.0.0
     * @static
     * @return XH_Social_Install
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    protected function __construct(){
        parent::__construct();
    }
    

    public function get_plugin_install_url($step=null){
        $request =array('action'=>$this->ajax_key,'tab'=>'plugin_install');
        if(!empty($step)){
            $request['step']=$step;
        }
        return $this->url($request);
    }
    
    public function get_plugin_license_url(){
        $request =array('action'=>$this->ajax_key,'tab'=>'license_change');
        return $this->url($request);
    }
    
    public function get_addon_license_url($addon_id){
        $request =array('action'=>$this->ajax_key,'tab'=>'addons_license','addon_id'=>$addon_id);
        return $this->url($request);
    }
    
    public function plugin_file(){
        return XH_SOCIAL_FILE;
    }
    
    private function enable_license_page(){
        return true;
    }
    
    public function get_install_steps(){
        $steps = array(
            'system_status'=>__('System Status',XH_SOCIAL)
        );
        
        if($this->enable_license_page()){
            $steps['license']=__('License',XH_SOCIAL);
        }
            
        $steps['finished']=__('Ready!',XH_SOCIAL);
        return $steps;
    }
  
    public function get_plugin_settings_url(){
        return XH_Social::instance()->WP->get_plugin_settings_url();
    }
   
    protected function header($plugin,$request){
        if ( ! $guessurl = site_url() ){
            $guessurl = wp_guess_url();
        }
         
        $step = isset($request['step'])?$request['step']:null;
        $suffix = SCRIPT_DEBUG ? '' : '.min';
        $current_dir =XH_Social_Helper_Uri::wp_url(__FILE__);
        ?>
       <!DOCTYPE html>
		<html lang="zh-CN">
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php echo sprintf(__('%s &rsaquo; Setup Wizard',XH_SOCIAL),$plugin['Name']) ?></title>
			<link rel='stylesheet'  href='<?php echo $guessurl."/wp-admin/css/common{$suffix}.css"; ?>' type='text/css' media='all' />
			<link rel='stylesheet'  href='<?php echo $guessurl."/wp-admin/css/dashboard{$suffix}.css"; ?>' type='text/css' media='all' />
			<link rel='stylesheet'  href='<?php echo $guessurl."/wp-admin/css/forms{$suffix}.css"; ?>' type='text/css' media='all' />
			<link rel='stylesheet'  href='<?php echo $guessurl."/wp-admin/css/install{$suffix}.css"; ?>' type='text/css' media='all' />
			<link rel='stylesheet'  href='<?php echo $guessurl."/wp-includes/css/buttons{$suffix}.css"; ?>' type='text/css' media='all' /> 
            <link rel='stylesheet'  href='<?php echo $current_dir."/css/setup.css"?>' type='text/css' media='all' />
            
            <script src="<?php echo $guessurl.'/wp-includes/js/jquery/jquery.js'; ?>"></script>
		</head>
		<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="https://www.wpweixin.net" target="_blank"><img src="<?php echo $current_dir;?>/img/logo.png" alt="迅虎网络" /></a></h1>
			<?php if(!(isset($request['ignore_step'])&&$request['ignore_step'])){
			    ?>
			    <ol class="wc-setup-steps">
    				<?php
    				$steps =$this->get_install_steps();
    				$index =0;
    				$active = false;
    				foreach ($steps as $key=>$val){
    				   if($key===$step){
    				       $active=true;
    				       break;
    				   } 
    				   $index++;
    				}
    				
    				foreach ($steps as $key=>$val){
    				    ?><li class="<?php echo $active&&$index-->=0?"active":"";?>"><?php echo $val?></li><?php 
    				}?>
    				
    			</ol>
			    <?php 
			}?>
			
       <?php  
    }
    
    protected function footer($plugin,$request){
        ?>
            </body>
    	</html>
        <?php 
    }
    
    public function system_status($plugin,$request){
        $this->header($plugin,$request);
        $this->update_plugin_options(array(
            'installed'=>false
        ));
        $is_valid = true;
        ?>
        <div class="wc-setup-content">		
        <table class="wc_status_table widefat" cellspacing="0" id="status">
        	<thead>
        		<tr>
        			<th colspan="3"><h3><?php echo __('WordPress environment:',XH_SOCIAL)?></h3></th>
        		</tr>
        	</thead>
        	<tbody>
        		<tr>
        			<td><?php echo __('WP version:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php global $wp_version;
        			if(XH_Social::instance()->supported_wp_version()){
        			    ?><span style="color:green;"><?php echo $wp_version;?></span><?php 
        			}else{
        			    $is_valid=false;
        			    ?><div style="color: #a00;"><span class="dashicons dashicons-warning"></span> <?php echo sprintf(__("%s - We recommend a minimum wordpress version of %s ,See: %s",XH_SOCIAL),$wp_version,XH_Social::instance()->min_wp_version,'<a href="'.admin_url('update-core.php').'" target="_blank">'.__('Update your wordpress',XH_SOCIAL).'</a>');?></div><?php 
        			}
        			?></td>
        		</tr>
        	</tbody>
        </table>
        <table class="wc_status_table widefat" cellspacing="0" id="status">
        	<thead>
        		<tr>
        			<th colspan="3"><h3><?php echo __('Directory:',XH_SOCIAL)?></h3></th>
        		</tr>
        	</thead>
        	<tbody>
        		
        		<tr>
        			<td  style="width:60px;"><?php echo __('Add-ons:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php 
        			$dir =XH_Social::instance()->plugins_dir[0];
        			if($this->load_writeable_dir($dir)){
        			    ?><span style="color:green;">YES  (<code><?php echo $dir;?></code>)</span><?php
        			}else{
        			    $is_valid=false;
        			    ?><div style="color: #a00;"><span class="dashicons dashicons-warning"></span><?php echo __('Unable to create directory or cannot read, please create the directory manually and set the permissions on the (0777) :',XH_SOCIAL) ?> <code><?php echo $dir;?></code></div><?php
        			}
        			?></td>
        		</tr>
			    <tr>
        			<td><?php echo __('Bin:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php 
            			$dir =$bin_dir =str_replace('\\', '/', XH_SOCIAL_DIR.'/bin/');
            			if($this->load_readable_dir($dir)){
            			    ?><span style="color:green;">YES  (<code><?php echo $dir;?></code>)</span><?php
            			}else{
            			    ?><div style="color: red;"><span class="dashicons dashicons-warning"></span> <?php echo __('Unable to create directory or cannot read, please create the directory manually and set the permissions on the (0777) :',XH_SOCIAL)?><code><?php echo $dir;?></code></div><?php
            			}
            			?></td>
        		</tr>
        		<tr>
        			<td><?php echo __('Logs:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php 
        			$dir =str_replace('\\', '/', XH_SOCIAL_DIR.'/logs/');    
        			if($this->load_writeable_dir($dir)){
        			    ?><span style="color:green;">YES  (<code><?php echo $dir;?></code>)</span><?php
        			}else{
        			    ?><div style="color: red;"><span class="dashicons dashicons-warning"></span> <?php echo __('Unable to create directory or cannot read, please create the directory manually and set the permissions on the (0777) :',XH_SOCIAL)?><code><?php echo $dir;?></code></div><?php
        			}
        			?></td>
        		</tr>
        	</tbody>
        </table>
        <table class="wc_status_table widefat" cellspacing="0" id="status">
        	<thead>
        		<tr>
        			<th colspan="3"><h3><?php echo __('Server environment',XH_SOCIAL)?></h3></th>
        		</tr>
        	</thead>
        	<tbody>
        		<tr>
        			<td><?php echo __('PHP version:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php 
        			     if(version_compare(PHP_VERSION, '5.3.2.7','>=')){
        			         ?><span style="color:green;"><?php echo PHP_VERSION;?></span><?php
        			     }else{
        			         $is_valid=false;
        			         ?><div style="color: #a00;"><span class="dashicons dashicons-warning"></span> <?php echo sprintf(__("%s - We recommend a minimum php version of %s ,See: %s",XH_SOCIAL),PHP_VERSION,'5.3.2.7','<a href="http://www.wpupdatephp.com/" target="_blank">'.__('How to update your PHP version',XH_SOCIAL).'</a>');?></div><?php
        			     }
        			?></td>
        		</tr>
        		
        		<tr>
        			<td><?php echo __('PHP curl:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php 
        			     if(function_exists('curl_init')){
        			         ?><span style="color:green;">YES</span><?php
        			     }else{
        			         $is_valid=false;
        			         ?><div style="color: #a00;"><span class="dashicons dashicons-warning"></span> <?php echo sprintf(__("php curl extension is missing ,See: %s",XH_SOCIAL),'<a href="https://www.wpweixin.net/blog/1370.html" target="_blank">'.__('How to install php curl extension',XH_SOCIAL).'</a>');?></div><?php
        			     }
        			?></td>
        		</tr>
        		
        		<tr>
        			<td><?php echo __('PHP mbstring:',XH_SOCIAL)?></td>
        			<td class="help"><span class="xunhuweb-help-tip"></span></td>
        			<td><?php
        			     if(function_exists('mb_strlen')){
        			         ?><span style="color:green;">YES</span><?php
        			     }else{
        			         $is_valid=false;
        			         ?><div style="color: #a00;"><span class="dashicons dashicons-warning"></span> <?php echo sprintf(__("PHP mbstring extension is missing ,See: %s",XH_SOCIAL),'<a href="https://www.wpweixin.net/blog/1370.html" target="_blank">'.__('How to install php mbstring extension',XH_SOCIAL).'</a>');?></div><?php
        			     }
        			?></td>
        		</tr>
        		
        	</tbody>
        </table>
        
        <p class="wc-setup-actions step">
        	<?php 
            if(!$is_valid){
                ?><a href="javascript:void(0);" class="button button-large" style="color:gray;"><?php echo __('Continue',XH_SOCIAL)?></a><?php
            }else{
                ?><a href="<?php echo $this->get_plugin_install_url('system_status_save')?>" class="button-primary button button-large button-next"><?php echo __('Continue',XH_SOCIAL)?></a><?php
            }
            
        	?>
        	<a href="<?php echo $this->get_plugin_install_url('system_status_skip')?>" class="button button-large"><?php echo __('Skip this step',XH_SOCIAL)?></a>
        	<p style="color:green;">点击“继续”：高级扩展将<b>重新安装</b>到目录[Add-ons]。(如果没有正常安装，请手动解压[Bin]下的*.zip文件到[Add-ons])</p>
		</p>
		</div>
        <?php 
        $this->footer($plugin,$request);
    }
    
    public function wellcome($plugin,$request){
        $this->header($plugin,$request);
        ?>
		<div class="wc-setup-content">		
    		<h1><?php echo sprintf(__('"%s" setup',XH_SOCIAL),$plugin['Name'])?></h1>
    		<div><?php echo $plugin['Description']?></div>
    		<br/>
    		<p class="wc-setup-actions step">
    		<a href="<?php echo $this->get_plugin_install_url('system_status')?>" class="button-primary button button-large button-next"><?php echo __('Let\'s Go!',XH_SOCIAL)?></a>
			<a href="<?php echo $this->get_plugin_install_url('skip')?>" class="button button-large"><?php echo __('Not right now',XH_SOCIAL)?></a>
    		</p>
		</div>
        <?php 
        $this->footer($plugin,$request);
    }
    
    public function license($plugin,$request){
        $this->header($plugin,$request);
        
        $plugin_options = $this->get_plugin_options();
        $license_key = $plugin_options&&isset($plugin_options['license'])?$plugin_options['license']:null;
        
        $params =array('action'=>$this->ajax_key,'tab'=>'plugin_install');
        $params['step']='license_save';
        $pname=$plugin['Name'];
        ?>
		<div class="wc-setup-content">		
    		<h1><?php echo sprintf(__('"%s" license key',XH_SOCIAL),$pname)?></h1>
    		<p><?php echo sprintf(__('Thank you for using "%s", Please enter your license key below! Have any questions? visit our website %s or contact with %s',XH_SOCIAL),'<a href="'.$plugin['PluginURI'].'" target="_blank">'.$pname.'</a>','<a href="https://www.wpweixin.net" target="_blank">'.__('XunhuWeb',XH_SOCIAL).'</a>','<a href="http://wpa.qq.com/msgrd?v=3&uin=6347007&site=qq&menu=yes" target="_blank">'.__('Customer Service(via QQ)',XH_SOCIAL).'</a>')?></p>
    		<form action="<?php echo $this->url($params)?>" method="POST" id="form-license">
    			<input type="text" class="regular-text" value="<?php print esc_attr( $license_key)?>" name="license_key" placeholder="<?php echo __('license key',XH_SOCIAL)?>">
				<div style="color: red;font-size:12px;"><?php echo __('Don\'t have any license key? Just click the "continue" button.',XH_SOCIAL)?></div>
    		</form>
    		<p class="wc-setup-actions step">
        		<a href="javascript:void(0);" onclick="window.view.submit();" class="button-primary button button-large button-next"><?php echo __('Continue',XH_SOCIAL)?></a>
    		</p>
    		<script type="text/javascript">
				(function($){
						window.view={
							submit:function(){
								$('#form-license').submit();
							}
						};
				})(jQuery);
    		</script>
		</div>
        <?php 
        $this->footer($plugin,$request);
    }
    
    public function license_change($plugin,$request){      
        $request['ignore_step']=true;
        $this->header($plugin,$request);
        
        if(isset($_POST['license_key'])){
            $this->update_plugin_options(array(
                'license'=>stripcslashes($_POST['license_key'])
            ));
        }
        
        $plugin_options = $this->get_plugin_options(true);
        $license_key = $plugin_options&&isset($plugin_options['license'])?$plugin_options['license']:null;
        $pname=$plugin['Name'];
        ?>
		<div class="wc-setup-content">		
    		<h1><?php echo sprintf(__('"%s" license key',XH_SOCIAL),$pname)?></h1>
    		<p><?php echo sprintf(__('Thank you for using "%s", Please enter your license key below! Have any questions? visit our website %s or contact with %s',XH_SOCIAL),'<a href="'.$plugin['PluginURI'].'" target="_blank" >'.$pname.'</a>','<a href="https://www.wpweixin.net" target="_blank">'.__('XunhuWeb',XH_SOCIAL).'</a>','<a href="http://wpa.qq.com/msgrd?v=3&uin=6347007&site=qq&menu=yes" target="_blank">'.__('Customer Service(via QQ)',XH_SOCIAL).'</a>')?></p>
    		<form action="" method="POST" id="form-license">
    			<input type="text" class="regular-text" value="<?php print esc_attr( $license_key)?>" name="license_key" placeholder="<?php echo __('license key',XH_SOCIAL)?>">
    		</form>
    		<p class="wc-setup-actions step">
        		<a href="javascript:void(0);" onclick="window.view.submit();" class="button-primary button button-large button-next"><?php echo __('Change',XH_SOCIAL)?></a>
        		<a href="<?php echo $this->get_plugin_settings_url()?>" class="button button-large"><?php echo __('Back',XH_SOCIAL)?></a>
    		</p>
    		<script type="text/javascript">
				(function($){
						window.view={
							submit:function(){
								$('#form-license').submit();
							}
						};
				})(jQuery);
    		</script>
		</div>
        <?php 
        $this->footer($plugin,$request);
    }
    
    public function license_save(){
       $this->update_plugin_options(array(
            'license'=>stripcslashes($_POST['license_key'])
        ));
       
       $options = $this->get_plugin_options();
      
        wp_redirect($this->get_plugin_install_url('finished'));
        exit;
    }
    
    public function skip($plugin,$request){
        $this->update_plugin_options(array(
             'installed'=>true
        ));
        
        
        wp_redirect($this->get_plugin_settings_url());
        exit;
    }
    
    private function get_bin_files(){
        $plugins_dir=null;
        $plugin_files = array();
        $bin_dir =str_replace('\\', '/', XH_SOCIAL_DIR.'/bin/');
        
        try {
            $plugins_dir = @ opendir( $bin_dir);
            if ( $plugins_dir ) {
                while (($file = @readdir( $plugins_dir ) ) !== false ) {
                    if ( substr($file, 0, 1) == '.' ){
                        continue;
                    }
        
                    if ( substr($file, -4) !== '.zip' ){
                        continue;
                    }
        
                    $plugin_files[]=array(
                        'file'=>$bin_dir.$file,
                        'name'=>substr($file, 0,-4)
                    );
                }
        
                closedir( $plugins_dir );
            }
        } catch (Exception $e) {
            if($plugins_dir){
                @closedir( $plugins_dir );
            }
        }
        
        return $plugin_files;
    }
    
    public function system_status_save($plugin,$request){
        $plugin_files = $this->get_bin_files();
        
        if(count($plugin_files)>0){
            $add_ons_dir =XH_Social::instance()->plugins_dir[0];
            if(!$this->load_writeable_dir($add_ons_dir)){
                XH_Social::instance()->WP->wp_die("Add-ons installed failed! detail errors: Unable to read directory<code>$add_ons_dir</code>");
                exit;
            }
            
            try {
                //解压收费扩展到指定目录
                foreach ($plugin_files as $plugin){
                    $file = $plugin['file'];
                    $name = $plugin['name'];
                
                    $to_dir = $add_ons_dir.$name;
                    if(!$this->load_writeable_dir($to_dir)){
                        XH_Social::instance()->WP->wp_die("Add-ons installed failed! detail errors:Unable to read directory<code>$to_dir</code>");
                        exit;
                    }
                
                    try {
                        $this->unzip($file, $add_ons_dir);
                    } catch (Exception $e) {
                        XH_Social::instance()->WP->wp_die("Add-ons installed failed when unzip file! detail errors: ".$e->getMessage()."<code>$file</code>");
                        exit;
                    }
                    
                    try {
                        @unlink($file);
                    } catch (Exception $e) {
                    }
                }
            } catch (Exception $e) {
                XH_Social::instance()->WP->wp_die("Add-ons installed failed! detail errors: ".$e->getMessage());
                exit;
            }
            
            wp_redirect($this->get_plugin_install_url('license'));
            exit;
        }
        
        
        if($this->enable_license_page()){
            wp_redirect($this->get_plugin_install_url('license'));
        }else{
            wp_redirect($this->get_plugin_install_url('finished'));
        }
       
        exit;
    }
    
    public function system_status_skip($plugin,$request){
        if($this->enable_license_page()){ 
            wp_redirect($this->get_plugin_install_url('license'));
            exit;
        }
        
        wp_redirect($this->get_plugin_install_url('finished'));
        exit;
    }
    
    public function finished($plugin, $request){
        $this->update_plugin_options(array(
             'installed'=>true
        ));
        
        $this->header($plugin, $request);
        $pname=$plugin['Name'];
        ?>
        <div class="wc-setup-content">		
    		<h1><?php echo __('Your plugin is ready!',XH_SOCIAL)?></h1>
    		<p><?php echo sprintf(__('Thank you for using "%s", Have any questions? visit our website %s or contact with %s',XH_SOCIAL),'<a href="'.$plugin['PluginURI'].'" target="_blank">'.$pname.'</a>','<a href="https://www.wpweixin.net" target="_blank">'.__('XunhuWeb',XH_SOCIAL).'</a>','<a href="http://wpa.qq.com/msgrd?v=3&uin=6347007&site=qq&menu=yes" target="_blank">'.__('Customer Service(via QQ)',XH_SOCIAL).'</a>')?></p>
    		<br/>
    		<p class="wc-setup-actions step">
        		<a class="button-primary button button-large" href="<?php echo $this->get_plugin_settings_url()?>"><?php echo __('Go to settings',XH_SOCIAL)?></a>
    		</p>
		</div>
        <?php 
        $this->footer($plugin, $request);
    }
    
    public function addons_license($plugin, $request){
        $add_on = XH_Social::instance()->get_installed_addon($request['addon_id']);
        if(!$add_on){
            XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(404));
            exit;
        }
        
        $request['ignore_step']=true;
        $this->header($plugin,$request);
        
        $plugin_options = $this->get_plugin_options();
        $license_key = $plugin_options&&isset($plugin_options[$add_on->id])?$plugin_options[$add_on->id]:null;
        
        $params =array(
            'action'=>$this->ajax_key,
            'tab'=>'addons_license_save',
            'addon_id'=>$add_on->id
        );
        $pname ="{$plugin['Name']} - {$add_on->title}";
        ?>
		<div class="wc-setup-content">		
    		<h1><?php echo sprintf(__('"%s" license key',XH_SOCIAL),$pname)?></h1>
    		<p><?php echo sprintf(__('Thank you for using "%s", Please enter your license key below! Have any questions? visit our website %s or contact with %s',XH_SOCIAL),'<a href="'.$add_on->plugin_uri.'" target="_blank">'.$pname.'</a>','<a href="https://www.wpweixin.net" target="_blank">'.__('XunhuWeb',XH_SOCIAL).'</a>','<a href="http://wpa.qq.com/msgrd?v=3&uin=6347007&site=qq&menu=yes" target="_blank">'.__('Customer Service(via QQ)',XH_SOCIAL).'</a>')?></p>
    		<form action="<?php echo $this->url($params);?>" method="POST" id="form-license">
    			<input type="text" class="regular-text" value="<?php print esc_attr( $license_key)?>" name="license_key" placeholder="<?php echo __('license key',XH_SOCIAL)?>">
    		</form>
    		<p class="wc-setup-actions step">
    			<?php if($add_on->is_authoirzed){
    			    ?><a href="javascript:void(0);" onclick="window.view.submit();" class="button-primary button button-large button-next"><?php echo __('Change',XH_SOCIAL)?></a><?php
    			    ?><a href="<?php echo $add_on->get_settings_url();?>" class="button button-large"><?php echo __('Back',XH_SOCIAL)?></a><?php
    			}else{
    			    ?><a href="javascript:void(0);" onclick="window.view.submit();" class="button-primary button button-large button-next"><?php echo __('Validate',XH_SOCIAL)?></a><?php 
    			}?>
        		
    		</p>
    		<script type="text/javascript">
				(function($){
						window.view={
							submit:function(){
								$('#form-license').submit();
							}
						};
				})(jQuery);
    		</script>
		</div>
        <?php 
        $this->footer($plugin,$request);
    }
    
    public function addons_license_save($plugin, $request){
        $add_on = XH_Social::instance()->get_installed_addon($request['addon_id']);
        if(!$add_on){
            XH_Social::instance()->WP->wp_die(XH_Social_Error::err_code(404));
            exit;
        }
        
        $license_key = isset($_POST['license_key'])?stripcslashes($_POST['license_key']):null;
        $this->update_plugin_options(array(
            $add_on->id=>$license_key
        ));
        
        
        wp_redirect($this->get_addon_license_url($add_on->id));
    }
}    
?>