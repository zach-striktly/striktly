<?php
/**
 * Plugin Name: nuvoh2o-portal
 * Plugin URI: http://www.nuvoh2o.net/
 * Description: nuvoh2o portal setup 
 * Version: 1.0.0
 * Author: Zach Baker 
 * Author URI: http://www.zachlab.com
 * License: GPL2
 */
/*  Copyright 2015 Cozmoslabs (www.cozmoslabs.com)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Nuvoh2o_Client_Portal
{
    private $subdomain;
    private $defaults;
    private $fields;
    public $options;
 
    function __construct()
    {
    //add_action('init', array( $this, 'nv_create_post_type'));
    add_filter('the_content', array( $this, 'nv_restrict_content'));
    add_filter('user_row_actions', array( $this, 'nv_add_link_to_private_page'), 10, 2);
    add_action('admin_notices', array( $this, 'nv_admin_notices'));
//    add_action('admin_enqueue_scripts', array( $this, 'nv_enqueue_admin_scripts'));
    add_action('init', array( $this, 'nv_flush_rules'), 20);
    add_action('admin_menu', array( $this, 'nv_add_settings_page'));
    /* register the settings */
    add_action('admin_init', array( $this, 'nv_register_settings'));
//    add_action('admin_footer', 'media_selector_print_scripts');

    $this->defaults = [ 
                            'subdomain-name' => __('dealername.nuvoh2o.net','private-page'),
                            'dealer-name' => __('Generic Nuvoh2o Dealer', 'nuvoh2o'),
                            'dealer-address' => __('555 Nuvoh2o Dr.'."\r\n".'Salt Lake City, UT 84111', 'nuvoh2o'),
                            'portal-image' => __('//nuvoh2o.net/default-image.png', 'nuvoh2o')
                           ];
    foreach($this->defaults as $key => $def_value)
    { 
		$siteOption = get_site_option($key);
		
echo "<pre>\r\n".print_r($siteOption,true)."\r\n</pre>\r\n";
	if ($siteOption != false && strlen($siteOption) > 0)
	{
		$this->options[$key] = $siteOption;
		update_site_option($key,$siteOption);
		echo "Site option $key found, setting to $siteOption<br/>\r\n";
	} else {
		add_site_option($key,$def_value);
		$siteOption = get_site_option($key, $def_value, false);
		
		echo "Site option $key not found, setting $key to default value: $def_value<br/>\r\n";
		$this->options[$key] = $def_value;
	}
    }
 
        $this->subdomain = 'nuvoh2o-options';
	echo "<pre>\r\n".print_r($this->options,true)."\r\n</pre>\r\n";
foreach ($this->defaults as $key => $def_value)
{
	$this->options[$key] = get_site_option($key,$def_value,false);
	echo "$key :: ".$this->options[$key]."<br/>\t\n";
}
 
    }

    /**
     * Function that registers the post type
     */
    function nv_create_post_type() {
 
        $labels = array(
            'name'               => _x('Private Pages', 'post type general name', 'nuvoh2o'),
            'singular_name'      => _x('Private Page', 'post type singular name', 'nuvoh2o'),
            'menu_name'          => _x('Private Page', 'admin menu', 'nuvoh2o'),
            'name_admin_bar'     => _x('Private Page', 'add new on admin bar', 'nuvoh2o'),
            'add_new'            => _x('Add New', 'private Page', 'nuvoh2o'),
            'add_new_item'       => __('Add New Private Page', 'nuvoh2o'),
            'new_item'           => __('New Private Page', 'nuvoh2o'),
            'edit_item'          => __('Edit Private Page', 'nuvoh2o'),
            'view_item'          => __('View Private Page', 'nuvoh2o'),
            'all_items'          => __('All Private Pages', 'nuvoh2o'),
            'search_items'       => __('Search Private Pages', 'nuvoh2o'),
            'parent_item_colon'  => __('Parent Private Page:', 'nuvoh2o'),
            'not_found'          => __('No Private Pages found.', 'nuvoh2o'),
            'not_found_in_trash' => __('No Private Pages found in Trash.', 'nuvoh2o')
        );
 
        $args = array(
            'labels'             => $labels,
            'description'        => __('Description.', 'nuvoh2o'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => true,
            'supports'           => array('title', 'editor', 'thumbnail')
        );
 
//        if(!empty( $this->options['subdomain-name'])){
//            $args['rewrite'] = array('subdomain' => $this->options['subdomain-name']);
//        }
//        else{
//            $args['rewrite'] = array('subdomain' => $this->defaults['subdomain-name']);
//        }
//	$args['rewrite'] = !empty($this->options['dealer-name']) ? array('dealer-name' => $this->options['dealer-name']) : $args['rewrite'] = array('dealer-name' => $this->defaults['dealer-name']);
 
        register_post_type('private-page', $args);
    }
 
   /**
     * Function that creates the admin settings page under the Users menu
     */
    function nv_add_settings_page(){
        add_users_page('Nuvoh2o Settings', 'Nuvoh2o Settings', 'manage_options', 'nuvoh2o_settings', array( $this, 'nv_settings_page_content'));
    }


    /**
     * Function that outputs the content for the settings page
     */
    function nv_settings_page_content(){

    echo "<pre>POST:\r\n".print_r($_POST,false)."\r\n</pre>\r\n";
	if ( isset( $_POST['submit_image_selector'] ) && isset( $_POST['image_attachment_id'] ) ) :
		update_option( 'media_selector_attachment_id', absint( $_POST['image_attachment_id'] ) );
	endif;
	wp_enqueue_media();

	$my_saved_attachment_post_id = get_site_option('media_selector_attachment_id', 0, false);

	?><script type='text/javascript'>
		jQuery( document).ready( function( $) {
			// Uploading files
			var file_frame;
			var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
			var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
			jQuery('#upload_image_button').on('click', function( event){
				event.preventDefault();
				// If the media frame already exists, reopen it.
				if ( file_frame) {
					// Set the post ID to what we want
					file_frame.uploader.uploader.param('post_id', set_to_post_id);
					// Open frame
					file_frame.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = set_to_post_id;
				}
				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});
				// When an image is selected, run a callback.
				file_frame.on('select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();
					// Do something with attachment.id and/or attachment.url here
					$('#image-preview').attr('src', attachment.url).css('width', 'auto');
					$('#portal-image-input').attr('value',attachment.url)
					$('#image_attachment_id').val( attachment.id);
					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});
					// Finally, open the modal
					file_frame.open();
			});
			// Restore the main ID when the add media button is pressed
			jQuery('a.add_media').on('click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
		});
	</script><?php
        /* if the user pressed the generate button then generate pages for existing users */
        if( !empty( $_GET[ 'nv_generate_for_all' ]) && $_GET[ 'nv_generate_for_all' ] == true){
    echo "<pre>POST:\r\n".print_r($_POST,false)."\r\n</pre>\r\n";
	?><script language="javascript">alert("generate for all.");</script><?php
            $this->nv_create_private_pages_for_all_users();
        }
 
        ?>
        <div class="wrap form-wrap">
 
            <h2><?php _e('Nuvoh2o Custom Dealer Settings', 'nuvoh2o'); ?></h2>
 
            <?php settings_errors(); ?>
 
            <form method="POST" action="options.php">
 
                <?php echo "Setting Fields<br/>\r\n";
			settings_fields($this->subdomain); 
			echo "\r\n<br/>done setting fields<br/>\r\n"; ?>
 
                <div class="scp-form-field-wrapper">
                    <label class="scp-form-field-label" for="subdomain-name"><?php echo __('Sub Domain Name' , 'nuvoh2o') ?></label>
                    <input type="text" class="widefat" id="subdomain-name" name="nuvoh2o-options[subdomain-name]" value="<?php echo ( isset( $this->options['subdomain-name']) ? $this->options['subdomain-name'] : $this->defaults['subdomain-name']); ?>" />
                    <p class="description"><?php echo __('Website for portal', 'nuvoh2o'); ?></p>
                </div>
 
 
                <div class="scp-form-field-wrapper">
                    <label class="scp-form-field-label" for="dealer-name"><?php echo __('Nuvoh2o Dealer Name' , 'nuvoh2o') ?></label>
                    <input type="text" class="widefat" name="nuvoh2o-options[dealer-name]" id="dealer-name" value='<?php echo isset($this->options['dealer-name'])?$this->options['dealer-name']:$this->defaults['dealer-name']; ?>'>
                    <p class="description"><?php echo __('The name of the dealer', 'nuvoh2o'); ?></p>
                </div>
 
                <div class="scp-form-field-wrapper">
                    <label class="scp-form-field-label" for="dealer-address"><?php echo __('Dealer Name' , 'nuvoh2o') ?></label>
                    <textarea address="nuvoh2o-options[dealer-address]" id="dealer-address" class="widefat"><?php echo ( isset( $this->options['dealer-address']) ? $this->options['dealer-address'] : $this->defaults['dealer-address']); ?></textarea>
                    <p class="description"><?php echo __('The address of the dealer', 'nuvoh2o'); ?></p>
                </div>

                <div class="scp-form-field-wrapper">
                    <label class="scp-form-field-label" for="portal-image"><?php echo __('Portal Image', 'nuvoh2o') ?></label>
                    <!-- textarea name="nuvoh2o-options[portal-image]" id="portal-image" class="widefat"> php echo ( isset( $this->options['portal-image']) ? $this->options['portal-image'] : $this->defaults['portal-image']); </textarea> -->
				<div class='image-preview-wrapper'>
					<img id='image-preview' src='<?php echo isset($this->options['portal-image'])?$this->options['portal-image']:$this->defaults['portal-image']; ?>' height='200px' width='200px'><br/>
					<input type='text' id='portal-image-input' name='nuvoh2o-options[portal-image]' class="widefat" value='<?php echo isset($this->options['portal-image'])?$this->options['portal-image']:$this->defaults['portal-image']; ?>'>
				</div>
				<input id="upload_image_button" type="button" class="button" value="<?php _e('Upload image'); ?>" />
				<input type='hidden' name='image_attachment_id' id='image_attachment_id' value='<?php echo get_option('media_selector_attachment_id'); ?>'>
				<!--<input type="submit" name="submit_image_selector" value="Save" class="button-primary">-->
			<p class='description'><?php echo __('URL for dealer-image','nuvoh2o'); ?></p>
		</div>
<!--			<img id='portal-image-preview' src='' width=100 height=100 style='max-height: 100px; width: 100px;'>
		    </div>
		    <input id='upload_image_button' type='button' class='button' value='<?php echo (isset($this->options['portal-image'])?$this->options['portal-image']:$this->defaults['portal-image']); ?>'>
		    <input type='hidden' name='image_attachment_id' id='image_attachment_id' value=''>

                    <p class="description"><php echo __('URL for the portal(dealer) image.', 'nuvoh2o'); ?></p>
                </div>
-->
 
                <?php submit_button( __('Save Settings', 'nuvoh2o_settings')); ?>
 
            </form>
        </div>
    <?php
    }
 
    /**
     * Function that registers the settings for the settings page with the Settings API
     */
    public function nv_register_settings() {
    	echo "<pre>POST(register_settings):\r\n".print_r($_POST,true)."\r\n</pre>\r\n";

	foreach ($this->defaults as $key => $def_val)
	{
		if (isset($_POST) && !empty($_POST) && !empty($_POST[$key]) && isset($_POST[$key]))
		{
			$val = $_POST[$key];
			echo "<pre>val[$key]: $val\r\n</pre><br/>\r\n";
			$testme = get_site_option($key);
			if ($testme != false)
				update_site_option($key,$val);
			else
				add_site_option($key,$val);
		}
	}
//	print_r($this->subdomain);
	print_r();
	foreach ($this->subdomain as $key => $value)
	{
		$prevval = get_site_option($key);
		if ($prevval == false)
		{
			echo "<pre>val: $key not set. Adding.\r\n</pre><br/>\r\n";
			add_site_option($key,$value);
		} else {
			echo "<pre>val: $key is set as $prevval\r\n</pre><br/>\r\n";
			update_site_option($key,$value);
		}
		//else
//			add_site_option($key,$value);

	}
//        register_setting($this->subdomain, $this->subdomain);
    }
 /**
     * Function that creates the notice messages on the settings page
     */
    function nv_admin_notices(){
        if( !empty( $_GET['page']) && $_GET['page'] == 'nuvoh2o_settings') {
            if( !empty( $_GET['nv_generate_for_all']) && $_GET['nv_generate_for_all'] == true) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Successfully generated private pages for existing users.', 'nuvoh2o'); ?></p>
                </div>
                <?php
                if( !empty( $_REQUEST['settings-updated']) && $_GET['settings-updated'] == 'true') {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Settings saved.', 'nuvoh2o'); ?></p>
                    </div>
                <?php
                }
            }
        }
    }
 
    /**
     * Function that flushes the rewrite rules when we save the settings page
     */
    function nv_flush_rules(){
        if( isset( $_GET['page']) && $_GET['page'] == 'nuvoh2o_settings' && isset( $_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == 'true') {
            flush_rewrite_rules(false);
        }
    }
 
 
    /**
     * Function that filters the WHERE clause in the select for adjacent posts so we exclude private pages
     * @param $where
     * @param $in_same_term
     * @param $excluded_terms
     * @param $taxonomy
     * @param $post
     * @return mixed
     */
    function nv_exclude_from_post_navigation( $where, $in_same_term, $excluded_terms, $taxonomy, $post){
        if( $post->post_type == 'private-page'){
            $where = str_replace("'private-page'", "'do not show this'", $where);
        }
        return $where;
    }


}
$Nuvo_Object = new Nuvoh2o_Client_Portal();

?>
