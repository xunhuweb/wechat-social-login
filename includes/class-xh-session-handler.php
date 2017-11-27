<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Abstract_XH_Session {

	/** @var int $_customer_id */
	protected $_customer_id;

	/** @var array $_data  */
	protected $_data = array();

	/** @var bool $_dirty When something changes */
	protected $_dirty = false;

	/**
	 * __get function.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * __set function.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	 /**
	 * __isset function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
	    $key = $this->sanitize_key( $key );
		return isset( $this->_data[ $key ] );
	}
	protected function sanitize_key( $key ) {
	    $raw_key = $key;
	    $key = preg_replace( '/[^a-z0-9_\-A-Z]/', '', $key );
	
	    /**
	     * Filter a sanitized key string.
	     *
	     * @since 3.0.0
	     *
	     * @param string $key     Sanitized key.
	     * @param string $raw_key The key prior to sanitization.
	     */
	    return apply_filters( 'sanitize_key_ignorecase', $key, $raw_key );
	}
	/**
	 * __unset function.
	 *
	 * @param mixed $key
	 */
	public function __unset( $key ) {
	    $key = $this->sanitize_key( $key );
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * Get a session variable.
	 *
	 * @param string $key
	 * @param  mixed $default used if the session variable isn't set
	 * @return array|string value of session variable
	 */
	public function get( $key, $default = null ) {
		$key =$this->sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}
	
	/**
	 * Set a session variable.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ $this->sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty = true;
		}
	}

	/**
	 * get_customer_id function.
	 *
	 * @access public
	 * @return int
	 */
	public function get_customer_id() {
		return $this->_customer_id;
	}
}

/**
 * Handle data for the current customers session.
 * Implements the XH_Session abstract class.
 *
 *
 * @class    XH_Session_Handler
 * @since    1.0.0
 * @author   ranj
 */
class XH_Session_Handler extends Abstract_XH_Session {

	/** @var string cookie name */
	private $_cookie;

	/** @var string session due to expire timestamp */
	private $_session_expiring;

	/** @var string session expiration timestamp */
	private $_session_expiration;

	/** $var bool Bool based on whether a cookie exists **/
	private $_has_cookie = false;

	/** @var string Custom session table name */
	private $_table;
	
	private static $_instance;
	
	/**
	 * @since  1.0.0
	 */
	public static function instance() {
	    if ( is_null( self::$_instance ) )
	        self::$_instance = new self();
	
	        return self::$_instance;
	}
	/**
	 * Constructor for the session class.
	 */
	private function __construct() {
		global $wpdb;
		$this->_cookie = 'wp_xh_session_' . COOKIEHASH;
		$this->_table  = $wpdb->prefix . 'xh_sessions';
		$cookie = $this->get_session_cookie();
		if ( $cookie ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;

			// Update session if its close to expiring
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}

			$this->_data = $this->get_session_data();			
		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
			$this->_data = $this->get_session_data();
		}

		// Actions
		add_action( 'wp', array( $this, 'set_customer_session_cookie' ), 99 ); // Set cookies
		add_action( 'shutdown', array( $this, 'set_customer_session_cookie' ), 0 ); // Set cookies before shutdown and ob flushing
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		add_action( 'wp_login', array( $this, 'destroy_session' ));
		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	public function get_notice($key,$reset = false){
	    $key = $this->sanitize_key( $key );
	    $session_id ="notice:$key";
	    
	    $last_value = $this->get($session_id,'');
	 
	    if(empty($last_value)||$reset){
	        $new_value=str_shuffle(time());
	        $this->set($session_id,$new_value);
	        if(empty($last_value)){
	            $last_value=$new_value;
	        }
	    }
	   
	    return $last_value;
	}
	
	/**
	 * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
	 *
	 * Warning: Cookies will only be set if this is called before the headers are sent.
	 */
	public function set_customer_session_cookie(  ) {
	    if( ! headers_sent()){
    		// Set/renew our cookie
    		$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
    		$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
    		$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
    		$this->_has_cookie = true;
    
    		// Set the cookie
    		$this->setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, apply_filters( 'xh_session_use_secure_cookie', false ) );
	    }
	}
	
	private function setcookie( $name, $value, $expire = 0, $secure = false ) {
	    if ( ! headers_sent() ) {
	        setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure );
	    } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	        headers_sent( $file, $line );
	        trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	    }
	}
	
	/**
	 * Return true if the current user has an active session, i.e. a cookie to retrieve values.
	 *
	 * @return bool
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
	}

	/**
	 * Set session expiration.
	 */
	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'xh_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
		$this->_session_expiration = time() + intval( apply_filters( 'xh_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
	}

	/**
	 * Generate a unique customer ID for guests, or return user ID if logged in.
	 *
	 * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
	 *
	 * @return int|string
	 */
	public function generate_customer_id() {
// 		if ( is_user_logged_in() ) {
// 			return get_current_user_id();
// 		} else {
			return strtolower($this->guid());
		//}
	}
	private  function guid(){
	    $guid = '';
	    //extension=php_com_dotnet.dll
	    if (function_exists('com_create_guid')) {
	        $guid = com_create_guid();
	    } else {
	        mt_srand((double) microtime() * 10000); // optional for php 4.2.0 and up.
	        $charid = strtoupper(md5(uniqid(rand(), true)));
	        $hyphen = chr(45); // "-"
	        $uuid = chr(123) . // "{"
	        substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12) . chr(125); // "}"
	        $guid = $uuid;
	    }
	
	    return str_replace('-', '', trim($guid, '{}'));
	}
	/**
	 * Get session cookie.
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		if ( empty( $_COOKIE[ $this->_cookie ] ) || ! is_string( $_COOKIE[ $this->_cookie ] ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		// Validate hash
		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * Get session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id, array() ) : array();
	}

	/**
	 * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
	 *
	 * @return string
	 */
	private function get_cache_prefix() {
	    $group='xh_session_id';
		$prefix = wp_cache_get( 'xh_' . $group . '_cache_prefix', $group );
	
	    if ( false === $prefix ) {
	        $prefix = 1;
	        wp_cache_set( 'xh_' . $group . '_cache_prefix', $prefix, $group );
	    }
	
	    return 'xh_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 * @param  string $group
	 */
	public  function incr_cache_prefix( $group ) {
	    wp_cache_incr( 'xh_' . $group . '_cache_prefix', 1, $group );
	}
	/**
	 * Save data.
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->replace(
				$this->_table,
				array(
					'session_key' => $this->_customer_id,
					'session_value' => maybe_serialize( $this->_data ),
					'session_expiry' => $this->_session_expiration
				),
				array(
					'%s',
					'%s',
					'%d'
				)
			);
			
			// Set cache
			wp_cache_set( $this->get_cache_prefix() . $this->_customer_id, $this->_data, 'xh_session_id', $this->_session_expiration - time() );

			// Mark session clean after saving
			$this->_dirty = false;
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		// Clear cookie
		$this->setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'xh_session_use_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );

		// Clear data
		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce by using the customer/session ID.
	 *
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
	}

	/**
	 * Cleanup sessions.
	 */
	public function cleanup_sessions() {
		global $wpdb;

		if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {

			// Delete expired sessions
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) );

			// Invalidate cache
			$this->incr_cache_prefix( 'xh_session_id' );
		}
	}

	/**
	 * Returns the session.
	 *
	 * @param string $customer_id
	 * @param mixed $default
	 * @return string|array
	 */
	public function get_session( $customer_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		// Try get it from the cache, it will return false if not present or if object cache not in use
		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, 'xh_session_id' );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) );

			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, 'xh_session_id', $this->_session_expiration - time() );
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param int $customer_id
	 */
	public function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $customer_id, 'xh_session_id' );

		$wpdb->delete(
			$this->_table,
			array(
				'session_key' => $customer_id
			)
		);
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @param string $customer_id
	 * @param int $timestamp
	 */
	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;

		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp
			),
			array(
				'session_key' => $customer_id
			),
			array(
				'%d'
			)
		);
	}
}

include_once( 'abstracts/abstract-xh-schema.php' );
/**
 * session数据模型
 * 
 * @author ranj
 * @since 1.0.0
 */
class XH_Session_Handler_Model{
    protected function get_collate(){
        global $wpdb;
        $collate = '';
    
        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }
    
        return $collate;
    }
    
    /**
     * {@inheritDoc}
     * @see Abstract_XH_Model_Api::init()
     */
    public function init()
    {
        $collate=$this->get_collate();
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}xh_sessions` (
                	`session_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                	`session_key` CHAR(32) NOT NULL,
                	`session_value` LONGTEXT NOT NULL,
                	`session_expiry` BIGINT(20) NOT NULL,
                	PRIMARY KEY (`session_key`),
                	UNIQUE INDEX `session_id` (`session_id`)
                )
                $collate;");
        
        if(!empty($wpdb->last_error)){
            throw new Exception($wpdb->last_error);
        }
    }
}
