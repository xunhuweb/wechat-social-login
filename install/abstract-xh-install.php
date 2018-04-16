<?php
if (! defined('ABSPATH'))
    exit(); // Exit if accessed directly

if (! class_exists('Abstract_XH_Install')) {

    abstract class Abstract_XH_Install
    {

        public $ajax_key;

        protected function __construct()
        {
            $this->ajax_key = strtolower(get_called_class());
            add_action("wp_ajax_{$this->ajax_key}", array(
                $this,
                'ajax'
            ));
            add_action("wp_ajax_nopriv_{$this->ajax_key}", array(
                $this,
                'ajax'
            ));
            add_action('activated_plugin', array(
                $this,
                'activated_plugin'
            ), 10, 2);
        }

        public abstract function plugin_file();

        public function activated_plugin($plugin, $network_wide)
        {
            if (ob_get_length() > 0) {
                return;
            }
            
            if ($plugin !== plugin_basename($this->plugin_file())) {
                return;
            }
            
            if (! $this->is_plugin_installed()) {
                wp_redirect($this->url(array(
                    'action' => $this->ajax_key,
                    'tab' => 'plugin_install'
                )));
            }
        }

        public function plugin_install($plugin, $request)
        {
            $step = isset($request['step']) ? $request['step'] : null;
            if (! $step || ! method_exists($this, $step)) {
                $this->wellcome($plugin, $request);
            } else {
                $this->{$step}($plugin, $request);
            }
        }

        public function unzip($file, $to)
        {
            @ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
            
            if (class_exists('ZipArchive', false)) {
                $this->_unzip_file_ziparchive($file, $to);
                return;
            }
            $this->_unzip_file_pclzip($file, $to);
        }

        private function _unzip_file_pclzip($file, $to)
        {
            mbstring_binary_safe_encoding();
            
            require_once (ABSPATH . 'wp-admin/includes/class-pclzip.php');
            
            $archive = new PclZip($file);
            
            $archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
            
            reset_mbstring_encoding();
            
            // Is the archive valid?
            if (! is_array($archive_files)){
                throw new Exception('PclZip error:incompatible archive');
            }
            
            if (0 == count($archive_files)){
                throw new Exception('PclZip empty archive');
            }
            
            $uncompressed_size = 0;
            $needed_dirs=array();
            // Determine any children directories needed (From within the archive)
            foreach ($archive_files as $file) {
                if ('__MACOSX/' === substr($file['filename'], 0, 9)) // Skip the OS X-created __MACOSX directory
                    continue;
                
                $uncompressed_size += $file['size'];
                
                $needed_dirs[] = $to . untrailingslashit($file['folder'] ? $file['filename'] : dirname($file['filename']));
            }
            
            /*
             * disk_free_space() could return false. Assume that any falsey value is an error.
             * A disk that has zero free bytes has bigger problems.
             * Require we have enough space to unzip the file and copy its contents, with a 10% buffer.
             */
            if (defined('DOING_CRON') && DOING_CRON) {
                $available_space = @disk_free_space(WP_CONTENT_DIR);
                if ($available_space && ($uncompressed_size * 2.1) > $available_space){
                    throw new Exception('PclZip error:Could not copy files. You may have run out of disk space.');
                }
            }
            
            $needed_dirs = array_unique($needed_dirs);
            foreach ($needed_dirs as $dir) {
                // Check the parent folders of the folders all exist within the creation array.
                if (untrailingslashit($to) == $dir) // Skip over the working directory, We know this exists (or will exist)
                    continue;
                if (strpos($dir, $to) === false) // If the directory is not within the working directory, Skip it
                    continue;
                
                $parent_folder = dirname($dir);
                while (! empty($parent_folder) && untrailingslashit($to) != $parent_folder && ! in_array($parent_folder, $needed_dirs)) {
                    $needed_dirs[] = $parent_folder;
                    $parent_folder = dirname($parent_folder);
                }
            }
            asort($needed_dirs);
            
            // Create those directories if need be:
            foreach ($needed_dirs as $_dir) {
                if (! $this->load_writeable_dir($_dir, true)) {
                    throw new Exception('pclzip error:Could not create directory:' . $dir);
                }
            }
            
            unset($needed_dirs);
            
            // Extract the files from the zip
            foreach ($archive_files as $file) {
                if ($file['folder']) {
                    continue;
                }
                
                if ('__MACOSX/' === substr($file['filename'], 0, 9)) {// Don't extract the OS X-created __MACOSX directory files
                    continue;
                }
                
                try {
                    $new_file = $to . $file['filename'];
                    $contents = $file['content'];
                    if (file_exists($new_file)) {
                        $result = @unlink($new_file);
                        if (! $result) {
                            throw new Exception("pclzip error:Could not remove exists file({$new_file}).");
                        }
                    }
                
                    $myfile = @fopen($new_file, "w");
                    if ($myfile) {
                        @fwrite($myfile, $contents);
                        @fclose($myfile);
                    }
                } catch (Exception $e) {
                    throw new Exception("pclzip error:Could not create new file({$new_file}).");
                }
                   
            }
        }

        private function _unzip_file_ziparchive($file, $to)
        {
            if (! class_exists('ZipArchive')) {
                throw new Exception('PHP ZipArchive is missing!');
            }
            
            $z = new ZipArchive();
            $zopen = $z->open($file, ZIPARCHIVE::CHECKCONS);
            if (true !== $zopen) {
                throw new Exception('ziparchive error:incompatible archive');
            }
            
            try {
                $uncompressed_size = 0;
                
                for ($i = 0; $i < $z->numFiles; $i ++) {
                    if (! $info = $z->statIndex($i)) {
                        throw new Exception('ziparchive error:Could not retrieve file from archive.');
                    }
                    
                    if ('__MACOSX/' === substr($info['name'], 0, 9)) { // Skip the OS X-created __MACOSX directory
                        continue;
                    }
                    
                    $uncompressed_size += $info['size'];
                    
                    if ('/' === substr($info['name'], - 1)) {
                        // Directory.
                        $needed_dirs[] = $to . untrailingslashit($info['name']);
                    } elseif ('.' !== $dirname = dirname($info['name'])) {
                        // Path to a file.
                        $needed_dirs[] = $to . untrailingslashit($dirname);
                    }
                }
                
                /*
                 * disk_free_space() could return false. Assume that any falsey value is an error.
                 * A disk that has zero free bytes has bigger problems.
                 * Require we have enough space to unzip the file and copy its contents, with a 10% buffer.
                 */
                if (defined('DOING_CRON') && DOING_CRON) {
                    $available_space = @disk_free_space(WP_CONTENT_DIR);
                    if ($available_space && ($uncompressed_size * 2.1) > $available_space) {
                        throw new Exception('ziparchive error:Could not copy files. You may have run out of disk space.');
                    }
                }
                
                $needed_dirs = array_unique($needed_dirs);
                foreach ($needed_dirs as $dir) {
                    // Check the parent folders of the folders all exist within the creation array.
                    if (untrailingslashit($to) == $dir) // Skip over the working directory, We know this exists (or will exist)
                        continue;
                    if (strpos($dir, $to) === false) // If the directory is not within the working directory, Skip it
                        continue;
                    
                    $parent_folder = dirname($dir);
                    while (! empty($parent_folder) && untrailingslashit($to) != $parent_folder && ! in_array($parent_folder, $needed_dirs)) {
                        $needed_dirs[] = $parent_folder;
                        $parent_folder = dirname($parent_folder);
                    }
                }
                asort($needed_dirs);
              
                // Create those directories if need be:
                foreach ($needed_dirs as $_dir) {
                    if (! $this->load_writeable_dir($_dir, true)) {
                        throw new Exception('ziparchive error:Could not create directory:' . $dir);
                    }
                }
                unset($needed_dirs);
                
                for ($i = 0; $i < $z->numFiles; $i ++) {
                    if (! $info = $z->statIndex($i)) {
                        throw new Exception('ziparchive error:Could not retrieve file from archive.');
                    }
                    
                    if ('/' == substr($info['name'], - 1)) // directory
                        continue;
                    
                    if ('__MACOSX/' === substr($info['name'], 0, 9)) // Don't extract the OS X-created __MACOSX directory files
                        continue;
                    
                    $contents = $z->getFromIndex($i);
                    if (false === $contents) {
                        throw new Exception("ziparchive error:Could not retrieve file from archive({$info['name']}).");
                    }
                    
                    try {
                        $new_file = $to . $info['name'];
                        if (file_exists($new_file)) {
                            $result = @unlink($new_file);
                            if (! $result) {
                                throw new Exception("ziparchive error:Could not remove exists file({$new_file}).");
                            }
                        }
                        
                        $myfile = @fopen($new_file, "w");
                        if(!$myfile){
                            throw new Exception("ziparchive error:Could not unzip file to new dictionary({$new_file}).");
                        }
                        
                        if(!@fwrite($myfile, $contents)){
                            throw new Exception("ziparchive error:Could not unzip file to new dictionary({$new_file}).");
                        }
                        
                        if(!@fclose($myfile)){
                            throw new Exception("ziparchive error:Can not free file handler({$new_file}).");
                        }
                    } catch (Exception $e) {
                        throw new Exception("ziparchive error:Could not create new file({$new_file}).");
                    }
                }
            } catch (Exception $e) {
                if ($z) {
                    $z->close();
                }
                
                throw $e;
            }
            
            $z->close();
        }

        public function load_writeable_dir($dir, $create_if_not_exists = true)
        {
            try {
                if (! @is_dir($dir)) {
                    if ($create_if_not_exists) {
                        if (! @mkdir($dir, 0777, true)) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
                //防止bug，都使用这个接口
                return @win_is_writable($dir);
            } catch (Exception $e) {
                return false;
            }
            
            return true;
        }

        public function load_readable_dir($dir, $create_if_not_exists = true)
        {
            try {
                if (! @is_dir($dir)) {
                    if ($create_if_not_exists) {
                        if (! @mkdir($dir, 0777, true)) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }
                
                return @is_readable($dir);
            } catch (Exception $e) {
                return false;
            }
            
            return true;
        }

        /**
         * 判断插件是否已安装
         *
         * @return boolean
         */
        public function is_plugin_installed()
        {
            $plugins = get_option('xh_install_plugins', array());
            if (! $plugins || ! is_array($plugins)) {
                return false;
            }
            
            $key = plugin_basename($this->plugin_file());
            return isset($plugins[$key]) && isset($plugins[$key]['installed']) && $plugins[$key]['installed'];
        }

        /**
         * 获取插件信息
         *
         * @return NULL
         */
        public function get_plugin_options($reset = false)
        {
            static $xh_install_plugins;
            if ($reset || ! $xh_install_plugins) {
                $xh_install_plugins = get_option('xh_install_plugins', array());
            }
            
            if (! $xh_install_plugins || ! is_array($xh_install_plugins)) {
                return null;
            }
            
            $key = plugin_basename($this->plugin_file());
            return isset($xh_install_plugins[$key]) ? $xh_install_plugins[$key] : null;
        }

        public function update_plugin_options($option)
        {
            $xh_install_plugins = get_option('xh_install_plugins', array());
            if (! $xh_install_plugins || ! is_array($xh_install_plugins)) {
                $xh_install_plugins = array();
            }
            
            $key = plugin_basename($this->plugin_file());
            if (! isset($xh_install_plugins[$key])) {
                $xh_install_plugins[$key] = array();
            }
            
            $xh_install_plugins[$key] = array_merge($xh_install_plugins[$key], $option); 
            update_option('xh_install_plugins', $xh_install_plugins, true);
        }

        public function capability($roles = array('administrator'))
        {
            global $current_user;
            if (! is_user_logged_in()) {}
            
            if (! $current_user->roles || ! is_array($current_user->roles)) {
                $current_user->roles = array();
            }
            
            foreach ($roles as $role) {
                if (in_array($role, $current_user->roles)) {
                    return true;
                }
            }
            return false;
        }

        public function ajax()
        {
            $request = shortcode_atts(array(
                'tab' => '',
                'action' => $this->ajax_key,
                $this->ajax_key => '',
                'hash' => null,
                'notice_str' => null
            ), stripslashes_deep($_REQUEST));
            
            if (isset($_REQUEST['step'])) {
                $request['step'] = stripslashes($_REQUEST['step']);
            }
            
            if (isset($_REQUEST['ignore_step'])) {
                $request['ignore_step'] = stripslashes($_REQUEST['ignore_step']);
            }
            
            if (isset($_REQUEST['addon_id'])) {
                $request['addon_id'] = stripslashes($_REQUEST['addon_id']);
            }
            
            if (! $this->capability()) {
                wp_die(XH_Social_Error::err_code(501)->to_json());
                exit();
            }
            
            if ($request['hash'] != $this->generate_hash($request)) {
                wp_die('Sorry!invalid request.');
                exit();
            }
            
            if (! check_ajax_referer($this->ajax_key, $this->ajax_key, false)) {
                wp_die('Sorry!your request is timeout');
                exit();
            }
            
            if (method_exists($this, $request['tab'])) {
                $current_plugin = $this->get_plugin_info();
                if (! $current_plugin) {
                    wp_die('Sorry!Plugin not found(or can not be read) when installing');
                    exit();
                }
                $this->{$request['tab']}($current_plugin, $request);
                exit();
            }
            
            wp_die('Sorry!your request is not found!');
            exit();
        }

        public function get_plugin_info()
        {
            $path = $this->plugin_file();
            if (! is_readable($path)) {
                return null;
            }
            
            try {
                return get_plugin_data($path, false, false);
                ;
            } catch (Exception $e) {
                return null;
            }
        }

        public function url($action = null)
        {
            $url = admin_url('admin-ajax.php');
            
            $params = array();
            if ($action) {
                if (is_string($action)) {
                    $params['action'] = $action;
                } else 
                    if (is_array($action)) {
                        $params = $action;
                    }
            }
            
            if (isset($params['action']) && ! empty($params['action'])) {
                if (true) {
                    $params[$params['action']] = wp_create_nonce($params['action']);
                }
            }
            
            if (true) {
                $params['notice_str'] = str_shuffle(time());
                $params['hash'] = $this->generate_hash($params);
            }
            
            if (count($params) > 0) {
                $url .= "?" . http_build_query($params);
            }
            return $url;
        }

        public function generate_hash(array $datas)
        {
            ksort($datas);
            reset($datas);
            
            $arg = '';
            $index = 0;
            foreach ($datas as $key => $val) {
                if ($key == 'hash') {
                    continue;
                }
                if ($index ++ != 0) {
                    $arg .= "&";
                }
                
                if (! is_string($val) && ! is_numeric($val)) {
                    continue;
                }
                $arg .= "$key=$val";
            }
            
            $hash_key = AUTH_KEY;
            if (empty($hash_key)) {
                $hash_key = __FILE__;
            }
            
            return md5($arg . $hash_key);
        }
    }
}