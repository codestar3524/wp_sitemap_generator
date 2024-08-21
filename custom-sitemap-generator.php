<?php  
/*  
Plugin Name: Custom Sitemap Generator  
Description: A plugin to generate a custom sitemap.  
Version: 1.0  
Author: WordPress dev  
*/  

defined('ABSPATH') or die('No script kiddies please!');  

function generate_custom_sitemap() {  
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';  
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';  

    $startOfWeek = new DateTime();
    $startOfWeek->setISODate((int)date('o'), (int)date('W'), 1); // 1 corresponds to Monday

    // Format the start of the week date as W3C format for lastmod
    $lastmod = $startOfWeek->format(DATE_W3C);


    // Add static pages with high priority  
    $pages = [  
        home_url('/') => 1.0,  
        wp_login_url() => 1.0,  
        wp_registration_url() => 1.0,  
        wc_get_cart_url() => 1.0,  
        home_url('/contact-us') => 1.0,  
        home_url('/plate-all-states') => 0.8,  
    ];  

    foreach ($pages as $url => $priority) {  
        $sitemap .= '<url>';  
        $sitemap .= '<loc>' . esc_url($url) . '</loc>';  
        $sitemap .= '<lastmod>' . $lastmod . '</lastmod>';  
        $sitemap .= '<changefreq>weekly</changefreq>';  
        $sitemap .= '<priority>' . $priority . '</priority>';  
        $sitemap .= '</url>';  
    }  

    global $wpdb;  
    $table_name = $wpdb->prefix . 'plates';  // Use prefix for compatibility with different installations  
    
    // Fetch unique states  
    $states = $wpdb->get_results("SELECT DISTINCT state FROM $table_name ORDER BY state");  

    // Add all states pages with specific priority  
    foreach ($states as $state) {  
        $url = home_url('/plate-by-state/?state=' . urlencode($state->state));  
        $sitemap .= '<url>';  
        $sitemap .= '<loc>' . esc_url($url) . '</loc>';  
        $sitemap .= '<lastmod>' . $lastmod . '</lastmod>';  
        $sitemap .= '<changefreq>weekly</changefreq>';  
        $sitemap .= '<priority>0.8</priority>';  
        $sitemap .= '</url>';  
    }  
    
    // Fetch plate slugs  
    $plate_slugs = $wpdb->get_results("SELECT DISTINCT plate_slug FROM $table_name ORDER BY plate_slug");  

    // Add all plate details pages with specific priority  
    foreach ($plate_slugs as $slug) {  
        $url = home_url('/plate-details/?plate=' . urlencode($slug->plate_slug));  
        $sitemap .= '<url>';  
        $sitemap .= '<loc>' . esc_url($url) . '</loc>';  
        $sitemap .= '<lastmod>' . $lastmod . '</lastmod>';  
        $sitemap .= '<changefreq>weekly</changefreq>';  
        $sitemap .= '<priority>0.8</priority>';  
        $sitemap .= '</url>';  
    }  

    $sitemap .= '</urlset>';  

    return $sitemap;  
}  

function custom_sitemap_rewrite_rule() {  
    add_rewrite_rule('^sitemap\.xml$', 'index.php?custom_sitemap=1', 'top');  
}  
add_action('init', 'custom_sitemap_rewrite_rule');  

function custom_sitemap_query_vars($vars) {  
    $vars[] = 'custom_sitemap';  
    return $vars;  
}  
add_filter('query_vars', 'custom_sitemap_query_vars');  

function custom_sitemap_template() {  
    if (get_query_var('custom_sitemap')) {  
        header('Content-Type: application/xml; charset=utf-8');  
        echo generate_custom_sitemap();  
        exit;  
    }  
}  
add_action('template_redirect', 'custom_sitemap_template');

function disable_sitemap_canonical_redirect($redirect_url, $requested_url) {
    if (strpos($requested_url, '/sitemap.xml') !== false) {
        return false;
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'disable_sitemap_canonical_redirect', 10, 2);