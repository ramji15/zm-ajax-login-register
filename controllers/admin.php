<?php

Class Admin Extends AjaxLogin {

    /**
     * WordPress hooks to be ran during init
     */
    public function __construct(){
        add_action( 'admin_init', array( &$this, 'admin_init' ) );
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links'), 10, 2 );
    }

    /**
     * Register settings during the admin init
     */
    public function admin_init(){
        $this->register_settings();
    }


    /**
     * Retrieve our settings from our parent class and register each setting.
     * @todo use add_settings_section()
     */
    public function register_settings(){

        $settings = $this->get_settings();

        foreach( $settings as $k => $v ){
            foreach( $settings[ $k ] as $k ) {
                register_setting('ajax_login_register', $k['key'], array( &$this, 'sanitize' ) );
            }
        }
    }


    /**
     * This method is fired when the settings that are register with register_settings()
     * are saved from the settings page. We filter out ALL html/js using wp_kses().
     */
    public function sanitize( $setting ){
        return wp_kses( $setting,array(),array());
    }


    /**
     * Build our admin menu
     */
    public function admin_menu(){

        $parent = 'options-general.php';

        $sub_menu_pages = array(
            array(
                'parent_slug' => $parent,
                'page_title' => __( 'AJAX Login &amp; Register', 'ajax_login_register' ),
                'menu_title' => __( 'AJAX Login &amp; Register', 'ajax_login_register' ),
                'capability' => 'manage_options',
                'menu_slug' => 'ajax-login-register-settings',
                'function' => 'load_template'
                )
            );

        foreach( $sub_menu_pages as $sub_menu ){
            add_submenu_page(
                $sub_menu['parent_slug'],
                $sub_menu['page_title'],
                $sub_menu['menu_title'],
                $sub_menu['capability'],
                $sub_menu['menu_slug'],
                array( &$this, $sub_menu['function'] )
            );
        }
    }


    /**
     * Call back function which is fired when the admin menu page is loaded.
     */
    public function load_template(){
        load_template( plugin_dir_path( dirname( __FILE__ ) ) . 'views/settings.php');
    }


    public function plugin_action_links( $links, $current_plugin_file ){
        $this_plugin_file = dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/plugin.php';

        if ( $current_plugin_file == $this_plugin_file ){
            $links['ajax_login_register_support'] = sprintf('<a href="http://support.zanematthew.com/" title="%1$s">%1$s</a>', esc_attr__('Support', 'ajax_login_register') );
            $links['ajax_login_register_pro'] = sprintf('<a href="http://store.zanematthew.com/downloads/zm-ajax-login-register-pro/" title="%1$s">%1$s</a>', esc_attr__('Pro Version', 'ajax_login_register') );
        }

        return $links;
    }
}
new Admin;