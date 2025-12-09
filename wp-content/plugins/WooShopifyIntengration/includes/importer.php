<?php
/**
 * Shopify to WooCommerce Product Importer
 */


/*
*
* Webhook generator
*/





// Initialize the importer


class Webhook_Generator {

    private $namespace;
    private $hookEndpoint; 
    private $webhookImporter;
	private $webhook_secret;
	


    public function __construct(Shopify_WooCommerce_Importer $importer) {
        $this->namespace = "woo-shopify-integration/v2";
        $this->hookEndpoint = "shopify-connector"; // Use the corrected property name
        $this->webhookImporter = $importer;

       
		 add_action('init',array($this,'load_webhook_secret' ) );
    }


    public function load_webhook_secret(){
       
           $secret = carbon_get_theme_option("shopify_webhook_secret");

           if($secret){
                $this->webhook_secret = $secret;
          }

    }

    public function webhook_importer($productData) {

        $this->webhookImporter->import_product($productData);
    }

 
    /**
     * Registers the custom REST API route.
     */
    public function register_webhook() {
        // 'register_rest_route' needs three arguments:
        // 1. The namespace (e.g., 'my-plugin/v1')
        // 2. The route path (e.g., '/my-endpoint/')
        // 3. An array of arguments defining methods, permissions, and the callback function.
        $isRegister = register_rest_route(
            $this->namespace,
            '/' . $this->hookEndpoint, 
            array(
                'methods'             => 'POST', // Webhooks usually use POST requests
                'callback'            => array( $this, 'handle_webhook_data' ), // Callback function in this class
                'permission_callback' => '__return_true', // Allows public access (necessary for webhooks)
            )
        );

      
    }

   
    public function get_webhook_url() {

        return rest_url($this->namespace. "/" . $this->hookEndpoint);
        
    }

  public function handle_webhook_data(WP_REST_Request $request) {

    $web_content = $request->get_body();

    // 1. Check if body is empty
    if (empty($web_content)) {
        return new WP_Error(
            'missing_product',
            'Product data is missing',
            array('status' => 400)
        );
    }

    
    $hash = "sha256";

    // 2. Get Shopify HMAC header
    $receivedHash = $request->get_header('X-Shopify-Webhook-Signature');

    if (empty($receivedHash)) {
        return new WP_Error(
            'missing_signature',
            'Signature is missing',
            array('status' => 400)
        );
    }

    // 3. Calculate expected hash
    $calculateHash = hash_hmac($hash, $web_content, $this->webhook_secret);
	
	/*
	* $calculateHash = base64_encode(hash_hmac($hash, $web_content, $this->webhook_secret, true));
	* Check to see if shopify webhook sending Signature as hex
	*/
	

    // 4. Verify signature
    if (!hash_equals($calculateHash, $receivedHash)) {
        return new WP_Error(
            'webhook_failed',
            'Signature did not match',
            array('status' => 401)
        );
    }

    // 5. Decode payload
    $payload = json_decode($web_content, true);

    if (empty($payload['product'])) {
        return new WP_Error(
            'missing_product_data',
            'Product data is missing in payload',
            array('status' => 400)
        );
    }

    // 6. Process product
    $product_data = $payload['product'];

    $this->webhook_importer($product_data);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Webhook received and verified successfully!'
    ], 200);
}



      public function render_url_display_html() {
        $url = $this->get_webhook_url();

        return $url;


        ?>
        
        <?php
    }
}


/*
 * Initialization function hooked into 'rest_api_init'.*/
 
function init_webhook() {

   global $shopify_webhook;
   
   if($shopify_webhook){
	   
	   return $shopify_webhook->register_webhook();
	   
      }
	  return '';
    
} 
add_action("rest_api_init", 'init_webhook');



class Shopify_WooCommerce_Importer {
    
    private $shop_url;
    private $api_key;
    private $api_token;
    private $api_version = '2024-01';
    private $webhook_url;
	private $webhook_data;
    private $webhook_secret;

    public function __construct() {
 
          //$this->webhook_url = $webhook->render_url_display_html();
		  //$this->$webhook_data = $webhook->handle_webhook_data();
		  
		  
		
        add_action('admin_menu', array($this, 'add_importer_page'), 99);
        add_action('wp_ajax_shopify_import_products', array($this, 'ajax_import_products'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
	
	
	
	public function set_webhook_url($url) {
		
    $this->webhook_url = $url;
	
	
   }
    
    /**
     * Get Shopify credentials from Carbon Fields
     */
    private function get_credentials() {
        $this->shop_url = carbon_get_theme_option('shopify_shop_url');
        $this->api_key = carbon_get_theme_option('shopify_api_key');
        $this->api_token = carbon_get_theme_option('shopify_access_token');
    }
    
    /**
     * Add importer page to admin menu
     */
    
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'shopify-integration_page_shopify-importer') {
              return;

        }
        
        wp_enqueue_script('shopify-importer', plugins_url('js/importer.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('shopify-importer', 'shopifyImporter', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('shopify_import_nonce')
        ));
    }


    public function add_importer_page() {
            add_submenu_page(
                'sim-shopify-integration',
                'Shopify Importer',
                'Product Importer',
                'manage_options',        // Only admins can import products
                'shopify-importer',
                array($this, 'render_importer_page')
            );
        }
    
    /**
     * Render importer page
     */
    public function render_importer_page() {
        ?>
        <div class="wrap woocommerce">
            <h1>Shopify to WooCommerce Importer</h1>
            
            <div class="card">
                <h2>Import Products from Shopify</h2>
                <p>This will import or update WooCommerce products from your Shopify store.</p>
                
                <div style="margin: 20px 0;">
                    <label>
                        <input type="checkbox" id="update-existing" checked> 
                        Update existing products
                    </label>
                    <br>
                    <label>
                        <input type="checkbox" id="import-images" checked> 
                        Import product images
                    </label>
                </div>
                
                <button id="start-import" class="button button-primary">Start Import</button>
                
                <div id="import-progress" style="display:none; margin-top: 20px;">
                    <div class="progress-bar" style="background: #ddd; height: 30px; border-radius: 4px; overflow: hidden;">
                        <div id="progress-fill" style="background: #7f54b3; height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <p id="progress-text" style="margin-top: 10px;">Importing products...</p>

                    
                </div>
                
                <div id="import-results" style="display:none; margin-top: 20px;"></div>

                <br/>
                <br/>

                 <h3>Shopify Webhook URL</h3>
                 <p>Use this URL for your Shopify connection:</p>

                <?php 
                
                   $urlString = esc_url($this->webhook_url);
                   
                   echo  '<input type="text" value="' . $urlString . '" class="large-text code" readonly="readonly" onclick="this.select();" />
                            <br/> <p class="description">Copy and paste this URL into the Shopify Webhooks configuration.</p>
                   ';
                
                ?>
                
            </div>
        </div>
        <?php

    }
    
    /**
     * Make API request to Shopify
     */
    private function make_shopify_request($endpoint, $params = array()) {
    $this->get_credentials();
    
    if (empty($this->shop_url) || empty($this->api_token)) {
        return new WP_Error('missing_credentials', 'Shopify credentials are not configured.');
    }
    
    // Clean shop URL
    $shop_url = str_replace(array('https://', 'http://'), '', $this->shop_url);
    $shop_url = rtrim($shop_url, '/');
    
    // Build URL for local API
    $url = sprintf(
        'http://%s/%s',
        $shop_url,
        ltrim($endpoint, '/')
    );
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    error_log('=== SHOPIFY REQUEST ===');
    error_log('URL: ' . $url);
    error_log('API Key: ' . $this->api_key);
    error_log('Token: ' . $this->api_token);
    error_log('Origin: ' . home_url());
    
    // Make request with correct headers for your Remix app
    $response = wp_remote_get($url, array(
        'headers' => array(
            'x-api-key' => $this->api_key,
            'x-token' => $this->api_token,
            'origin' => home_url(),
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        error_log('ERROR: ' . $response->get_error_message());
        return $response;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    error_log('Status Code: ' . $status_code);
    error_log('Response Body: ' . substr($body, 0, 500)); // First 500 chars
    error_log('=== END REQUEST ===');
    
    $data = json_decode($body, true);
    
    return $data;
}
    /**
     * AJAX handler for importing products
     */
    public function ajax_import_products() {
        check_ajax_referer('shopify_import_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $update_existing = isset($_POST['update_existing']) ? $_POST['update_existing'] === 'true' : true;
        $import_images = isset($_POST['import_images']) ? $_POST['import_images'] === 'true' : true;
        $limit = 50;
        
        // Fetch products from Shopify
       /* $result = $this->make_shopify_request('products.json', array(
            'limit' => $limit,
            'page' => $page
        ));*/

         $result = $this->make_shopify_request('', array(
            'limit' => $limit,
            'page' => $page
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        if (empty($result['products'])) {
            wp_send_json_success(array(
                'completed' => true,
                'message' => 'Import completed successfully!'
            ));
        }
        
        // Import products
        $imported = 0;
        $updated = 0;
        foreach ($result['products'] as $product) {
            $result = $this->import_product($product, $update_existing, $import_images);
            if ($result) {
                if ($result === 'updated') {
                    $updated++;
                } else {
                    $imported++;
                }
            }
        }
        
        wp_send_json_success(array(
            'completed' => count($result['products']) < $limit,
            'imported' => $imported,
            'updated' => $updated,
            'page' => $page
        ));
    }
    
    /**
     * Import a single product to WooCommerce
     */
    private function import_product($shopify_product, $update_existing = true, $import_images = true) {
        // Check if product already exists
        $existing_id = $this->get_product_by_shopify_id($shopify_product['id']);
        
        if ($existing_id && !$update_existing) {
            return false;
        }
        
        // Create or update WooCommerce product
        if ($existing_id) {
            $product = wc_get_product($existing_id);
            $is_update = true;
        } else {
            // Determine if product has variants
            $has_variants = !empty($shopify_product['variants']) && count($shopify_product['variants']) > 1;
            $product = $has_variants ? new WC_Product_Variable() : new WC_Product_Simple();
            $is_update = false;
        }
        
        // Set basic product data
        $product->set_name($shopify_product['title']);
        $product->set_description($shopify_product['body_html']);
        $product->set_status($shopify_product['status'] === 'active' ? 'publish' : 'draft');
        
        // Set SKU from first variant if available
        if (!empty($shopify_product['variants'][0]['sku'])) {
            $product->set_sku($shopify_product['variants'][0]['sku']);
        }
        
        // Set price for simple products
        if (!($product instanceof WC_Product_Variable)) {
            $variant = $shopify_product['variants'][0];
            $product->set_regular_price($variant['price']);
            
            if (isset($variant['compare_at_price']) && $variant['compare_at_price'] > $variant['price']) {
                $product->set_sale_price($variant['price']);
                $product->set_regular_price($variant['compare_at_price']);
            }
            
            // Set stock
            $product->set_manage_stock(true);
            $product->set_stock_quantity($variant['inventory_quantity']);
            $product->set_stock_status($variant['inventory_quantity'] > 0 ? 'instock' : 'outofstock');
        }
        
        // Save product
        $product_id = $product->save();
        
        // Store Shopify ID and other meta data
        carbon_set_post_meta($product_id, 'shopify_product_id', $shopify_product['id']);
        carbon_set_post_meta($product_id, 'shopify_handle', $shopify_product['handle']);
        carbon_set_post_meta($product_id, 'shopify_vendor', $shopify_product['vendor']);
        carbon_set_post_meta($product_id, 'shopify_product_type', $shopify_product['product_type']);
        
        // Set product tags
        if (!empty($shopify_product['tags'])) {
            $tags = is_array($shopify_product['tags']) ? $shopify_product['tags'] : explode(',', $shopify_product['tags']);
            wp_set_object_terms($product_id, array_map('trim', $tags), 'product_tag');
        }
        
        // Set product category from product type
        if (!empty($shopify_product['product_type'])) {
            $term = get_term_by('name', $shopify_product['product_type'], 'product_cat');
            if (!$term) {
                $term = wp_insert_term($shopify_product['product_type'], 'product_cat');
                if (!is_wp_error($term)) {
                    $term = get_term($term['term_id'], 'product_cat');
                }
            }
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($product_id, array($term->term_id), 'product_cat');
            }
        }
        
        // Handle variants for variable products
        if ($product instanceof WC_Product_Variable && count($shopify_product['variants']) > 1) {
            $this->import_product_variants($product_id, $shopify_product['variants']);
        }
        
        // Import images
        if ($import_images) {
            $this->import_product_images($product_id, $shopify_product);
        }
        
        // Clear cache
        wc_delete_product_transients($product_id);
        
        return $is_update ? 'updated' : 'created';
    }
    
    /**
     * Import product variants
     */
    private function import_product_variants($product_id, $variants) {
        $product = wc_get_product($product_id);
        
        // Create attributes from variants
        $attributes_data = array();
        $variant_options = array();
        
        foreach ($variants as $variant) {
            if (!empty($variant['option1'])) {
                $variant_options['option1'][] = $variant['option1'];
            }
            if (!empty($variant['option2'])) {
                $variant_options['option2'][] = $variant['option2'];
            }
            if (!empty($variant['option3'])) {
                $variant_options['option3'][] = $variant['option3'];
            }
        }
        
        // Create attributes
        $position = 0;
        foreach ($variant_options as $key => $values) {
            $values = array_unique($values);
            $attribute = new WC_Product_Attribute();
            $attribute->set_name(ucfirst($key));
            $attribute->set_options($values);
            $attribute->set_position($position++);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $attributes_data[] = $attribute;
        }
        
        $product->set_attributes($attributes_data);
        $product->save();
        
        // Create variations
        foreach ($variants as $variant) {
            $this->create_product_variation($product_id, $variant);
        }
    }
    
    /**
     * Create a product variation
     */
    private function create_product_variation($product_id, $variant_data) {
        // Check if variation exists
        $existing_variation_id = $this->get_variation_by_shopify_id($variant_data['id']);
        
        if ($existing_variation_id) {
            $variation = new WC_Product_Variation($existing_variation_id);
        } else {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
        }
        
        // Set variation attributes
        $attributes = array();
        if (!empty($variant_data['option1'])) {
            $attributes['option1'] = $variant_data['option1'];
        }
        if (!empty($variant_data['option2'])) {
            $attributes['option2'] = $variant_data['option2'];
        }
        if (!empty($variant_data['option3'])) {
            $attributes['option3'] = $variant_data['option3'];
        }
        $variation->set_attributes($attributes);
        
        // Set price
        $variation->set_regular_price($variant_data['price']);
        if (isset($variant_data['compare_at_price']) && $variant_data['compare_at_price'] > $variant_data['price']) {
            $variation->set_sale_price($variant_data['price']);
            $variation->set_regular_price($variant_data['compare_at_price']);
        }
        
        // Set SKU
        if (!empty($variant_data['sku'])) {
            $variation->set_sku($variant_data['sku']);
        }
        
        // Set stock
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity($variant_data['inventory_quantity']);
        $variation->set_stock_status($variant_data['inventory_quantity'] > 0 ? 'instock' : 'outofstock');
        
        $variation_id = $variation->save();
        
        // Store Shopify variant ID
        carbon_set_post_meta($variation_id, 'shopify_variant_id', $variant_data['id']);
        
        return $variation_id;
    }
    
    /**
     * Import product images
     */
    private function import_product_images($product_id, $shopify_product) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $gallery_images = array();
        
        // Import all images
        if (!empty($shopify_product['images'])) {
            foreach ($shopify_product['images'] as $index => $image) {
                $attachment_id = media_sideload_image($image['src'], $product_id, null, 'id');
                
                if (!is_wp_error($attachment_id)) {
                    if ($index === 0) {
                        // Set as featured image
                        set_post_thumbnail($product_id, $attachment_id);
                    } else {
                        // Add to gallery
                        $gallery_images[] = $attachment_id;
                    }
                }
            }
        }
        
        // Set gallery images
        if (!empty($gallery_images)) {
            $product = wc_get_product($product_id);
            $product->set_gallery_image_ids($gallery_images);
            $product->save();
        }
    }
    
    /**
     * Get product by Shopify ID
     */
    private function get_product_by_shopify_id($shopify_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_shopify_product_id' 
            AND meta_value = %s",
            $shopify_id
        );
        
        return $wpdb->get_var($query);
    }
    
    /**
     * Get variation by Shopify ID
     */
    private function get_variation_by_shopify_id($shopify_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_shopify_variant_id' 
            AND meta_value = %s",
            $shopify_id
        );
        
        return $wpdb->get_var($query);
    }
}



// At bottom, replace direct instantiation with:
function initialize_shopify_integration() {
    global $shopify_importer, $shopify_webhook;
    
    $shopify_importer = new Shopify_WooCommerce_Importer();
    $shopify_webhook = new Webhook_Generator($shopify_importer);
    $shopify_importer->set_webhook_url($shopify_webhook->get_webhook_url());
}
add_action('init', 'initialize_shopify_integration');

