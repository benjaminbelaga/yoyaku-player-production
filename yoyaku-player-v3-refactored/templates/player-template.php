<?php
/**
 * Player template for YOYAKU Player V3
 * 
 * Available variables:
 * - $player_atts: Shortcode attributes array
 * - $player_product: WC_Product object
 * 
 * @package YoyakuPlayerV3
 * @since 6.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Extract variables for easier use
$product_id = $player_atts['product_id'];
$autoplay = $player_atts['autoplay'];
$custom_class = $player_atts['class'];
$custom_style = $player_atts['style'];

// Build classes
$classes = array('yoyaku-player-shortcode', 'ypv3-shortcode');
if (!empty($custom_class)) {
    $classes[] = esc_attr($custom_class);
}
$class_string = implode(' ', $classes);

// Build inline style
$style_string = !empty($custom_style) ? 'style="' . esc_attr($custom_style) . '"' : '';
?>

<div class="<?php echo esc_attr($class_string); ?>" <?php echo $style_string; ?>>
    <button class="yoyaku-play-button ypv3-play-button" 
            data-product-id="<?php echo esc_attr($product_id); ?>"
            data-autoplay="<?php echo esc_attr($autoplay); ?>"
            type="button"
            aria-label="<?php esc_attr_e('Play', 'yoyaku-player-v3'); ?>">
        <span class="ypv3-play-icon">▶</span>
        <span class="ypv3-play-text">
            <?php 
            /* translators: %s: Product name */
            printf(
                esc_html__('Play %s', 'yoyaku-player-v3'),
                esc_html($player_product->get_name())
            ); 
            ?>
        </span>
    </button>
</div>