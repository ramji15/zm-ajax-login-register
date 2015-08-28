<?php

Class ALRRegister {

    public function __construct( ZM_Dependency_Container $di ){

        $this->_zm_alr_html = $di->get_instance( 'html', 'ALRHtml', null );
        $this->_zm_alr_helpers = $di->get_instance( 'helpers', 'ALRHelpers', null );
        $this->prefix = 'zm_alr_register';
        add_action( 'zm_alr_init', array( &$this, 'init' ) );

        add_filter( 'zm_alr_register_field_container_classes', array( &$this, 'fieldContainerClasses' ) );

    }

    /**
     * Run various WordPress actions here
     *
     * @since 2.0.0
     */
    public function init(){

        add_action( 'wp_ajax_nopriv_setup_new_user', array( &$this,'setupNewUser' ) );
        add_action( 'wp_ajax_setup_new_user', array( &$this,'setupNewUser' ) );

        add_action( 'wp_ajax_nopriv_validate_username', array( &$this,'validateUsername' ) );
        add_action( 'wp_ajax_validate_username', array( &$this,'validateUsername' ) );

        add_action( 'wp_ajax_nopriv_validate_email', array( &$this,'validateEmail' ) );
        add_action( 'wp_ajax_validate_email', array( &$this,'validateEmail' ) );

        add_action( 'wp_ajax_nopriv_load_register_template', array( &$this, 'load_register_template' ) );
        add_action( 'wp_ajax_load_register_template', array( &$this, 'load_register_template' ) );

        add_shortcode( 'ajax_register', array( &$this, 'shortcode' ) );

        add_action( 'wp_footer', array( &$this, 'footer' ) );

    }


    /**
     * Add the form or show a message via WordPress' shortcode.
     *
     * @since 2.0.0
     */
    public function shortcode(){

        if ( get_option('users_can_register') ) {

            if ( is_user_logged_in() ) {

                $html = sprintf('<p>%s <a href="%s" title="%s">%s</a></p>',
                    __( 'You are already registered', ZM_ALR_TEXT_DOMAIN ),
                    wp_logout_url( site_url() ),
                    __( 'Logout', ZM_ALR_TEXT_DOMAIN ),
                    __( 'Logout', ZM_ALR_TEXT_DOMAIN )
                );

            } else {

                $html = $this->getRegisterForm();

            }

        } else {

            $html = __('Registration is currently closed.', ZM_ALR_TEXT_DOMAIN );

        }

        return $html;
    }


    /**
     * Build the registration form.
     *
     * @since 2.0.0
     *
     */
    public function getRegisterForm(){

        $container_classes = implode( " ", apply_filters( $this->prefix . '_form_container_classes', array(
            ZM_ALR_NAMESPACE . '_form_container',
            $this->prefix . '_form_container'
            ) ) );

        $form_classes = implode( " ", apply_filters( $this->prefix . '_form_classes', array(
            ZM_ALR_NAMESPACE . '_form'
            ) ) );

        $fields_html = $this->_zm_alr_html->buildFormFieldsHtml( array(
            $this->prefix . '_user_name' => array(
                'title' => 'User Name',
                'type' => 'text',
                'extra' => 'autocorrect="none" autocapitalize="none"'
                ),
            $this->prefix . '_email' => array(
                'title' => 'Email',
                'type' => 'email',
                'extra' => 'autocorrect="none" autocapitalize="none"'
                ),
            $this->prefix . '_password' => array(
                'title' => 'Password',
                'type' => 'password',
                'extra' => 'autocorrect="none" autocapitalize="none"'
                ),
            $this->prefix . '_confirm_password' => array(
                'title' => 'Confirm Passowrd',
                'type' => 'password',
                'extra' => 'autocorrect="none" autocapitalize="none"'
                ),
            $this->prefix . '_submit_button' => array(
                'title' => 'Register',
                'type' => 'submit',
                'extra' => 'disabled'
                )
            ), $this->prefix );


        $links_html = $this->_zm_alr_html->buildFormHtmlLinks( array(
            $this->prefix . '_not_a_member' => array(
                'href' => '#',
                'class' => 'already-registered-handle',
                'text' => __( 'Already registered?', ZM_ALR_TEXT_DOMAIN ),
                )
            ), $this->prefix );


        $html = null;
        $html .= '<div class="' . $container_classes . '">';
        $html .= '<form action="javascript://" name="registerform" class="' . $form_classes . '" data-$html ;a=lr_register_security="' . wp_create_nonce( 'setup_new_user' ) . '">';
        $html .= '<div class="form-wrapper">';

        $html .= '<div class="ajax-login-register-status-container">';
        $html .= '<div class="ajax-login-register-msg-target"></div>';
        $html .= '</div>';

        $html .= $fields_html;
        $html .= $links_html;
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;

    }


    /**
     * Handles setting up new user info and passes it to the appropriate.
     *
     * @since 2.0.0
     * @uses check_ajax_referer() http://codex.wordpress.org/Function_Reference/check_ajax_referer
     *
     * @param $login
     * @param $password
     * @param $email
     * @param $is_ajax
     *
     * @return Status (array|json) JSON if is_ajax is true, else as array
     */
    public function setupNewUser( $args=null, $is_ajax=true ) {

        // if ( $is_ajax ) check_ajax_referer('setup_new_user','security');

        // TODO consider using wp_generate_password( $length=12, $include_standard_special_chars=false );
        // and wp_mail the users password asking to change it.

        $user = apply_filters( $this->prefix . '_setup_new_user_args', wp_parse_args( $args, array(
            'user_login' => empty( $_POST['zm_alr_register_user_name'] ) ? '' : $_POST['zm_alr_register_user_name'],
            'email' => empty( $_POST['zm_alr_register_email'] ) ? '' : $_POST['zm_alr_register_email'],
            'user_pass' => empty( $_POST['zm_alr_register_confirm_password'] ) ? '' : $_POST['zm_alr_register_confirm_password']
            ) ) );


        $valid = array(
            'email' => $this->validateEmail( $user['email'], false ),
            'username' => $this->validateUsername( $user['user_login'], false )
        );


        $user_id = null;
        $status = null;

        $pre_status = apply_filters( $this->prefix . '_submit_pre_status_error', $_POST );
        if ( isset( $pre_status['code'] ) ){

            $status = $pre_status;

        }

        elseif ( $valid['username']['code'] == 'error' ){

            $status = $this->_zm_alr_helpers->status('invalid_username');

        }

        elseif ( $valid['email']['code'] == 'error' ){

            $status = $this->_zm_alr_helpers->status('invalid_username');

        }

        else {

            if ( ! isset( $status['code'] ) ){

                $user_id = $this->_zm_alr_helpers->createUser( $user, $this->prefix );

                if ( $user_id == false ){

                    $status = $this->_zm_alr_helpers->status('invalid_username'); // invalid user

                } else {

                    $status = $this->_zm_alr_helpers->status('success_registration'); // success

                    // Allow to void this!
                    $did_signon = apply_filters( $this->prefix . '_do_signon', true );
                    if ( $did_signon === true ){
                        $this->signOn( $user );
                    }
                    $status['id'] = $user_id;

                }

                $status = array_merge( $status, $this->registerRedirect( $user['user_login'] ) );
            }
        }

print_r( $user );
print_r( $status );
var_dump($did_signon);

die("\nf");
        if ( $is_ajax ) {

            wp_send_json( $status );

        } else {

            return $status;

        }
    }


    public function signOn( $user=null ){

        $wp_signon = wp_signon( array(
            'user_login' => $user['user_login'],
            'user_password' => $user['user_pass'],
            'remember' => true ),
        false );
        wp_new_user_notification( $user_id );
        do_action( $this->prefix . '_after_signon', $user_id );
        $status = apply_filters( $this->prefix . '_signon_status', $status, $user );

    }


    /**
     * Process request to pass variables into WordPress' validate_username();
     *
     * @since 2.0.0
     * @uses validate_username()
     * @param $username (string)
     * @param $is_ajax (bool) Process as an AJAX request or not.
     */
    public function validateUsername( $username=null, $is_ajax=true ) {


        $username = empty( $_POST['zm_alr_register_user_name'] ) ? esc_attr( $username ) : $_POST['zm_alr_register_user_name'];

        if ( validate_username( $username ) ) {
            $user_id = username_exists( $username );
            if ( $user_id ){
                $msg = $this->_zm_alr_helpers->status('username_exists');
            } else {
                $msg = $this->_zm_alr_helpers->status('valid_username');
            }
        } else {
            $msg = $this->_zm_alr_helpers->status('invalid_username');
        }

        if ( $is_ajax ){
            wp_send_json( $msg );
        } else {
            return $msg;
        }
    }


    /**
     * Check if an email is "valid" using PHPs filter_var & WordPress
     * email_exists();
     *
     * @since 2.0.0
     * @param $email (string) Emailt to be validated
     * @param $is_ajax (bool)
     * @todo check ajax refer
     */
    public function validateEmail( $email=null, $is_ajax=true ) {

        $email = is_null( $email ) ? $email : $_POST['zm_alr_register_email'];

        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $msg = $this->_zm_alr_helpers->status('email_invalid');
        } else if ( email_exists( $email ) ){
            $msg = $this->_zm_alr_helpers->status('email_in_use');
        } else {
            $msg = $this->_zm_alr_helpers->status('email_valid');
        }

        if ( $is_ajax ){
            wp_send_json( $msg );
        } else {
            return $msg;
        }
    }


    /**
     * Load the login form via an AJAX request.
     *
     * @since 2.0.0
     * @package AJAX
     */
    public function load_register_template(){

        // check_ajax_referer( $_POST['referer'], 'security' );
        $msg = $this->getRegisterForm();
        wp_send_json_success( $msg );

    }


    /**
     * Add the HTML for the register dialog via wp_footer.
     *
     * @since 2.0.0
     */
    public function footer(){

        $classes = implode( ' ', apply_filters( $this->prefix . '_dialog_class', array(
            $this->prefix . '_dialog',
            ZM_ALR_NAMESPACE . '_dialog'
            ) ) );

        ?>
        <?php
        /**
         * Markup needed for jQuery UI dialog, our form is actually loaded via AJAX
         */
        ?><div id="ajax-login-register-dialog" class="<?php echo $classes; ?>" title="<?php _e( 'Register',  ZM_ALR_TEXT_DOMAIN ); ?>" data-security="<?php print wp_create_nonce( 'register_form' ); ?>" style="display: none;">
            <div id="ajax-login-register-target" class="ajax-login-register-dialog"><?php _e( 'Loading...', ZM_ALR_TEXT_DOMAIN ); ?></div>
            <?php do_action( $this->prefix . '_after_dialog' ); ?>
        </div>
    <?php }


    public function registerRedirect( $user_login=null ){
        // Since this is handled via an AJAX request $wp->request is always empty
        // @todo Submit to core
        // global $wp;
        // $tmp = trailingslashit( add_query_arg( '', '', site_url( $wp->request ) ) );
        $current_url = empty( $_SERVER['HTTP_REFERER'] ) ? site_url( $_SERVER['REQUEST_URI'] ) : $_SERVER['HTTP_REFERER'];
        $redirect['redirect_url'] = apply_filters( $this->prefix . '_redirect_url', $current_url, $user_login );

        return $redirect;
    }


    public function fieldContainerClasses( $classes ){
        // print_r($classes);
        // zm_alr_form_field_container zm_alr_submit_container zm_alr_register_submit_container

    }
}

/**
 * Once plugins are loaded init our class
 */
function zm_alr_plugins_loaded_register(){

    new ALRRegister( new ZM_Dependency_Container( null ) );

}
add_action( 'plugins_loaded', 'zm_alr_plugins_loaded_register' );