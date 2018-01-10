<?php
/**
 * Plugin Name:     Easy Digital Downloads - Math Verification
 * Plugin URI:      https://sellcomet.com/downloads/math-verification
 * Description:     Adds a simple math CAPTCHA to the Easy Digital Downloads registration form.
 * Version:         1.0.0
 * Author:          Sell Comet
 * Author URI:      https://sellcomet.com
 * Text Domain:     edd-math-verification
 * Domain Path:     languages
 *
 * Math Verification is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Easy Digital Downloads is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy Digital Downloads. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package         EDD\MathVerification
 * @category        Core
 * @author          David Sherlock
 * @version         1.0.0
 * @copyright       Copyright (c) Sell Comet
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Math_Verification' ) ) {

    /**
     * Main EDD_Math_Verification class
     *
     * @since       1.0.0
     */
    class EDD_Math_Verification {

        /**
         * @var         EDD_Math_Verification $instance The one true EDD_Math_Verification
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_Math_Verification
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Math_Verification();
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_MATH_VERIFICATION_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_MATH_VERIFICATION_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_MATH_VERIFICATION_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {

            if ( is_admin() ) {
              // Register extension subsection and settings
              add_filter( 'edd_settings_sections_extensions', array( $this, 'settings_subsection' ), 1, 1 );
              add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

              // Handle licensing
              if( class_exists( 'EDD_License' ) ) {
                  $license = new EDD_License( __FILE__, 'Math Verification', EDD_MATH_VERIFICATION_VER, 'Sell Comet', null, 'https://sellcomet.com/', 410 );
              }
            }

            // Render the math verification form fields
            add_action( 'edd_register_form_fields_before_submit', array( $this, 'render_math_fields' ), 0 );

            // Verify the math verification field submission
            add_action( 'edd_process_register_form', array( $this, 'verify_math' ) );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_MATH_VERIFICATION_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_math_verification_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-math-verification' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-math-verification', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-plugin-name/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-plugin-name/ folder
                load_textdomain( 'edd-math-verification', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-plugin-name/languages/ folder
                load_textdomain( 'edd-math-verification', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-math-verification', false, $lang_dir );
            }
        }

        /**
         * Registers the extension settings subsection
         *
         * @access      public
         * @since       1.0.0
         * @param       array $sections The sections
         * @return      array Sections with new subsection added
         */
        public function settings_subsection( $sections ) {
          $sections['math_verification'] = __( 'Math Verification', 'edd-math-verification' );
          return $sections;
        }

        /**
         * Add extension settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {

          $math_verification_settings = array(
            array(
              'id'          => 'math_verification_display_settings_header',
              'name'        => '<strong>' . __( 'Display Settings', 'edd-math-verification' ) . '</strong>',
              'desc'        => '',
              'type'        => 'header',
              'size'        => 'regular',
            ),
            array(
              'id'          => 'math_verification_label',
              'name'        => __( 'Math Label', 'edd-math-verification' ),
              'desc'        => __( 'Enter the text shown next to the simple math verification field. Default: "What does this equal?:"', 'edd-math-verification' ),
              'type'        => 'text',
            ),
            array(
              'id'          => 'math_verification_general_settings_header',
              'name'        => '<strong>' . __( 'General Settings', 'edd-math-verification' ) . '</strong>',
              'desc'        => '',
              'type'        => 'header',
              'size'        => 'regular',
            ),
            array(
              'id'          => 'math_verification_highest',
              'name'        => __( 'Highest Number', 'edd-math-verification' ),
              'desc'        => __( 'Enter the highest number the pseudo random generator should use. Default is 10.', 'edd-math-verification' ),
              'type'        => 'number',
              'size'        => 'small',
              'std'         => '10',
              'min'         => '0',
            ),
          );

          $math_verification_settings = apply_filters( 'edd_math_verification_settings', $math_verification_settings );

          if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
            $math_verification_settings = array( 'math_verification' => $math_verification_settings );
          }

          return array_merge( $settings, $math_verification_settings );
        }

        /**
         * Render the math verification fields
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        function render_math_fields() {
        	$math_one = rand( 0, (int) edd_get_option( 'math_verification_highest', 10 ) );
        	$math_two = rand( 0, (int) edd_get_option( 'math_verification_highest', 10 ) );
          $label = edd_get_option( 'math_verification_label', __( 'What does this equal?:', 'edd-math-verification' ) );
        	ob_start(); ?>
        		<p>
        			<input id="edd-math-1" type="hidden" name="edd-math-1" value="<?php echo esc_attr( $math_one ); ?>"/>
        			<input id="edd-math-2" type="hidden" name="edd-math-2" value="<?php echo esc_attr( $math_two ); ?>"/>
        			<label for="edd-math-answer"><?php echo esc_attr( $label ) . ' ' . esc_attr( $math_one ) . ' + ' . esc_attr( $math_two ); ?></label>
        			<input id="edd-math-answer" class="required edd-input" type="text" name="edd-math-answer" value=""/>
        		</p>
        		<?php
        	echo ob_get_clean();
        }

        /**
         * Verify form submission math
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function verify_math() {
          do_action( 'edd_math_verification_verify_math_before' );

        	if( ! isset( $_POST['edd-math-1'] ) || ! isset( $_POST['edd-math-2'] ) || ! isset( $_POST['edd-math-answer'] ) ) {
                edd_set_error( 'do_math', __( 'Please answer the math question', 'edd-math-verification' ) );
        	}

        	if( ! is_numeric( $_POST['edd-math-1'] ) || ! is_numeric( $_POST['edd-math-2'] ) || ! is_numeric( $_POST['edd-math-answer'] ) ) {
                edd_set_error( 'do_math', __( 'Please only enter numbers in the math fields', 'edd-math-verification' ) );
        	}

        	$result = sanitize_text_field( $_POST['edd-math-1'] ) + sanitize_text_field( $_POST['edd-math-2'] );
        	$answer = sanitize_text_field( $_POST['edd-math-answer'] );

        	if( $result != $answer ) {
                edd_set_error( 'incorrect_math', __( 'Your math is incorrect', 'edd-math-verification' ) );
        	}

          do_action( 'edd_math_verification_verify_math_after' );
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Math_Verification
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Math_Verification The one true EDD_Math_Verification
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function EDD_Math_Verification_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_Math_Verification::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_Math_Verification_load' );
