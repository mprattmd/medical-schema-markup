jQuery(document).ready(function($) {
    
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Analyze site content
    $('#analyze-site-btn').on('click', function() {
        var $btn = $(this);
        var $status = $('#analysis-status');
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Analyzing...');
        $status.removeClass('success error').addClass('loading')
               .html('<span class="dashicons dashicons-update-alt spinning"></span> Analyzing website content... This may take a moment.');
        
        $.ajax({
            url: medicalSchemaAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'analyze_site_content',
                nonce: medicalSchemaAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.removeClass('loading').addClass('success')
                           .html('<span class="dashicons dashicons-yes-alt"></span> Analysis complete! Data has been populated in the fields below. Please review and edit as needed.');
                    
                    // Populate form fields
                    var data = response.data;
                    
                    if (data.name) $('#practice-name').val(data.name);
                    if (data.description) $('#practice-description').val(data.description);
                    if (data.phone) $('#practice-phone').val(data.phone);
                    if (data.email) $('#practice-email').val(data.email);
                    if (data.logo) {
                        $('#practice-logo').val(data.logo);
                        $('#logo-preview').html('<img src="' + data.logo + '" style="max-width:200px;height:auto;margin-top:10px;" />');
                    }
                    
                    if (data.address) {
                        if (data.address.street) $('#address-street').val(data.address.street);
                        if (data.address.city) $('#address-city').val(data.address.city);
                        if (data.address.state) $('#address-state').val(data.address.state);
                        if (data.address.postal) $('#address-postal').val(data.address.postal);
                    }
                    
                    // Add physicians if found
                    if (data.physicians && data.physicians.length > 0) {
                        $('#physicians-container').empty();
                        data.physicians.forEach(function(physician) {
                            addPhysician(physician);
                        });
                    }
                    
                    // Add locations if found
                    if (data.locations && data.locations.length > 0) {
                        $('#locations-container').empty();
                        data.locations.forEach(function(location) {
                            addLocation(location);
                        });
                    }
                    
                    // Add services if found
                    if (data.services && data.services.length > 0) {
                        $('#services-container').empty();
                        data.services.forEach(function(service) {
                            addService(service);
                        });
                    }
                } else {
                    $status.removeClass('loading').addClass('error')
                           .html('<span class="dashicons dashicons-warning"></span> ' + response.data);
                }
            },
            error: function() {
                $status.removeClass('loading').addClass('error')
                       .html('<span class="dashicons dashicons-warning"></span> Error analyzing site. Please try again or enter data manually.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Analyze Site Content');
            }
        });
    });
    
    // Validate schema
    $('#validate-schema-btn').on('click', function() {
        var $btn = $(this);
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Validating...');
        
        $.ajax({
            url: medicalSchemaAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'validate_schema',
                nonce: medicalSchemaAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showValidationResults(response.data);
                } else {
                    alert('Error validating schema: ' + response.data);
                }
            },
            error: function() {
                alert('Error validating schema. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Validate Schema');
            }
        });
    });
    
    // Show validation results modal
    function showValidationResults(results) {
        var html = '<div class="validation-results">';
        
        var errors = results.filter(function(r) { return r.type === 'error'; });
        var warnings = results.filter(function(r) { return r.type === 'warning'; });
        var valid = results.filter(function(r) { return r.type === 'valid'; });
        
        // Summary
        html += '<div class="validation-summary" style="margin-bottom:20px;padding:15px;background:#f0f0f0;border-radius:4px;">';
        html += '<h3 style="margin-top:0;">Validation Summary</h3>';
        html += '<p><strong>' + valid.length + '</strong> items valid, ';
        html += '<strong>' + warnings.length + '</strong> warnings, ';
        html += '<strong>' + errors.length + '</strong> errors</p>';
        html += '</div>';
        
        // Errors
        if (errors.length > 0) {
            html += '<h3>Errors (Must Fix)</h3>';
            errors.forEach(function(item) {
                html += '<div class="validation-item error">';
                html += '<h4><span class="icon">❌</span> ' + item.field + '</h4>';
                html += '<p>' + item.message + '</p>';
                html += '</div>';
            });
        }
        
        // Warnings
        if (warnings.length > 0) {
            html += '<h3>Warnings (Recommended)</h3>';
            warnings.forEach(function(item) {
                html += '<div class="validation-item warning">';
                html += '<h4><span class="icon">⚠️</span> ' + item.field + '</h4>';
                html += '<p>' + item.message + '</p>';
                html += '</div>';
            });
        }
        
        // Valid items (show a few)
        if (valid.length > 0) {
            html += '<h3>Valid Items</h3>';
            valid.slice(0, 5).forEach(function(item) {
                html += '<div class="validation-item valid">';
                html += '<h4><span class="icon">✓</span> ' + item.field + '</h4>';
                html += '<p>' + item.message + '</p>';
                html += '</div>';
            });
            if (valid.length > 5) {
                html += '<p><em>... and ' + (valid.length - 5) + ' more valid items</em></p>';
            }
        }
        
        html += '<div style="margin-top:20px;padding:15px;background:#e5f5fa;border-radius:4px;">';
        html += '<p><strong>💡 Tip:</strong> You can also test your schema with <a href="https://search.google.com/test/rich-results" target="_blank">Google\'s Rich Results Test</a> for additional validation.</p>';
        html += '</div>';
        
        html += '</div>';
        
        $('#validation-results').html(html);
        $('#validation-modal').fadeIn();
    }
    
    // Preview rich snippet
    $('#preview-rich-snippet-btn').on('click', function() {
        var $btn = $(this);
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Loading...');
        
        $.ajax({
            url: medicalSchemaAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'preview_rich_snippet',
                nonce: medicalSchemaAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showRichSnippetPreview(response.data);
                } else {
                    alert('Error generating preview: ' + response.data);
                }
            },
            error: function() {
                alert('Error generating preview. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-visibility"></span> Preview Rich Snippet');
            }
        });
    });
    
    // Show rich snippet preview modal
    function showRichSnippetPreview(html) {
        $('#rich-snippet-preview').html(html);
        $('#rich-snippet-modal').fadeIn();
    }
    
    // Close modals
    $('.close').on('click', function() {
        $(this).closest('.modal').fadeOut();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').fadeOut();
        }
    });
    
    // Add location
    var locationIndex = $('#locations-container .location-entry').length;
    
    function addLocation(data) {
        var template = $('#location-template').html();
        var $html = $(template.replace(/INDEX/g, locationIndex));
        
        if (data) {
            $html.find('input[name*="[name]"]').val(data.name || '');
            $html.find('input[name*="[street]"]').val(data.street || '');
            $html.find('input[name*="[city]"]').val(data.city || '');
            $html.find('input[name*="[state]"]').val(data.state || '');
            $html.find('input[name*="[postal]"]').val(data.postal || '');
            $html.find('input[name*="[phone]"]').val(data.phone || '');
            $html.find('input[name*="[email]"]').val(data.email || '');
        }
        
        $('#locations-container').append($html);
        locationIndex++;
    }
    
    $('.add-location').on('click', function() {
        addLocation(null);
    });
    
    $(document).on('click', '.remove-location', function() {
        if (confirm('Are you sure you want to remove this location?')) {
            $(this).closest('.location-entry').fadeOut(function() {
                $(this).remove();
            });
        }
    });
    
    // Add physician
    var physicianIndex = $('#physicians-container .physician-entry').length;
    
    function addPhysician(data) {
        var template = $('#physician-template').html();
        var $html = $(template.replace(/INDEX/g, physicianIndex));
        
        if (data) {
            $html.find('input[name*="[name]"]').val(data.name || '');
            $html.find('input[name*="[title]"]').val(data.title || '');
            $html.find('input[name*="[specialty]"]').val(data.specialty || '');
            $html.find('textarea[name*="[bio]"]').val(data.bio || '');
            $html.find('input[name*="[image]"]').val(data.image || '');
        }
        
        $('#physicians-container').append($html);
        physicianIndex++;
    }
    
    $('.add-physician').on('click', function() {
        addPhysician(null);
    });
    
    $(document).on('click', '.remove-physician', function() {
        if (confirm('Are you sure you want to remove this physician?')) {
            $(this).closest('.physician-entry').fadeOut(function() {
                $(this).remove();
            });
        }
    });
    
    // Add service
    var serviceIndex = $('#services-container .service-entry').length;
    
    function addService(data) {
        var template = $('#service-template').html();
        var $html = $(template.replace(/INDEX/g, serviceIndex));
        
        if (data) {
            $html.find('input[name*="[name]"]').val(data.name || '');
            $html.find('textarea[name*="[description]"]').val(data.description || '');
        }
        
        $('#services-container').append($html);
        serviceIndex++;
    }
    
    $('.add-service').on('click', function() {
        addService(null);
    });
    
    $(document).on('click', '.remove-service', function() {
        if (confirm('Are you sure you want to remove this service?')) {
            $(this).closest('.service-entry').fadeOut(function() {
                $(this).remove();
            });
        }
    });
    
    // Handle checkbox to disable/enable time inputs
    $(document).on('change', 'input[name*="[closed]"]', function() {
        var $hoursInputs = $(this).closest('tr, div').find('.hours-inputs, input[type="time"]');
        if ($(this).is(':checked')) {
            $hoursInputs.prop('disabled', true).css('opacity', '0.5');
        } else {
            $hoursInputs.prop('disabled', false).css('opacity', '1');
        }
    });
    
    // Initialize closed state on page load
    $('input[name*="[closed]"]:checked').each(function() {
        $(this).closest('tr, div').find('.hours-inputs, input[type="time"]')
               .prop('disabled', true).css('opacity', '0.5');
    });
    
    // Media uploader for images
    var mediaUploader;
    
    $(document).on('click', '.upload-image-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var targetInput = $button.data('target');
        var $input = targetInput ? $button.siblings('.' + targetInput) : $button.siblings('input[type="url"]');
        
        // If we don't have a specific target, find the closest input
        if (!$input.length) {
            $input = $button.prev('input[type="url"]');
        }
        
        // Create media uploader
        mediaUploader = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        // When image is selected
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
            
            // Update logo preview if it's the logo field
            if ($input.attr('id') === 'practice-logo') {
                $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width:200px;height:auto;margin-top:10px;" />');
            }
        });
        
        mediaUploader.open();
    });
    
    // Generate schema preview
    $('#generate-preview').on('click', function() {
        var schemaObj = generateSchemaPreview();
        $('#schema-output').text(JSON.stringify(schemaObj, null, 2));
    });
    
    function generateSchemaPreview() {
        var schemaObj = {
            "@context": "https://schema.org",
            "@type": $('#practice-type').val() || "MedicalBusiness",
            "name": $('#practice-name').val(),
            "description": $('#practice-description').val(),
            "url": $('#practice-url').val(),
            "telephone": $('#practice-phone').val(),
            "email": $('#practice-email').val()
        };
        
        // Add logo
        if ($('#practice-logo').val()) {
            schemaObj.logo = $('#practice-logo').val();
            schemaObj.image = $('#practice-logo').val();
        }
        
        // Add price range
        if ($('select[name="schema[price_range]"]').val()) {
            schemaObj.priceRange = $('select[name="schema[price_range]"]').val();
        }
        
        // Add address (from first location or main address)
        var firstLocation = $('.location-entry').first();
        if (firstLocation.length) {
            var street = firstLocation.find('input[name*="[street]"]').val();
            if (street) {
                schemaObj.address = {
                    "@type": "PostalAddress",
                    "streetAddress": street,
                    "addressLocality": firstLocation.find('input[name*="[city]"]').val(),
                    "addressRegion": firstLocation.find('input[name*="[state]"]').val(),
                    "postalCode": firstLocation.find('input[name*="[postal]"]').val(),
                    "addressCountry": "US"
                };
            }
        } else if ($('#address-street').val()) {
            schemaObj.address = {
                "@type": "PostalAddress",
                "streetAddress": $('#address-street').val(),
                "addressLocality": $('#address-city').val(),
                "addressRegion": $('#address-state').val(),
                "postalCode": $('#address-postal').val(),
                "addressCountry": "US"
            };
        }
        
        // Add rating
        var ratingValue = $('input[name="schema[rating_value]"]').val();
        var reviewCount = $('input[name="schema[review_count]"]').val();
        if (ratingValue && reviewCount) {
            schemaObj.aggregateRating = {
                "@type": "AggregateRating",
                "ratingValue": ratingValue,
                "reviewCount": reviewCount
            };
        }
        
        // Add physicians
        var physicians = [];
        $('.physician-entry').each(function() {
            var name = $(this).find('input[name*="[name]"]').val();
            if (name) {
                var physician = {
                    "@type": "Physician",
                    "name": name
                };
                
                var specialty = $(this).find('input[name*="[specialty]"]').val();
                if (specialty) physician.medicalSpecialty = specialty;
                
                var title = $(this).find('input[name*="[title]"]').val();
                if (title) physician.jobTitle = title;
                
                physicians.push(physician);
            }
        });
        
        if (physicians.length > 0) {
            schemaObj.employee = physicians;
        }
        
        return schemaObj;
    }
    
    // Copy schema to clipboard
    $('#copy-schema').on('click', function() {
        var schemaText = $('#schema-output').text();
        
        if (!schemaText) {
            alert('Please generate the preview first');
            return;
        }
        
        // Create temporary textarea
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(schemaText).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
        setTimeout(function() {
            $btn.html(originalText);
        }, 2000);
    });
    
    // Form submission
    $('#schema-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var formData = $form.serialize();
        
        $submit.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spinning"></span> Saving...');
        
        $.ajax({
            url: medicalSchemaAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_schema_data',
                nonce: medicalSchemaAjax.nonce,
                formData: formData
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    var $notice = $('<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Schema data saved successfully. Your schema markup is now active on your website.</p></div>');
                    $('.medical-schema-admin h1').after($notice);
                    
                    // Scroll to top
                    $('html, body').animate({ scrollTop: 0 }, 500);
                    
                    // Auto-dismiss after 5 seconds
                    setTimeout(function() {
                        $notice.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                } else {
                    alert('Error saving data: ' + response.data);
                }
            },
            error: function() {
                alert('Error saving data. Please try again.');
            },
            complete: function() {
                $submit.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Schema Data');
            }
        });
    });
    
    // Export schema data
    $('#export-schema').on('click', function() {
        var formData = $('#schema-form').serializeArray();
        var schemaData = {};
        
        // Convert form data to object
        formData.forEach(function(field) {
            var keys = field.name.match(/\[([^\]]+)\]/g);
            if (keys) {
                var current = schemaData;
                keys.forEach(function(key, index) {
                    key = key.replace(/[\[\]]/g, '');
                    if (index === keys.length - 1) {
                        current[key] = field.value;
                    } else {
                        current[key] = current[key] || {};
                        current = current[key];
                    }
                });
            }
        });
        
        // Create download
        var dataStr = JSON.stringify(schemaData.schema, null, 2);
        var dataBlob = new Blob([dataStr], { type: 'application/json' });
        var url = URL.createObjectURL(dataBlob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'medical-schema-data.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });
    
    // Import schema data
    $('#import-schema').on('click', function() {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = function(e) {
            var file = e.target.files[0];
            var reader = new FileReader();
            
            reader.onload = function(event) {
                try {
                    var data = JSON.parse(event.target.result);
                    
                    // Populate form fields
                    if (confirm('This will replace all current data. Continue?')) {
                        populateFormWithData(data);
                        alert('Data imported successfully! Please review and save.');
                    }
                } catch (error) {
                    alert('Error parsing JSON file: ' + error.message);
                }
            };
            
            reader.readAsText(file);
        };
        
        input.click();
    });
    
    function populateFormWithData(data) {
        // Basic fields
        if (data.type) $('#practice-type').val(data.type);
        if (data.name) $('#practice-name').val(data.name);
        if (data.description) $('#practice-description').val(data.description);
        if (data.url) $('#practice-url').val(data.url);
        if (data.phone) $('#practice-phone').val(data.phone);
        if (data.email) $('#practice-email').val(data.email);
        if (data.logo) $('#practice-logo').val(data.logo);
        
        // Locations
        if (data.locations && data.locations.length > 0) {
            $('#locations-container').empty();
            data.locations.forEach(function(location) {
                $('.add-location').click();
                var $lastLocation = $('.location-entry').last();
                Object.keys(location).forEach(function(key) {
                    $lastLocation.find('input[name*="[' + key + ']"]').val(location[key]);
                });
            });
        }
        
        // Physicians
        if (data.physicians && data.physicians.length > 0) {
            $('#physicians-container').empty();
            data.physicians.forEach(function(physician) {
                addPhysician(physician);
            });
        }
        
        // Services
        if (data.services && data.services.length > 0) {
            $('#services-container').empty();
            data.services.forEach(function(service) {
                addService(service);
            });
        }
    }
    
    // Add spinning animation CSS
    var style = document.createElement('style');
    style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } .spinning { animation: spin 1s linear infinite; display: inline-block; }';
    document.head.appendChild(style);
});