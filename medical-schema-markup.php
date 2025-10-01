<?php
/**
 * Plugin Name: Medical Schema Markup Generator Pro
 * Plugin URI: https://github.com/mprattmd/medical-schema-markup
 * Description: Automatically generates and manages schema markup for medical practices with enhanced site analysis
 * Version: 2.0.2
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class Medical_Schema_Plugin {
    
    private $option_name = 'medical_schema_data';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_head', array($this, 'output_schema_markup'), 1);
        add_action('wp_ajax_analyze_site_content', array($this, 'ajax_analyze_site_content'));
        add_action('wp_ajax_save_schema_data', array($this, 'ajax_save_schema_data'));
        add_action('wp_ajax_validate_schema', array($this, 'ajax_validate_schema'));
        add_action('wp_ajax_preview_rich_snippet', array($this, 'ajax_preview_rich_snippet'));
    }
    
    public function add_admin_menu() {
        add_menu_page('Medical Schema', 'Medical Schema', 'manage_options', 'medical-schema', 
                      array($this, 'admin_page'), 'dashicons-welcome-learn-more', 30);
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_medical-schema') return;
        
        wp_enqueue_media();
        wp_enqueue_script('medical-schema-admin', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '2.0', true);
        wp_localize_script('medical-schema-admin', 'medicalSchemaAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('medical_schema_nonce')
        ));
    }
    
    public function admin_page() {
        $schema_data = get_option($this->option_name, array());
        ?>
        <div class="wrap medical-schema-admin">
            <h1>Medical Schema Markup Generator Pro</h1>
            
            <div class="notice notice-info">
                <p><strong>How it works:</strong> Click "Analyze Site" to scan your website. Review and edit the data, then save.</p>
            </div>
            
            <div class="top-actions" style="margin: 20px 0;">
                <button id="analyze-site-btn" class="button button-primary button-hero">
                    <span class="dashicons dashicons-search"></span> Analyze Site Content
                </button>
                <button id="validate-schema-btn" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-yes-alt"></span> Validate Schema
                </button>
                <button id="preview-rich-snippet-btn" class="button button-secondary button-hero">
                    <span class="dashicons dashicons-visibility"></span> Preview Rich Snippet
                </button>
            </div>
            
            <div id="analysis-status" style="margin: 20px 0;"></div>
            
            <!-- Modals -->
            <div id="rich-snippet-modal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Rich Snippet Preview</h2>
                    <p>This is how your practice may appear in Google:</p>
                    <div id="rich-snippet-preview"></div>
                </div>
            </div>
            
            <div id="validation-modal" class="modal" style="display:none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Schema Validation Results</h2>
                    <div id="validation-results"></div>
                </div>
            </div>
            
            <form method="post" id="schema-form">
                <?php wp_nonce_field('save_schema_data', 'schema_nonce'); ?>
                
                <div class="schema-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#tab-practice" class="nav-tab nav-tab-active">Practice Info</a>
                        <a href="#tab-locations" class="nav-tab">Locations</a>
                        <a href="#tab-physicians" class="nav-tab">Physicians</a>
                        <a href="#tab-services" class="nav-tab">Services</a>
                        <a href="#tab-advanced" class="nav-tab">Advanced</a>
                    </nav>
                    
                    <!-- Practice Info Tab -->
                    <div id="tab-practice" class="tab-content active">
                        <h2>Medical Practice Information</h2>
                        
                        <table class="form-table">
                            <tr>
                                <th><label>Practice Type</label></th>
                                <td>
                                    <select name="schema[type]" id="practice-type">
                                        <option value="MedicalBusiness">General Medical Practice</option>
                                        <option value="Physician">Individual Physician</option>
                                        <option value="Dentist">Dental Practice</option>
                                        <option value="MedicalClinic">Medical Clinic</option>
                                        <option value="Hospital">Hospital</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Practice Name *</label></th>
                                <td>
                                    <input type="text" name="schema[name]" id="practice-name" 
                                           value="<?php echo esc_attr($schema_data['name'] ?? ''); ?>" 
                                           class="regular-text" required />
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Description</label></th>
                                <td>
                                    <textarea name="schema[description]" id="practice-description" 
                                              rows="4" class="large-text"><?php echo esc_textarea($schema_data['description'] ?? ''); ?></textarea>
                                    <p class="description">Brief description (50-160 characters recommended)</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Phone Number</label></th>
                                <td>
                                    <input type="tel" name="schema[phone]" id="practice-phone" 
                                           value="<?php echo esc_attr($schema_data['phone'] ?? ''); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Email</label></th>
                                <td>
                                    <input type="email" name="schema[email]" id="practice-email" 
                                           value="<?php echo esc_attr($schema_data['email'] ?? ''); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Website URL</label></th>
                                <td>
                                    <input type="url" name="schema[url]" id="practice-url" 
                                           value="<?php echo esc_attr($schema_data['url'] ?? home_url()); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Logo URL</label></th>
                                <td>
                                    <input type="url" name="schema[logo]" id="practice-logo" 
                                           value="<?php echo esc_attr($schema_data['logo'] ?? ''); ?>" 
                                           class="regular-text" />
                                    <button type="button" class="button upload-image-btn">Upload Logo</button>
                                    <div id="logo-preview" style="margin-top:10px;">
                                        <?php if (!empty($schema_data['logo'])): ?>
                                            <img src="<?php echo esc_url($schema_data['logo']); ?>" style="max-width:200px;" />
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Accepting New Patients</label></th>
                                <td>
                                    <input type="checkbox" name="schema[accepting_patients]" value="1"
                                           <?php checked(!empty($schema_data['accepting_patients'])); ?> />
                                    Yes, accepting new patients
                                </td>
                            </tr>
                            
                            <tr>
                                <th><label>Aggregate Rating</label></th>
                                <td>
                                    Rating: <input type="number" name="schema[rating_value]" 
                                           value="<?php echo esc_attr($schema_data['rating_value'] ?? ''); ?>" 
                                           step="0.1" min="0" max="5" class="small-text" placeholder="4.8" />
                                    Reviews: <input type="number" name="schema[review_count]" 
                                           value="<?php echo esc_attr($schema_data['review_count'] ?? ''); ?>" 
                                           class="small-text" placeholder="127" />
                                    <p class="description">Shows star ratings in search results</p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3>Social Media Profiles</h3>
                        <table class="form-table">
                            <tr>
                                <th><label>Facebook</label></th>
                                <td><input type="url" name="schema[social][facebook]" value="<?php echo esc_attr($schema_data['social']['facebook'] ?? ''); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label>Twitter</label></th>
                                <td><input type="url" name="schema[social][twitter]" value="<?php echo esc_attr($schema_data['social']['twitter'] ?? ''); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label>Instagram</label></th>
                                <td><input type="url" name="schema[social][instagram]" value="<?php echo esc_attr($schema_data['social']['instagram'] ?? ''); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label>LinkedIn</label></th>
                                <td><input type="url" name="schema[social][linkedin]" value="<?php echo esc_attr($schema_data['social']['linkedin'] ?? ''); ?>" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><label>YouTube</label></th>
                                <td><input type="url" name="schema[social][youtube]" value="<?php echo esc_attr($schema_data['social']['youtube'] ?? ''); ?>" class="regular-text" /></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Locations Tab -->
                    <div id="tab-locations" class="tab-content">
                        <h2>Practice Locations</h2>
                        <button type="button" class="button button-primary add-location">Add Location</button>
                        <div id="locations-container"></div>
                        <template id="location-template">
                            <div class="location-entry card" style="background:#f9f9f9;padding:20px;margin:20px 0;border:1px solid #ddd;">
                                <button type="button" class="button-link remove-location" style="float:right;color:red;">Remove</button>
                                <h3>Location</h3>
                                <table class="form-table">
                                    <tr><th>Name</th><td><input type="text" name="schema[locations][INDEX][name]" class="regular-text" /></td></tr>
                                    <tr><th>Street</th><td><input type="text" name="schema[locations][INDEX][street]" class="regular-text" /></td></tr>
                                    <tr><th>City</th><td><input type="text" name="schema[locations][INDEX][city]" class="regular-text" /></td></tr>
                                    <tr><th>State</th><td><input type="text" name="schema[locations][INDEX][state]" class="regular-text" /></td></tr>
                                    <tr><th>Zip</th><td><input type="text" name="schema[locations][INDEX][postal]" class="regular-text" /></td></tr>
                                    <tr><th>Phone</th><td><input type="tel" name="schema[locations][INDEX][phone]" class="regular-text" /></td></tr>
                                </table>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Physicians Tab -->
                    <div id="tab-physicians" class="tab-content">
                        <h2>Physicians & Staff</h2>
                        <button type="button" class="button button-primary add-physician">Add Physician</button>
                        <div id="physicians-container"></div>
                        <template id="physician-template">
                            <div class="physician-entry card" style="background:#f9f9f9;padding:20px;margin:20px 0;border:1px solid #ddd;">
                                <button type="button" class="button-link remove-physician" style="float:right;color:red;">Remove</button>
                                <h3>Physician</h3>
                                <table class="form-table">
                                    <tr><th>Name</th><td><input type="text" name="schema[physicians][INDEX][name]" class="regular-text" /></td></tr>
                                    <tr><th>Title</th><td><input type="text" name="schema[physicians][INDEX][title]" class="regular-text" placeholder="Cardiologist" /></td></tr>
                                    <tr><th>Specialty</th><td><input type="text" name="schema[physicians][INDEX][specialty]" class="regular-text" placeholder="Cardiology" /></td></tr>
                                    <tr><th>Bio</th><td><textarea name="schema[physicians][INDEX][bio]" rows="3" class="large-text"></textarea></td></tr>
                                </table>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Services Tab -->
                    <div id="tab-services" class="tab-content">
                        <h2>Medical Services</h2>
                        <button type="button" class="button button-primary add-service">Add Service</button>
                        <div id="services-container"></div>
                        <template id="service-template">
                            <div class="service-entry card" style="background:#f9f9f9;padding:20px;margin:20px 0;border:1px solid #ddd;">
                                <button type="button" class="button-link remove-service" style="float:right;color:red;">Remove</button>
                                <h3>Service</h3>
                                <table class="form-table">
                                    <tr><th>Name</th><td><input type="text" name="schema[services][INDEX][name]" class="regular-text" /></td></tr>
                                    <tr><th>Description</th><td><textarea name="schema[services][INDEX][description]" rows="3" class="large-text"></textarea></td></tr>
                                </table>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Advanced Tab -->
                    <div id="tab-advanced" class="tab-content">
                        <h2>Advanced Settings</h2>
                        <table class="form-table">
                            <tr>
                                <th><label>Enable Schema</label></th>
                                <td>
                                    <input type="checkbox" name="schema[enabled]" value="1" 
                                           <?php checked(!empty($schema_data['enabled']), true); ?> />
                                    Output schema markup on website
                                </td>
                            </tr>
                            <tr>
                                <th><label>Placement</label></th>
                                <td>
                                    <select name="schema[placement]">
                                        <option value="all">All Pages</option>
                                        <option value="home">Homepage Only</option>
                                        <option value="contact">Contact Page Only</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <h3>Preview Schema</h3>
                        <button type="button" class="button" id="generate-preview">Generate Preview</button>
                        <pre id="schema-output" style="background:#f5f5f5;padding:15px;border:1px solid #ddd;max-height:400px;overflow:auto;"></pre>
                    </div>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">Save Schema Data</button>
                </p>
            </form>
            
            <style>
                .button-hero { padding: 12px 24px !important; height: auto !important; font-size: 16px !important; }
                .schema-tabs { margin-top: 30px; }
                .tab-content { display: none; padding: 20px; background: #fff; border: 1px solid #ccd0d4; border-top: none; }
                .tab-content.active { display: block; }
                .card { background: #f9f9f9; padding: 20px; margin: 20px 0; border: 1px solid #ddd; }
                #analysis-status { font-weight: bold; padding: 10px; border-radius: 4px; }
                #analysis-status.loading { color: #0073aa; background: #e5f5fa; }
                #analysis-status.success { color: #46b450; background: #ecf7ed; }
                #analysis-status.error { color: #dc3232; background: #fef7f7; }
                .modal { display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; 
                         background-color: rgba(0,0,0,0.6); }
                .modal-content { background-color: #fff; margin: 5% auto; padding: 30px; border: 1px solid #888; 
                                width: 80%; max-width: 900px; border-radius: 8px; }
                .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
                .close:hover { color: #000; }
                .rich-snippet-preview { border: 1px solid #dfe1e5; border-radius: 8px; padding: 20px; 
                                       background: #fff; font-family: arial, sans-serif; max-width: 600px; }
                .rich-snippet-preview .title { color: #1a0dab; font-size: 20px; margin-bottom: 5px; }
                .rich-snippet-preview .url { color: #006621; font-size: 14px; margin-bottom: 5px; }
                .rich-snippet-preview .description { color: #545454; font-size: 14px; line-height: 1.58; }
                .rich-snippet-preview .stars { color: #f4b400; font-size: 16px; }
                .rich-snippet-preview .rating-text { color: #70757a; font-size: 13px; margin-left: 5px; }
                .rich-snippet-preview .info-items { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px; }
                .rich-snippet-preview .info-item { color: #70757a; font-size: 13px; }
                .validation-item { padding: 15px; margin: 10px 0; border-left: 4px solid #ddd; background: #f9f9f9; }
                .validation-item.valid { border-left-color: #46b450; background: #ecf7ed; }
                .validation-item.warning { border-left-color: #ffb900; background: #fff8e5; }
                .validation-item.error { border-left-color: #dc3232; background: #fef7f7; }
            </style>
        </div>
        <?php
    }
    
    public function ajax_analyze_site_content() {
        check_ajax_referer('medical_schema_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Insufficient permissions');
        
        $data = array(
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'email' => get_option('admin_email')
        );
        
        // Get logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo) $data['logo'] = $logo[0];
        }
        
        // Analyze pages for phone numbers and addresses
        $pages = get_pages(array('number' => 20));
        foreach ($pages as $page) {
            // Look for phone numbers
            if (empty($data['phone']) && preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $page->post_content, $matches)) {
                $data['phone'] = $matches[0];
            }
        }
        
        // Find contact page for address
        $contact_page = get_page_by_path('contact');
        if (!$contact_page) $contact_page = get_page_by_path('contact-us');
        if (!$contact_page) $contact_page = get_page_by_path('locations');
        
        $data['locations'] = array();
        
        if ($contact_page) {
            $content = $contact_page->post_content;
            
            // Try to extract address
            $location = array();
            
            // Street address
            if (preg_match('/(\d+\s+[\w\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr|Court|Ct|Circle|Cir|Way|Parkway|Pkwy)\.?)/i', $content, $matches)) {
                $location['street'] = trim($matches[1]);
            }
            
            // City, State, Zip
            if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*),\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/', $content, $matches)) {
                $location['city'] = $matches[1];
                $location['state'] = $matches[2];
                $location['postal'] = $matches[3];
            }
            
            // Phone for this location
            if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content, $matches)) {
                $location['phone'] = $matches[0];
            }
            
            if (!empty($location['street']) || !empty($location['city'])) {
                $location['name'] = 'Main Office';
                $data['locations'][] = $location;
            }
        }
        
        // Look for multiple locations in location/contact pages
        $location_pages = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => 5,
            's' => 'location OR office OR address'
        ));
        
        foreach ($location_pages as $loc_page) {
            if ($loc_page->ID == ($contact_page->ID ?? 0)) continue; // Skip if already processed
            
            $content = $loc_page->post_content;
            $location = array();
            
            // Extract location name from title or content
            $location['name'] = $loc_page->post_title;
            
            // Street address
            if (preg_match('/(\d+\s+[\w\s]+(?:Street|St|Avenue|Ave|Road|Rd|Boulevard|Blvd|Lane|Ln|Drive|Dr)\.?)/i', $content, $matches)) {
                $location['street'] = trim($matches[1]);
            }
            
            // City, State, Zip
            if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*),\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)/', $content, $matches)) {
                $location['city'] = $matches[1];
                $location['state'] = $matches[2];
                $location['postal'] = $matches[3];
            }
            
            // Phone
            if (preg_match('/\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $content, $matches)) {
                $location['phone'] = $matches[0];
            }
            
            if (!empty($location['street']) && count($data['locations']) < 5) {
                $data['locations'][] = $location;
            }
        }
        
        // Find physicians
        $data['physicians'] = array();
        
        // Search for doctor/physician posts and pages
        $doctor_posts = get_posts(array(
            'post_type' => array('page', 'post', 'team', 'doctor', 'physician', 'provider', 'staff'),
            'posts_per_page' => 20,
            's' => 'doctor OR physician OR dr OR MD DO'
        ));
        
        foreach ($doctor_posts as $doctor) {
            $title = $doctor->post_title;
            $content = $doctor->post_content;
            
            // Check if this looks like a doctor profile
            $is_doctor = (
                stripos($title, 'dr.') !== false || 
                stripos($title, 'dr ') !== false || 
                stripos($title, 'doctor') !== false ||
                stripos($content, 'physician') !== false ||
                preg_match('/\b(MD|DO|DDS|DMD)\b/', $content)
            );
            
            if ($is_doctor) {
                $physician = array(
                    'name' => strip_tags($title)
                );
                
                // Clean up name - remove "Dr." prefix if it's in the title
                $physician['name'] = preg_replace('/^(Dr\.?|Doctor)\s+/i', '', $physician['name']);
                
                // Extract specialty from content
                if (preg_match('/(?:specialty|specializes? in|board certified in)[\s:]+([^<.\n]{10,50})/i', $content, $matches)) {
                    $physician['specialty'] = trim(strip_tags($matches[1]));
                }
                
                // Look for common titles
                if (preg_match('/\b(Cardiologist|Dermatologist|Pediatrician|Internist|Surgeon|Psychiatrist|Neurologist|Oncologist|Radiologist|Anesthesiologist|Orthopedic|Family Medicine|General Practice|Internal Medicine)\b/i', $content, $matches)) {
                    if (empty($physician['specialty'])) {
                        $physician['specialty'] = $matches[1];
                    }
                    $physician['title'] = $matches[1];
                }
                
                // Get bio (first 50 words of content)
                $bio = strip_tags($content);
                $bio = preg_replace('/\s+/', ' ', $bio); // Clean whitespace
                $physician['bio'] = wp_trim_words($bio, 50);
                
                // Get featured image
                if (has_post_thumbnail($doctor->ID)) {
                    $physician['image'] = get_the_post_thumbnail_url($doctor->ID, 'medium');
                }
                
                // Only add if we have a reasonable name (not just generic titles)
                if (!empty($physician['name']) && 
                    strlen($physician['name']) > 3 && 
                    !preg_match('/^(our|meet|about|staff|team|doctors?|physicians?)$/i', $physician['name']) &&
                    count($data['physicians']) < 15) {
                    $data['physicians'][] = $physician;
                }
            }
        }
        
        // Find services
        $data['services'] = array();
        $services_page = get_page_by_path('services');
        if (!$services_page) $services_page = get_page_by_path('our-services');
        if (!$services_page) $services_page = get_page_by_path('treatments');
        
        if ($services_page) {
            // Extract service names from headings
            if (preg_match_all('/<h[2-4][^>]*>(.*?)<\/h[2-4]>/i', $services_page->post_content, $matches)) {
                foreach (array_slice($matches[1], 0, 15) as $service_name) {
                    $service_name = strip_tags($service_name);
                    $service_name = trim($service_name);
                    
                    // Filter out generic headings
                    if (strlen($service_name) > 5 && 
                        strlen($service_name) < 100 &&
                        !preg_match('/^(our|about|why|what|when|where|how|contact|home|services)$/i', $service_name)) {
                        $data['services'][] = array('name' => $service_name);
                    }
                }
            }
        }
        
        wp_send_json_success($data);
    }
    
    public function ajax_save_schema_data() {
        check_ajax_referer('medical_schema_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Insufficient permissions');
        
        parse_str($_POST['formData'], $form_data);
        $schema_data = $form_data['schema'] ?? array();
        update_option($this->option_name, $schema_data);
        wp_send_json_success('Data saved successfully');
    }
    
    public function ajax_validate_schema() {
        check_ajax_referer('medical_schema_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Insufficient permissions');
        
        $schema_data = get_option($this->option_name, array());
        $schema = $this->generate_schema_json($schema_data);
        
        $results = array();
        $required = array('@context', '@type', 'name');
        foreach ($required as $field) {
            $results[] = array(
                'type' => empty($schema[$field]) ? 'error' : 'valid',
                'field' => $field,
                'message' => empty($schema[$field]) ? "Required field '$field' missing" : "Field '$field' present"
            );
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_preview_rich_snippet() {
        check_ajax_referer('medical_schema_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Insufficient permissions');
        
        $data = get_option($this->option_name, array());
        $name = $data['name'] ?? 'Your Practice Name';
        $url = $data['url'] ?? home_url();
        $desc = $data['description'] ?? 'Your practice description...';
        
        $html = '<div class="rich-snippet-preview">';
        $html .= '<div class="title">' . esc_html($name) . '</div>';
        $html .= '<div class="url">' . esc_html($url) . '</div>';
        $html .= '<div class="description">' . esc_html($desc) . '</div>';
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    public function output_schema_markup() {
        $data = get_option($this->option_name, array());
        if (empty($data['enabled'])) return;
        
        $schema = $this->generate_schema_json($data);
        if (!empty($schema)) {
            echo "\n<!-- Medical Schema -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            echo "\n</script>\n";
        }
    }
    
    private function generate_schema_json($data) {
        if (empty($data['name'])) return null;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $data['type'] ?? 'MedicalBusiness',
            'name' => $data['name'],
            'url' => $data['url'] ?? home_url()
        );
        
        if (!empty($data['description'])) $schema['description'] = $data['description'];
        if (!empty($data['logo'])) {
            $schema['logo'] = $data['logo'];
            $schema['image'] = $data['logo'];
        }
        if (!empty($data['phone'])) $schema['telephone'] = $data['phone'];
        if (!empty($data['email'])) $schema['email'] = $data['email'];
        
        // Add first location as main address
        if (!empty($data['locations']) && is_array($data['locations'])) {
            $first_location = reset($data['locations']);
            if (!empty($first_location['street'])) {
                $schema['address'] = array(
                    '@type' => 'PostalAddress',
                    'streetAddress' => $first_location['street'] ?? '',
                    'addressLocality' => $first_location['city'] ?? '',
                    'addressRegion' => $first_location['state'] ?? '',
                    'postalCode' => $first_location['postal'] ?? '',
                    'addressCountry' => 'US'
                );
            }
        }
        
        if (!empty($data['rating_value']) && !empty($data['review_count'])) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $data['rating_value'],
                'reviewCount' => $data['review_count']
            );
        }
        
        if (!empty($data['accepting_patients'])) {
            $schema['isAcceptingNewPatients'] = true;
        }
        
        // Add social media profiles
        if (!empty($data['social']) && is_array($data['social'])) {
            $urls = array();
            foreach ($data['social'] as $url) {
                if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) $urls[] = $url;
            }
            if (!empty($urls)) $schema['sameAs'] = $urls;
        }
        
        // Add physicians
        if (!empty($data['physicians']) && is_array($data['physicians'])) {
            $physicians = array();
            foreach ($data['physicians'] as $physician) {
                if (!empty($physician['name'])) {
                    $physician_schema = array(
                        '@type' => 'Physician',
                        'name' => $physician['name']
                    );
                    
                    if (!empty($physician['specialty'])) {
                        $physician_schema['medicalSpecialty'] = $physician['specialty'];
                    }
                    if (!empty($physician['title'])) {
                        $physician_schema['jobTitle'] = $physician['title'];
                    }
                    if (!empty($physician['bio'])) {
                        $physician_schema['description'] = $physician['bio'];
                    }
                    if (!empty($physician['image'])) {
                        $physician_schema['image'] = $physician['image'];
                    }
                    
                    $physicians[] = $physician_schema;
                }
            }
            if (!empty($physicians)) {
                $schema['employee'] = $physicians;
            }
        }
        
        // Add services
        if (!empty($data['services']) && is_array($data['services'])) {
            $services = array();
            foreach ($data['services'] as $service) {
                if (!empty($service['name'])) {
                    $services[] = array(
                        '@type' => 'MedicalProcedure',
                        'name' => $service['name'],
                        'description' => $service['description'] ?? ''
                    );
                }
            }
            if (!empty($services)) {
                $schema['hasOfferCatalog'] = array(
                    '@type' => 'OfferCatalog',
                    'name' => 'Medical Services',
                    'itemListElement' => $services
                );
            }
        }
        
        return $schema;
    }
}

new Medical_Schema_Plugin();