<?php

/*CODE TO ADD SHOPIFY INTEGRATION BELOW */

function maybe_sync_cart_to_shopify() {
    if (is_admin()) return;
    if (!function_exists('WC') || !WC()->cart) return;

    if (isset($_GET['sync_shopify']) && $_GET['sync_shopify'] == '1') {
        add_to_woocommerce_cart();
    }
}
add_action('template_redirect', 'maybe_sync_cart_to_shopify');


function add_to_woocommerce_cart() {
    if (!function_exists('WC') || !WC()->cart) return;
    
    error_log("Plugin reached line X");

    $woocommerce_cart = WC()->cart->get_cart();

    // Check if the cart is not empty
    if (empty($woocommerce_cart)) {
        WC()->session->set("ERROR_FOR_SHOPIFY", "WooCommerce cart is empty.<br/>");
        return;
    }

    error_log("Plugin reached line X 2");

    $payload = array();

    foreach ($woocommerce_cart as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
        $quantity = $cart_item['quantity'];

        // Use variation ID if it exists, otherwise use product ID
        $product_to_use = $variation_id ? $variation_id : $product_id;
        $product = wc_get_product($product_to_use);
        
        if (!$product) {
            error_log("Product not found: " . $product_to_use);
            continue;
        }

        // Get Shopify variant ID from product slug
        $shopify_id = $product->get_slug();
        
        error_log("Product ID: " . $product_to_use . " | Product Name: " . $product->get_name() . " | Slug (Shopify Variant ID): " . $shopify_id);

        // Verify we have a slug
        if (empty($shopify_id)) {
            error_log("ERROR: Empty slug for product: " . $product_to_use);
            WC()->session->set("ERROR_FOR_SHOPIFY", "Product '" . $product->get_name() . "' has no slug set.<br/>");
            continue;
        }

        $item_data = array(
            'variantId' => 'gid://shopify/ProductVariant/'. $shopify_id,
            'quantity' => $quantity,
        );

        $payload[] = $item_data;
        
        error_log("Added to payload: " . $item_data['variantId'] . " (Qty: " . $quantity . ")");
    }

    // Check if payload is not empty
    if (empty($payload)) {
        WC()->session->set("ERROR_FOR_SHOPIFY", "Failed to add to WooCommerce cart. Payload is empty. <br/>");
        return;
    }

    // WooCommerce request successful
    // Proceed to update Shopify cart
    $url = add_to_shopify_cart($payload);
    
    $NewUrl = (string) $url;

    // Store in WooCommerce session instead of PHP session
    WC()->session->set('custom_checkout_url', $NewUrl);
    
    error_log("Shopify checkout URL: " . $NewUrl);
    
    // Call redirect function
    custom_checkout_url_redirect($NewUrl);
}

// Hook this function to some action in your WooCommerce flow
add_action('woocommerce_before_cart', 'add_to_woocommerce_cart', 10, 2);


// Redirect the "Proceed to Checkout" button to a custom URL
function custom_checkout_url_redirect($url) {
    // Check WooCommerce session first
    if (function_exists('WC') && WC()->session) {
        $shopify_url = WC()->session->get('custom_checkout_url');
        if (!empty($shopify_url) && filter_var($shopify_url, FILTER_VALIDATE_URL)) {
            return $shopify_url;
        }
    }
    
    // Fallback to PHP session if WC session not available
    if (isset($_SESSION['custom_checkout_url']) && !empty($_SESSION['custom_checkout_url'])) {
        return $_SESSION['custom_checkout_url'];
    }
    
    // Return original URL as fallback instead of empty string
    return $url;
}
add_filter('woocommerce_get_checkout_url', 'custom_checkout_url_redirect');


function add_to_shopify_cart($payload) {
    error_log("Plugin reached line X 3");

    // Get token from Carbon Fields
    $shopify_token = carbon_get_theme_option('shopify_access_token');
    
    // Get Shopify URL from Carbon Fields
    $shopify_url = carbon_get_theme_option('shopify_shop_url');
    
    // Remove trailing slash if present
    $shopify_url = rtrim($shopify_url, '/');
    
    error_log("Shopify URL: " . $shopify_url);
    error_log("Token present: " . (!empty($shopify_token) ? 'Yes' : 'No'));
    
    // Check if configuration is valid
    if (empty($shopify_token)) {
        error_log("ERROR: Shopify access token is empty!");
        WC()->session->set("ERROR_FOR_SHOPIFY", "Shopify configuration error: Access token is missing. Please configure in WordPress admin.<br/>");
        return "ERROR: No access token configured";
    }
    
    if (empty($shopify_url)) {
        error_log("ERROR: Shopify shop URL is empty!");
        WC()->session->set("ERROR_FOR_SHOPIFY", "Shopify configuration error: Shop URL is missing. Please configure in WordPress admin.<br/>");
        return "ERROR: No shop URL configured";
    }

    $error_message = "ERROR:<br/>";
    $url = null;
    $error_flag = false;

    // Build line items for the GraphQL mutation
    $lineItems = array_map(function($item) {
        return [
            'merchandiseId' => $item['variantId'],  // Use merchandiseId instead of variantId
            'quantity' => $item['quantity']
        ];
    }, $payload);

    // GraphQL mutation
    $graphqlArray = [
        'query' => 'mutation cartCreate($input: CartInput!) {
            cartCreate(input: $input) {
                cart {
                    id
                    checkoutUrl
                }
                userErrors {
                    field
                    message
                }
            }
        }',
        'variables' => [
            'input' => [
                'lines' => $lineItems
            ]
        ]
    ];

    error_log("Shopify GraphQL request: " . json_encode($graphqlArray));
    error_log("Using Storefront Access Token (first 10 chars): " . substr($shopify_token, 0, 10) . "...");
    error_log("Making request to: " . $shopify_url . '/api/2024-04/graphql.json');

    // Send request to Shopify
    $response = wp_remote_post($shopify_url . '/api/2024-04/graphql.json', [
        'method'  => 'POST',
        'headers' => [
            'Content-Type'                      => 'application/json',
            'X-Shopify-Storefront-Access-Token' => $shopify_token,
        ],
        'body'    => json_encode($graphqlArray),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        error_log("Plugin reached line X 4 - WP Error: " . $response->get_error_message()); 
        $error_message .= "Request error: " . $response->get_error_message();
        $error_flag = true;
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log("Shopify response code: " . $status_code);
        error_log("Shopify response body: " . $response_body);

        if ($status_code === 200) {
            error_log("Plugin reached line X 5");

            $responseData = json_decode($response_body, true);

            if (!empty($responseData['data']['cartCreate']['cart']['checkoutUrl'])) {
                $url = $responseData['data']['cartCreate']['cart']['checkoutUrl'];
                error_log("SUCCESS - Checkout URL: " . $url);
            } else {
                error_log("Plugin reached line X 6");

                $error_flag = true;
                $error_message .= "Shopify cartCreate failed.<br/>";

                if (!empty($responseData['data']['cartCreate']['userErrors'])) {
                    error_log("Plugin reached line X 7");
                    foreach ($responseData['data']['cartCreate']['userErrors'] as $e) {
                        $error_message .= "- " . $e['message'] . "<br/>";
                        error_log("User Error: " . $e['message']);
                    }
                } else if (!empty($responseData['errors'])) {
                    error_log("Plugin reached line X 8");
                    foreach ($responseData['errors'] as $e) {
                        $error_message .= "- " . $e['message'] . "<br/>";
                        error_log("GraphQL Error: " . $e['message']);
                    }
                }
            }
        } else {
            // Handle non-200 status codes
            error_log("Plugin reached line X 9 - HTTP Status: " . $status_code);
            $error_flag = true;
            $error_message .= "HTTP Error: " . $status_code . "<br/>";
            $error_message .= "Response: " . substr($response_body, 0, 500) . "<br/>";
        }
    }

    // Store error in WooCommerce session
    if ($error_flag) {
        error_log("Plugin reached line X 10");
        WC()->session->set("ERROR_FOR_SHOPIFY", $error_message);
    }

    // Output JS for hiding checkout button
    echo '<script>
        jQuery(document).ready(function($) {
            if (' . ($error_flag ? 'true' : 'false') . ') {
                $(".checkout-button").hide();
                $(".error-message").show();
            }

            $(".checkout-button").hide();
            $(".cart_totals").append("<center><p>CLICK GREEN WHATSAPP BUTTON TO SUBMIT</p></center>");
        });
    </script>';

    return !empty($url) ? $url : $error_message;
}


// Display error messages on cart page
function display_shopify_errors() {
    if (!function_exists('WC') || !WC()->session) return;
    
    $error = WC()->session->get('ERROR_FOR_SHOPIFY');
    
    if (!empty($error)) {
        echo '<div class="woocommerce-error">' . $error . '</div>';
        // Clear the error after displaying
        WC()->session->set('ERROR_FOR_SHOPIFY', null);
    }
}
add_action('woocommerce_before_cart', 'display_shopify_errors');
add_action('woocommerce_before_checkout_form', 'display_shopify_errors');


/*CODE TO ADD SHOPIFY INTEGRATION ABOVE */