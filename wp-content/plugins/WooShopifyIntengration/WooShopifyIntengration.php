<?php

/*
*  Plugin Name: Woo Shopify Integration
*  Description: This is a plugin to integrate Shopify with woocommerce
*  Version: 1.0.0
*  
*/




if(!defined('ABSPATH'))
{
    die();
}

if(!class_exists('SimShopifyIntegration'))
{
   

  add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');
 
  function enqueue_custom_admin_script() {
    wp_enqueue_script('custom-admin-script', plugin_dir_url(__FILE__) . 'includes/CityPlumbing-admin.js', array('jquery'), null, true);
    wp_enqueue_style('custom-admin-style', plugin_dir_url(__FILE__) . 'includes/CityPlumbing-admin.css');
   }

    class SimShopifyIntegration
	{

		public function __construct()
		{
			define('PLUGIN_PATH', plugin_dir_path(__FILE__));

			require_once(PLUGIN_PATH . '/vendor/autoload.php');
			
		}

		public function initializer()
		{
			include_once(PLUGIN_PATH . 'includes/utilities.php');	
			include_once(PLUGIN_PATH . 'includes/options-page.php');
			include_once(PLUGIN_PATH . 'includes/integration.php');	
			include_once(PLUGIN_PATH . 'includes/importer.php');
		}

	}




	$IntegrationObj = new SimShopifyIntegration;

	$integrator = $IntegrationObj->initializer();
}