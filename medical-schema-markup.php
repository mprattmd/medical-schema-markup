<?php
/**
 * Plugin Name: Medical Schema Markup Generator Pro
 * Plugin URI: https://yoursite.com
 * Description: Automatically generates and manages schema markup for medical practice websites with rich snippets preview, multiple locations, and validation
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * Text Domain: medical-schema
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Medical_Schema_Plugin {
    
    private $option_name = 'medical_schema_data';
    
    public function __construct() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Frontend hooks
        add_action('wp_head', array($this, 'output_schema_markup'), 1);
        
        // AJAX hooks
        add_action('wp_ajax_analyze_site_content', array($this, 'ajax_analyze_site_content'));
        add_action('wp_ajax_save_schema_data', array($this, 'ajax_save_schema_data'));
        add_action('wp_ajax_validate_schema', array($this, 'ajax_validate_schema'));
        add_action('wp_ajax_preview_rich_snippet', array($this, 'ajax_preview_rich_snippet'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Medical Schema',
            'Medical Schema',
            'manage_options',
            'medical-schema',
            array($this, 'admin_page'),
            'dashicons-welcome-learn-more',
            30
        );
    }
    
    public function register_settings() {
        register_setting('medical_schema_settings', $this->option_name);
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_medical-schema') {
            return;
        }
        
        wp_enqueue_media();
        
        // Inline JavaScript
        add_action('admin_footer', array($this, 'output_admin_js'));
        
        wp_localize_script('jquery', 'medicalSchemaAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('medical_schema_nonce')
        ));
    }
    
    public function output_admin_js() {
        ?>
        <script type="text/javascript">
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
                       .html('<span class="dashicons dashicons-update-alt spinning"></span> Analyzing website content...');
                
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
                                   .html('<span class="dashicons dashicons-yes-alt"></span> Analysis complete! Data populated.');
                            
                            var data = response.data;
                            if (data.name) $('#practice-name').val(data.name);
                            if (data.description) $('#practice-description').val(data.description);
                            if (data.phone) $('#practice-phone').val(data.phone);
                            if (data.email) $('#practice-email').val(data.email);
                            if (data.logo) {
                                $('#practice-logo').val(data.logo);
                                $('#logo-preview').html('<img src="' + data.logo + '" style="max-width:200px;height:auto;" />');
                            }
                            
                            if (data.address) {
                                if (data.address.street) $('#address-street').val(data.address.street);
                                if