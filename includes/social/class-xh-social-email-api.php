<?php
if (! defined('ABSPATH')) {
    exit();
}

class XH_Social_Email_Api extends Abstract_XH_Social_Settings
{

    /**
     * Instance
     * 
     * @since 1.0.0
     */
    private static $_instance;

    /**
     * Instance
     * 
     * @since 1.0.0
     */
    public static function instance()
    {
        if (is_null(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    private function __construct()
    {
        $this->id = 'settings_default_other_email';
        $this->title = __('Email Settings', XH_SOCIAL);
        
        $this->init_form_fields();
    }

    public function init()
    {
        add_action('phpmailer_init', array($this,'phpmailer_init_smtp'), 10, 1);
        add_filter('wp_mail_from',array($this,'wp_mail_smtp_mail_from'),10,1);
        add_filter('wp_mail_from_name',array($this,'wp_mail_smtp_mail_from_name'),10,1);
    }
    
    public function wp_mail_smtp_mail_from_name ($orig) {
        // Only filter if the from name is the default
        
        if ($orig == 'WordPress') {
            $name = $this->get_option('mail_from_name');
            if(!empty($name)&&is_string($name)){
                return $name;
            }
        }
    
        // If in doubt, return the original value
        return $orig;
    
    }
    public function wp_mail_smtp_mail_from ($orig) {
        // This is copied from pluggable.php lines 348-354 as at revision 10150
        // http://trac.wordpress.org/browser/branches/2.7/wp-includes/pluggable.php#L348
    
        // Get the site domain and get rid of www.
        $sitename = strtolower( $_SERVER['SERVER_NAME'] );
        if ( substr( $sitename, 0, 4 ) == 'www.' ) {
            $sitename = substr( $sitename, 4 );
        }
    
        $default_from = 'wordpress@' . $sitename;        
        if ( $orig != $default_from ) {
            return $orig;
        }
        
        $from_email = $this->get_option('mail_from');
        if (is_email($from_email)){
            return $from_email;
        }
        
        return $orig;
    
    }
    
    /**
     * 
     * @param PHPMailer $phpmailer
     * @return PHPMailer
     */
    public function phpmailer_init_smtp($phpmailer)
    {
        $mailer =$this->get_option('mailer','mail');
        if(empty($mailer)||$mailer=='mail'){
            return;
        }
        
        if($mailer==$phpmailer->Mailer){
            return;
        }

        if(!empty( $phpmailer->From)){
            $phpmailer->Sender = $phpmailer->From;
        }
        
        // Set the mailer type as per config above, this overrides the already called isMail method        
        switch ($mailer){
            case 'mail':
            default:
                return;
            case 'smtp':
                $phpmailer->Mailer = $mailer;
                $phpmailer->SMTPSecure = $this->get_option('smtp_ssl');
                // Set the other options
                $phpmailer->Host = $this->get_option('smtp_host');
                $phpmailer->Port = $this->get_option('smtp_port');
                
                // If we're using smtp auth, set the username & password
                if ($this->get_option('smtp_auth') == "yes") {
                    $phpmailer->SMTPAuth = true;
                    $phpmailer->Username = $this->get_option('smtp_user');
                    $phpmailer->Password = $this->get_option('smtp_pass');
                }
                return;
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'disabled_captcha' => array(
                'title' => __('Disabled Captcha', XH_SOCIAL),
                'type' => 'checkbox',
                'label' => __('Disable captcha verify when send email validation code.', XH_SOCIAL),
                'default' => 'yes'
            ),
            'mail_from' => array(
                'title' => __('From Email', XH_SOCIAL),
                'type' => 'text',
                'description' => __('You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', XH_SOCIAL)
            ),
            'mail_from_name' => array(
                'title' => __('From Name', XH_SOCIAL),
                'type' => 'text',
                'description' => __('You can specify the name that emails should be sent from. If you leave this blank, the emails will be sent from WordPress.', XH_SOCIAL)
            ),
            'mailer' => array(
                'title' => __('Mailer', XH_SOCIAL),
                'type' => 'section',
                'options' => array(
                    'mail' => __('Use the PHP mail() function to send emails(wordpress default).', XH_SOCIAL),
                    'smtp' => __('via SMTP.', XH_SOCIAL),
                ),
                'description' => __('You can specify the email address that emails should be sent from. If you leave this blank, the default email will be used.', XH_SOCIAL)
            ),
            'smtp_settings' => array(
                'title' => __('SMTP Options', XH_SOCIAL),
                'type' => 'subtitle',
                'tr_css' => 'section-mailer section-smtp'
            ),
            'smtp_host' => array(
                'title' => __('SMTP Host', XH_SOCIAL),
                'type' => 'text',
                'placeholder'=>'smtp.exmail.qq.com',
                'tr_css' => 'section-mailer section-smtp'
            ),
            'smtp_port' => array(
                'title' => __('SMTP Port', XH_SOCIAL),
                'type' => 'text',
                'placeholder'=>'465',
                'tr_css' => 'section-mailer section-smtp'
            ),
            'smtp_ssl' => array(
                'title' => __('Encryption', XH_SOCIAL),
                'type' => 'select',
                'options' => array(
                    '' => __('No encryption.', XH_SOCIAL),
                    'ssl' => __('Use SSL encryption.', XH_SOCIAL),
                    'tls' => __('Use TLS encryption. This is not the same as STARTTLS. For most servers SSL is the recommended option.', XH_SOCIAL)
                ),
                'tr_css' => 'section-mailer section-smtp'
            ),
            'smtp_accounts' => array(
                'title' => __('SMTP account', XH_SOCIAL),
                'type' => 'subtitle',
                'tr_css' => 'section-mailer section-smtp'
            ),
            'smtp_auth' => array(
                'title' => __('Authentication', XH_SOCIAL),
                'type' => 'select',
                'options' => array(
                    'no' => __('No: Do not use SMTP authentication.', XH_SOCIAL),
                    'yes' => __('Yes: Use SMTP authentication.', XH_SOCIAL)
                ),
                'tr_css' => 'section-mailer section-smtp',
                'description' => __('If "Authentication" set to no, the values below are ignored.', XH_SOCIAL)
            ),
            'smtp_user' => array(
                'title' => __('Username', XH_SOCIAL),
                'type' => 'text',
                'tr_css' => 'section-mailer section-smtp'
            ),
            'smtp_pass' => array(
                'title' => __('Password', XH_SOCIAL),
                'type' => 'text',
                'tr_css' => 'section-mailer section-smtp'
            )
        );
    }
    
}