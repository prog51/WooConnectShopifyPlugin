<?php

/*
*
* Webhook generator
*/

class Webhook_Generator {

    private $namespace;
    private $hookEndpoint; // Renamed for consistency

    public function __construct() {
        $this->namespace = "WooShopifyIntengration/v1";
        $this->hookEndpoint = "shopifyConnector"; // Use the corrected property name
    }

    // Note: The GenerateHook method isn't strictly necessary if you only need the route definition
    // private function GenerateHook(){
    //     return esc_url(get_rest_url());
    // }

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
            '/' . $this->hookEndpoint, // Prefix the endpoint path with a slash
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

    public function handle_webhook_data(WP_REST_Request $resquest){

        return new WP_REST_Response( 'Webhook received successfully!', 200 );

    }


      public function render_url_display_html() {
        $url = $this->get_webhook_url();
        ?>
        <hr>
        <h3>Shopify Webhook URL</h3>
        <p>Use this URL for your Shopify connection:</p>
        <input type="text" value="<?php echo esc_url( $url ); ?>" class="large-text code" readonly="readonly" onclick="this.select();" />
        <p class="description">Copy and paste this URL into the Shopify Webhooks configuration.</p>
        <?php
    }
}


/**
 * Initialization function hooked into 'rest_api_init'.
 */
function init_webhook() {
    $init = new Webhook_Generator;
    $init->register_webhook();
} 

// This is the correct action hook to register custom REST API endpoints
add_action("rest_api_init", 'init_webhook');


function set_url_webhook(){

    $render_hook = new Webhook_Generator;

    $render_hook->render_url_display_html();
}
add_action("register_product_fields", "set_url_webhook");