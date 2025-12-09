<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('after_setup_theme', 'carbon_fields_fun');
add_action('carbon_fields_register_fields', 'create_option_page');

function carbon_fields_fun()
{
    \Carbon_Fields\Carbon_Fields::boot();
}

function create_option_page()
{
    Container::make( 'theme_options', __( 'Simple integration shopify' ) )
    ->set_page_file('sim-shopify-integration')
    ->set_page_menu_title('Shopify Integration')
    ->set_icon('dashicons-cart')
    ->add_fields( array(
        //Field::make( 'text', 'crb_facebook_url', __( 'Facebook URL' ) ),
        //Field::make( 'textarea', 'crb_footer_text', __( 'Footer Text' ) )

        Field::make('text', 'shopify_api_key', 'Shopify API Key')
                ->set_help_text('Enter your Shopify API key'),

            Field::make('text', 'shopify_webhook_secret', 'Webhook Secret')
                ->set_help_text('Enter the secret for your webhook: OPTIONAL'),

            Field::make('text', 'shopify_access_token', 'Shopify Access Token')
                ->set_help_text('Enter your Shopify access token'),

            Field::make('text', 'shopify_shop_url', 'Shopify URL')
                ->set_help_text('Enter the URL for your Shopify store')

    ) );
}

/**
 * Register Carbon Fields for products
 */
add_action('carbon_fields_register_fields', 'register_product_fields');

function register_product_fields() {
    Container::make('post_meta', __('Shopify Product Data'))
        ->where('post_type', '=', 'product') // Change to your custom post type
        ->add_fields(array(
            Field::make('text', 'shopify_product_id', 'Shopify Product ID')
                ->set_attribute('readOnly', true),
            
            Field::make('text', 'product_handle', 'Product Handle')
                ->set_attribute('readOnly', true),
            
            Field::make('text', 'product_vendor', 'Vendor'),
            
            Field::make('text', 'product_type', 'Product Type'),
            
            Field::make('text', 'product_tags', 'Tags'),
            
            Field::make('complex', 'product_variants', 'Product Variants')
                ->add_fields(array(
                    Field::make('text', 'variant_id', 'Variant ID')
                        ->set_width(20),
                    Field::make('text', 'title', 'Title')
                        ->set_width(20),
                    Field::make('text', 'price', 'Price')
                        ->set_width(20),
                    Field::make('text', 'sku', 'SKU')
                        ->set_width(20),
                    Field::make('text', 'inventory_quantity', 'Inventory')
                        ->set_width(20),
                ))
                ->set_collapsed(true)
                ->set_header_template('
                    <% if (title) { %>
                        <%= title %> - $<%= price %>
                    <% } else { %>
                        Variant #<%= variant_id %>
                    <% } %>
                '),
        ));
}
