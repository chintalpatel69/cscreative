<?php
// Color picker support added from wp
function cscreative_enqueue_color_picker($hook_suffix) {
    // Load the color picker CSS and JS
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
}

add_action('admin_enqueue_scripts', 'cscreative_enqueue_color_picker');


//Register for Setting options to database
function cscreative_register_theme_settings() {
    // Register the settings
    register_setting('cscreative_theme_options_group', 'cscreative_options', 'cscreative_options_validate');
    
    // Add settings sections and fields
    add_settings_section(
        'cscreative_general_section', 
        'General Settings', 
        'cscreative_general_section_callback', 
        'cscreative-theme-settings'
    );
    // Add settings sections and fields
    add_settings_section(
        'cscreative_general_section', 
        'General Settings', 
        'cscreative_general_section_callback', 
        'cscreative-theme-settings'
    );

    add_settings_field(
        'cscreative_logo',
        'Logo URL',
        'cscreative_logo_callback', 
        'cscreative-theme-settings',
        'cscreative_general_section'
    );

    add_settings_field(
        'cscreative_footer_text',
        'Footer Text',
        'cscreative_footer_callback',
        'cscreative-theme-settings',
        'cscreative_general_section'
    );

    add_settings_field(
        'cscreative_footer_logo',
        'Footer Logo',
        'cscreative_color_callback',
        'cscreative-theme-settings',
        'cscreative_general_section'
    );
}
add_action('admin_init', 'cscreative_register_theme_settings');

// Section callback
function cscreative_general_section_callback() {
    echo '<p>General settings for the CS Creative theme.</p>';
}

// Field callback for all setting fields
// Input field for Logo URL
function cscreative_logo_callback() {
    // Get the options from the database
    $options = get_option('cscreative_options');
    // Retrieve the value of the logo URL, if set
    $logo_url = isset($options['logo_url']) ? esc_attr($options['logo_url']) : '';
    ?>
    <input type="text" id="cscreative_logo" name="cscreative_options[logo_url]" value="<?php echo $logo_url; ?>" />
    <p class="description">Enter the URL for your logo.</p>
    <?php
}

// Input field for Footer Text
function cscreative_footer_callback() {
    // Get the options from the database
    $options = get_option('cscreative_options');
    // Retrieve the value of the footer text, if set
    $footer_text = isset($options['footer_text']) ? esc_attr($options['footer_text']) : '';
    ?>
    <input type="text" id="cscreative_footer_text" name="cscreative_options[footer_text]" value="<?php echo $footer_text; ?>" />
    <p class="description">Enter the text to display in the footer.</p>
    <?php
}

// Input field for Color with Color Picker
function cscreative_color_callback() {
    // Get the options from the database
    $options = get_option('cscreative_options');
    // Retrieve the value of the background color, if set
    $footer_logo = isset($options['footer_logo']) ? esc_attr($options['footer_logo']) : esc_attr($options['logo_url']) ;
    ?>
    <input type="text" id="cscreative_footer_logo" class="cscreative-footer_logo" name="cscreative_options[footer_logo]" value="<?php echo $footer_logo; ?>" />
    <p class="description">Select the Logo for footer.</p>
    <?php
}
