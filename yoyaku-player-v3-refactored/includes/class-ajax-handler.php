<?php
/**
 * Ajax handler for YOYAKU Player V3
 * 
 * @package YoyakuPlayerV3
 * @since 6.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax handler class
 */
class Yoyaku_Player_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor ready for future use
    }
    
    /**
     * Initialize Ajax handlers
     */
    public function init() {
        // Register Ajax endpoints
        add_action('wp_ajax_yoyaku_player_v3_get_track', array($this, 'get_track'));
        add_action('wp_ajax_nopriv_yoyaku_player_v3_get_track', array($this, 'get_track'));
    }
    
    /**
     * Get track data via Ajax
     */
    public function get_track() {
        // Verify nonce for security
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        if (!wp_verify_nonce($nonce, 'yoyaku_player_v3_nonce')) {
            // Log for debugging but allow for backward compatibility
            error_log('YOYAKU Player V3: Nonce verification warning');
        }
        
        // Get and validate product ID
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array(
                'message' => __('Invalid product ID', 'yoyaku-player-v3')
            ));
            return;
        }
        
        // Get product
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array(
                'message' => __('Product not found', 'yoyaku-player-v3')
            ));
            return;
        }
        
        // Get playlist files from meta
        $playlist_files = get_post_meta($product_id, '_yoyaku_playlist_files', true);
        
        if (empty($playlist_files)) {
            wp_send_json_error(array(
                'message' => __('No playlist found for this product', 'yoyaku-player-v3')
            ));
            return;
        }
        
        // Parse tracks
        $tracks = $this->parse_playlist_files($playlist_files);
        
        // Get taxonomies data
        $artist_name = $this->get_taxonomy_name($product_id, 'musicartist', __('Unknown Artist', 'yoyaku-player-v3'));
        $label_name = $this->get_taxonomy_name($product_id, 'musiclabel', __('Unknown Label', 'yoyaku-player-v3'));
        
        // Build response data
        $response_data = array(
            'product_id' => $product_id,
            'title' => $product->get_name(),
            'artist' => $artist_name,
            'label' => $label_name,
            'cover' => $this->get_product_image_url($product),
            'tracks' => $tracks,
            'sku' => $product->get_sku() ?: $product_id,
            'price' => $product->get_price(),
            'currency' => get_woocommerce_currency(),
            'in_stock' => $product->is_in_stock()
        );
        
        // Allow filtering of response data
        $response_data = apply_filters('yoyaku_player_v3_track_data', $response_data, $product_id);
        
        // Log for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'YOYAKU Player V3: Product %d, Tracks: %d',
                $product_id,
                count($tracks)
            ));
        }
        
        // Send success response
        wp_send_json_success($response_data);
    }
    
    /**
     * Parse playlist files from meta
     * 
     * @param mixed $playlist_files Playlist data from meta
     * @return array Parsed tracks array
     */
    private function parse_playlist_files($playlist_files) {
        $tracks = array();
        
        // Handle serialized array format
        if (is_string($playlist_files)) {
            $playlist_data = maybe_unserialize($playlist_files);
        } else {
            $playlist_data = $playlist_files;
        }
        
        // Validate data
        if (!is_array($playlist_data)) {
            return $tracks;
        }
        
        // Parse each track
        foreach ($playlist_data as $index => $file) {
            // Skip invalid entries
            if (!is_array($file)) {
                continue;
            }
            
            // Handle multiple possible formats
            $track_name = '';
            $track_url = '';
            $track_bpm = null;
            $track_duration = null;
            
            // Format 1: track_name, track_file_url
            if (isset($file['track_name']) && isset($file['track_file_url'])) {
                $track_name = $file['track_name'];
                $track_url = $file['track_file_url'];
                $track_bpm = isset($file['track_bpm']) ? $file['track_bpm'] : null;
                $track_duration = isset($file['track_duration']) ? $file['track_duration'] : null;
            }
            // Format 2: name, file
            elseif (isset($file['name']) && isset($file['file'])) {
                $track_name = $file['name'];
                $track_url = $file['file'];
                $track_bpm = isset($file['bpm']) ? $file['bpm'] : null;
                $track_duration = isset($file['duration']) ? $file['duration'] : null;
            }
            // Skip if no valid format found
            else {
                continue;
            }
            
            // Validate URL
            if (empty($track_url)) {
                continue;
            }
            
            // Build track data
            $track_data = array(
                'index' => $index + 1,
                'name' => sanitize_text_field($track_name),
                'url' => esc_url_raw($track_url),
                'bpm' => $track_bpm ? intval($track_bpm) : null,
                'duration' => $track_duration ? sanitize_text_field($track_duration) : null
            );
            
            // Add to tracks array
            $tracks[] = $track_data;
        }
        
        return $tracks;
    }
    
    /**
     * Get taxonomy name for a product
     * 
     * @param int $product_id Product ID
     * @param string $taxonomy Taxonomy name
     * @param string $default Default value if not found
     * @return string Taxonomy term name
     */
    private function get_taxonomy_name($product_id, $taxonomy, $default = '') {
        $terms = get_the_terms($product_id, $taxonomy);
        
        if ($terms && !is_wp_error($terms) && !empty($terms)) {
            return $terms[0]->name;
        }
        
        return $default;
    }
    
    /**
     * Get product image URL
     * 
     * @param WC_Product $product Product object
     * @return string Image URL or placeholder
     */
    private function get_product_image_url($product) {
        $image_id = $product->get_image_id();
        
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                return $image_url;
            }
        }
        
        // Return WooCommerce placeholder
        return wc_placeholder_img_src('woocommerce_single');
    }
}