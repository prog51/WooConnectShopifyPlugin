jQuery(document).ready(function($) {
    let currentPage = 1;
    let totalImported = 0;
    
    $('#start-import').on('click', function() {
        $(this).prop('disabled', true);
        $('#import-progress').show();
        $('#import-results').hide();
        currentPage = 1;
        totalImported = 0;
        
        importProducts();
    });
    
    function importProducts() {
        $.ajax({
            url: shopifyImporter.ajax_url,
            type: 'POST',
            data: {
                action: 'shopify_import_products',
                nonce: shopifyImporter.nonce,
                page: currentPage
            },
            success: function(response) {
                if (response.success) {
                    totalImported += response.data.imported;
                    
                    if (response.data.completed) {
                        // Import completed
                        $('#progress-fill').css('width', '100%');
                        $('#progress-text').text('Import completed! Total products imported: ' + totalImported);
                        $('#start-import').prop('disabled', false);
                        
                        $('#import-results').html(
                            '<div class="notice notice-success"><p>Successfully imported ' + totalImported + ' products!</p></div>'
                        ).show();
                    } else {
                        // Continue importing
                        currentPage++;
                        let progress = (currentPage * 10); // Approximate progress
                        $('#progress-fill').css('width', Math.min(progress, 90) + '%');
                        $('#progress-text').text('Importing products... (' + totalImported + ' imported so far)');
                        
                        importProducts();
                    }
                } else {
                    // Error occurred
                    $('#progress-text').text('Error: ' + response.data);
                    $('#start-import').prop('disabled', false);
                    
                    $('#import-results').html(
                        '<div class="notice notice-error"><p>Import failed: ' + response.data + '</p></div>'
                    ).show();
                }
            },
            error: function() {
                $('#progress-text').text('Connection error occurred');
                $('#start-import').prop('disabled', false);
                
                $('#import-results').html(
                    '<div class="notice notice-error"><p>Connection error occurred. Please try again.</p></div>'
                ).show();
            }
        });
    }
});