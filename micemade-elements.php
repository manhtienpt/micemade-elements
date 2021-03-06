<?php
/**
 * Plugin Name: Micemade Elements
 * Description: Addon plugin with custom elements for Elementor, created by Micemade. Elementor plugin required.
 * Plugin URI: https://github.com/Micemade/micemade-elements/
 * Version: 0.6.1
 * Author: micemade
 * Author URI: http://micemade.com
 * Text Domain: micemade-elements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Micemade_Elements {

	private static $instance = null;
	
	public static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
	
	public function init() {
		
		if( self::$instance->Elementor_plugin_activation_check() ) {
			
			define('MICEMADE_ELEMENTS_DIR', plugin_dir_path( __FILE__ ) );
			define('MICEMADE_ELEMENTS_URL', plugin_dir_url( __FILE__ ) );
			
			self::$instance->activation_checks();
			
			self::$instance->includes();

			// Add "Micemade Elements" widgets category
			add_action( 'elementor/init', array( $this, 'add_widgets_category' ) );
			// Register "Micemade elements" widgets
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
			// Add custom controls to Elementor section
			add_action( 'elementor/element/after_section_end', array( $this, 'custom_section_controls' ), 10, 5 );

			
			add_action('plugins_loaded', array( self::$instance, 'load_plugin_textdomain') );
			
			add_action( 'init', array( self::$instance, 'mega_menu_post_type' ) );
			
			// Enqueue script and styles for ADMIN
			//add_action( 'admin_enqueue_scripts', array( self::$instance, 'micemade_elements_admin_js_css' ) );
			
			// Enqueue scripts and styles for frontend
			add_action( 'wp_enqueue_scripts', array( self::$instance,'micemade_elements_styles') );
			add_action( 'wp_enqueue_scripts', array( self::$instance,'micemade_elements_scripts') );
			
			self::$instance->updater();
		
		}else {
			
			add_action( 'admin_notices', array( self::$instance ,'admin_notice') ); 
		}
	
		
	}
	

	private function Elementor_plugin_activation_check() {
		
		$micemade_elements_is_active = false;
		
		//if ( defined( 'ELEMENTOR_PATH' ) && class_exists( 'Elementor\Widget_Base' ) ) {
		if ( in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			
			$micemade_elements_is_active = true; 
			define('ELEMENTOR_IS_ACTIVE', true );
		}else{
			define('ELEMENTOR_IS_ACTIVE', false );
		}
		
		return $micemade_elements_is_active;
		
	}
	
	
	private function activation_checks() {
		// VARIOUS PLUGINS ACTIVATION CHECKS:
		require_once MICEMADE_ELEMENTS_DIR . 'includes/plugins.php';
		
	}
	
	public function add_widgets_category() {
		
		$elementsManager = Elementor\Plugin::instance()->elements_manager;
		$elementsManager->add_category(
			'micemade_elements',
			array(
				'title' => __( 'Micemade Elements', 'micemade-elements' ),
				'icon' =>  'eicon-font'
			),
			1
		);
	}

	public function widgets_registered() {

		require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-posts-grid.php';
		require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-buttons.php';
		
		// Revolution Slider plugin
		if( MICEMADE_ELEMENTS_REVSLIDER_ON ) {
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-rev-slider.php';	
		}
		// WooCommerce plugin
		if( MICEMADE_ELEMENTS_WOO_ACTIVE ) {
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-wc-categories.php';
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-wc-products.php';
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-wc-products-slider.php';
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-wc-single-product.php';
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-wc-products-tabs.php';
		}
		// Contact Form 7 plugin
		if( MICEMADE_ELEMENTS_CF7_ON ) {
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-contact-form-7.php';	
		}
		// MailChimp 4 WP plugin
		if( MICEMADE_ELEMENTS_MC4WP_ON ) {
			require_once MICEMADE_ELEMENTS_DIR . 'widgets/micemade-mailchimp.php';
		}
	}

	public function custom_section_controls( $element, $section_id, $args ) {
		// @var \Elementor\Element_Base $element
		if ( 'section' === $element->get_name() && 'section_typo' === $section_id ) {
		
			   $element->start_controls_section(
				   'section_sticky',
				   [
					   'tab' => \Elementor\Controls_Manager::TAB_STYLE,
					   'label' => __( 'Sticky section', 'micemade-elements' ),
				   ]
			   );
		
			   $element->add_control(
				   'sticky',
				   [
					   'label' => __( 'Section sticky method', 'micemade-elements' ),
					   'type' => \Elementor\Controls_Manager::SELECT,
					   'default'      => 'not-sticked',
					   'options'      => array( 
						   'not-sticked' => __( 'Not sticked', 'micemade-elements' ),
						   'sticked-header' => __( 'Sticked header', 'micemade-elements' ),
						   'sticked-inner' => __( 'Sticked inside column', 'micemade-elements' ),
						   'sticked-footer' => __( 'Sticked footer', 'micemade-elements' ),
					   ),
					   'prefix_class' => 'selection-is-',
				   ]
			   );
		
			   $element->end_controls_section();
		   }
	}
	
	public function includes () {
		
		include( MICEMADE_ELEMENTS_DIR . "/includes/Parsedown.php" );
		include( MICEMADE_ELEMENTS_DIR . "/includes/admin.php" );
		include( MICEMADE_ELEMENTS_DIR . "/includes/ajax_posts.php" );
		include( MICEMADE_ELEMENTS_DIR . "/includes/helpers.php" );
		include( MICEMADE_ELEMENTS_DIR . "/includes/wc-functions.php" );
		
	}
	
	
	/**
	 * Load Plugin Text Domain
	 *
	 * Looks for the plugin translation files in certain directories and loads
	 * them to allow the plugin to be localised
	 */
	public function load_plugin_textdomain() {
					
		$lang_dir = apply_filters('micemade_elements_lang_dir', trailingslashit( MICEMADE_ELEMENTS_DIR . 'languages') );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'micemade-elements' );
		$mofile = sprintf('%1$s-%2$s.mo', 'micemade-elements', $locale );

		// Setup paths to current locale file
		$mofile_local = $lang_dir . $mofile;

		if ( file_exists( $mofile_local ) ) {
			// Look in the /wp-content/plugins/micemade-elements/languages/ folder
			load_textdomain( 'micemade-elements', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'micemade-elements', false, $lang_dir );
		}

		return false;
	}
	
	// ENQUEUE STYLES
	public function micemade_elements_styles () {
		
		// CSS styles:
		wp_register_style( 'micemade-elements', MICEMADE_ELEMENTS_URL . 'assets/css/micemade-elements.css' );
		wp_enqueue_style( 'micemade-elements' );
		
	}
	
	// ENQUEUE SCRIPTS
	public function micemade_elements_scripts () {
		
		// JS scripts:
		wp_register_script('micemade-elements-js', MICEMADE_ELEMENTS_URL .'assets/js/micemade-elements.min.js');
		wp_enqueue_script('micemade-elements-js', MICEMADE_ELEMENTS_URL .'assets/js/micemade-elements.min.js', array('jQuery'), '1.0', true);
		
		$ajaxurl = '';
		if( MICEMADE_ELEMENTS_WPML_ON ){
			$ajaxurl .= admin_url( 'admin-ajax.php?lang=' . ICL_LANGUAGE_CODE );
		} else{
			$ajaxurl .= admin_url( 'admin-ajax.php');
		}
		 
		wp_localize_script( 'micemade-elements-js', 'micemadeJsLocalize', array(
			'ajaxurl'		=> $ajaxurl,
			'loadingposts'  => esc_html__('Loading posts ...', 'micemade-elements'),
			'noposts'		=> esc_html__('No more posts found', 'micemade-elements'),
			'loadmore'		=> esc_html__('Load more posts', 'micemade-elements')
		) );
		
	}
	
	public function ajax_url_var() {
		echo '<script type="text/javascript">var micemade_elements_ajaxurl = "'. admin_url( "admin-ajax.php" ) .'"</script>';
	}
	
	public function admin_notice() {
		
		$class = "error updated settings-error notice is-dismissible";
		$message = __( '"Micemade elements" plugin is not effective without "Elementor" plugin activated. Either install and activate  "Elementor" plugin or deactivate "Micemade elements".', 'micemade-elements' );
        echo "<div class=\"$class\"><p>$message</p></div>"; 
		
	}
	
	public function mega_menu_post_type() {
		
		// list of Micemade themes compatible with MM Mega menu CPT
		$micemade_themes	= array( 'natura', 'beautify', 'ayame', 'inspace', 'cloth','goodfood','lillabelle' );
		
		if( is_child_theme() ) {
			$parent_theme		= wp_get_theme();
			$active_theme		= $parent_theme->get( 'Template' );
		}else{
			$active_theme		= get_option( 'template' );
		}

		if( in_array( $active_theme, $micemade_themes ) && ELEMENTOR_IS_ACTIVE ) {
			
			// include file with Custom Post Types registration:
			// MM Mega Menu, MM Header, MM Footer
			include( MICEMADE_ELEMENTS_DIR . "/includes/cpt.php" );
			
		}
		
		return false;
		
	}
	
	function updater() {
			
		require_once( plugin_dir_path( __FILE__ ) . 'github_updater.php' );
		if ( is_admin() ) {
			new Micemade_GitHub_Plugin_Updater( __FILE__, 'Micemade', "micemade-elements" );
		}
	}

	
} // end class Micemade_Elements

Micemade_Elements::get_instance()->init();