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
        register_rest_route(
            $this->namespace,
            '/' . $this->hookEndpoint, // Prefix the endpoint path with a slash
            array(
                'methods'             => 'POST', // Webhooks usually use POST requests
                'callback'            => array( $this, 'handle_webhook_data' ), // Callback function in this class
                'permission_callback' => '__return_true', // Allows public access (necessary for webhooks)
            )
        );
    }

    /**
     * The callback function that runs when the endpoint is hit.
     * 
     * @param WP_REST_Request $request The request object.
     */
    public function handle_webhook_data( $request ) {
        // You can access data sent to the endpoint via the $request object
        $parameters = $request->get_params();

        // Perform your logic here (e.g., save data to the database, process the Shopify data)

        // Return a response
        return new WP_REST_Response( 'Webhook received successfully!', 200 );
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
