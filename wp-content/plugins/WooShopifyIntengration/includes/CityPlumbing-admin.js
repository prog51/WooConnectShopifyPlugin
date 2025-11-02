document.addEventListener("DOMContentLoaded", function() {
    
    // Select all input fields with specific attributes or class names that need to be blurred
    var inputs = document.querySelectorAll('input[name="carbon_fields_compact_input[_shopify_api_key]"], input[name="carbon_fields_compact_input[_shopify_api_secret]"], input[name="carbon_fields_compact_input[_shopify_access_token]"]');

    inputs.forEach(function(input) {
        input.classList.add('blur-text');
    });
});
