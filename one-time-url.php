<?php
/**
 * @package One Time URL
 * @version 0.0.1
 */
/*
Plugin Name: One Time URL
Plugin URI: http://cto911.com/
Description: Create one time url.
Version: 0.0.2
Author: CTO911
Author URI: http://cto911.com/
License: GPLv2 or later
Text Domain: cto911.com
 */
?>
<?php

/**
 * Prevent Direct Access
 */

defined( 'ABSPATH' ) or die( 'Direct Access to This File is Not Allowed.' );


/**
 * Define Constants
 */

global $wpdb;
define( 'VERSION', '0.0.2' );
define( 'TABLE_NAME', $wpdb->prefix . 'one_time_url' );


/**
 * Add Scripts And Styles To Dashboard
 */

add_action( 'admin_enqueue_scripts', 'addOTUScripts' );
function addOTUScripts() {
    wp_enqueue_media();
    wp_enqueue_script( 'mainOTUjs', plugin_dir_url( __FILE__ ) . 'js/main.js', '', VERSION );

	wp_localize_script( 'mainOTUjs', 'url', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));

    wp_enqueue_style( 'mainOTUcss', plugin_dir_url( __FILE__ ) . 'css/main.css', '', VERSION );
}


/**
 * Flush Rewrites After Plugin Deactivation
 */

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );


/**
 * Flush Rewrites After Plugin Activation
 */

register_activation_hook( __FILE__, 'activatePlugin' );
function activatePlugin() {
    createDownloadUrl();
    flush_rewrite_rules();
    initDatabaseScheme();

    update_option( 'salt_str', get_option( 'salt_str', 'saltr-string' ) );
    update_option( 'valid_days', get_option( 'valid_days', 10 ) );
}


/**
 * Init Database Scheme
 */

function initDatabaseScheme() {
    global $wpdb;
    $charsetCollate = $wpdb->get_charset_collate();
    $tableName = TABLE_NAME;

    $sql = "CREATE TABLE $tableName (
		ID int(11) NOT NULL AUTO_INCREMENT,
		post_id int(11) NOT NULL,
		str_key VARCHAR(64) DEFAULT NULL,
		referer_req VARCHAR(8) DEFAULT NULL,
		http_referer VARCHAR(1024) DEFAULT NULL,
		created VARCHAR(1024) DEFAULT NULL,
		created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
		UNIQUE KEY id (ID)
	) $charsetCollate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}


/**
 * Create Download Page
 */

add_filter( 'template_redirect', 'downloadPage' );
function downloadPage() {
    $fileid = get_query_var('fileid');
    if ($fileid != ''):
        require_once( plugin_dir_path( __FILE__ ) . '/inc/download.php' );
        exit;
    endif;
}


/**
 * Create Download URL
 */

add_action( 'init', 'createDownloadUrl' );
function createDownloadUrl(){
    add_rewrite_rule(
        'fileid/([0-9a-f]+)/([0-9a-f]+)/?$',
        'index.php?fileid=$matches[1]&secret=$matches[2]',
        'top'
    );
}


/**
 * Register Query Var
 */

add_filter( 'query_vars', 'registerQueryVars' );
function registerQueryVars( $query_vars ){
    $query_vars[] = 'fileid';
    $query_vars[] = 'secret';
    return $query_vars;
}


/**
 * Create Settings Menu
 */

add_action( 'admin_menu', 'createOTUMenu' );
function createOTUMenu() {
    add_menu_page( 'One Time URL', 'One Time URL', 'manage_options', __FILE__, 'createOTUMenuPage', 'dashicons-admin-links', 31 );
}


/**
 * Create Settings Menu Page
 */

function createOTUMenuPage() { ?>
    <?php add_thickbox(); ?>

    <div class="wrap">
        <h1>One Time URL</h1>

        <h2 class="nav-tab-wrapper">
            <a href="#" id="tab-general" class="nav-tab nav-tab-active">General</a>
            <a href="#" id="tab-settings" class="nav-tab">Settings</a>
        </h2>

        <div id="tab-settings-page" style="display: none">
            <form method="post" action="options.php">
                <?php settings_fields( 'one-time-url-settings-group' ); ?>
                <?php do_settings_sections( 'one-time-url-settings-group' ); ?>
                <table class="form-table widefat">
                    <tr>
                        <td style="width:25%"><input type="number" name="valid_days" value="<?php echo esc_attr( get_option( 'valid_days' ) ); ?>"/></td>
                        <td>Set Default Valid Days</td>
                    </tr>
                    <tr>
                        <td style="width:25%"><input type="text" name="salt_str" value="<?php echo esc_attr( get_option( 'salt_str' ) ); ?>"/></td>
                        <td>Set URL Encrypt Key</td>
                    </tr>
                    <tr>
                        <td style="width:25%"><?php submit_button(); ?></td>
                        <td></td>
                    </tr>
                </table>
            </form>
        </div>

        <div id="tab-general-page">
            <table class="form-table widefat">
                <thead>
                    <tr>
                        <td>N</td>
                        <td>Expires</td>
                        <td>File Name</td>
                        <td>Shortcode</td>
                        <td></td>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    global $wpdb;
                    $results = $wpdb->get_results('SELECT * FROM ' . TABLE_NAME);
                    $i = 1;
                    if( sizeof($results) > 0 ): ?>
                        <?php foreach( $results as $res ): ?>

                            <?php
                            $postId = $res->post_id;
                            $ID = $res->ID;
                            $fileName = basename( get_attached_file($postId) );
                            $created = getPostMeta($ID, 'created');
                            $fileIdHash = getPostMeta($ID, 'str_key');
                            $refererReq = getPostMeta($ID, 'referer_req');
                            $httpReferer = getPostMeta($ID, 'http_referer');
                            $valid = strtotime( date($created[0]) ) + (24 * 3600 ) * $created[1];
                            ?>

                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo date('Y-m-d', $valid); ?></td>
                                <td><a href="<?php echo get_the_guid($postId); ?>" target="_blank"><?php echo $fileName; ?></a></td>
                                <td><textarea cols="80" rows="3"><?php echo generateOTUShortcode( $fileIdHash, $fileName, $httpReferer ); ?></textarea></td>
                                <td>
                                    <a href="#TB_inline?width=600&height=250&inlineId=url-content-<?php echo $ID; ?>" class="thickbox">Edit</a> /
                                    <a href="#" class="delete-OTU-meta" data-id="<?php echo $ID; ?>">Delete</a>
                                    <div id="url-content-<?php echo $ID; ?>" style="display:none;">
                                        <table class="form-table widefat">
                                            <tbody>
                                                <tr>
                                                    <td>Valid (days):</td>
                                                    <td><input type="number" id="valid-days-<?php echo $ID; ?>" value="<?php echo $created[1]; ?>"></td>
                                                </tr>
                                                <tr>
                                                    <td>Referer Required:</td>
                                                    <td><input type="checkbox" id="referer-required-<?php echo $ID; ?>" <?php if($refererReq == 'yes') echo 'checked'; ?>></td>
                                                </tr>
                                                <tr>
                                                    <td>Referer:</td>
                                                    <td><input type="text" id="link-referer-<?php echo $ID; ?>" value="<?php echo $httpReferer; ?>" style="width:100%;"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <input type="button" class="save-otu-item button-primary" data-id="<?php echo $ID; ?>" value="Save" style="margin-top:20px;">
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>
            <input type="button" id="fileupload" class="button-primary" value="Create URL" />
        </div>
<?php }


/**
 * Generate ShortCode
 */

function generateOTUShortcode( $fileIdHash, $fileName, $httpReferer ) {
    return '[one_time_url id="' . $fileIdHash . '" title="' . $fileName . '" referer="' . $httpReferer . '"]';
}


/**
 * Register Settings
 */

add_action( 'admin_init', 'registerOTUSettings' );
function registerOTUSettings() {
    register_setting( 'one-time-url-settings-group', 'valid_days' );
    register_setting( 'one-time-url-settings-group', 'salt_str' );
}


/**
 * Get Valid Days
 */

function checkExpiration($data) {
    $currDate = strtotime( date('Y-m-d H:i:s') );
    $createdDate = strtotime($data[0]);

    $diff = ($currDate - $createdDate) / (60 * 60 * 24);

    if( ($data[1] - $diff) < 0 ) {
        return false;
    } else {
        return $diff;
    }
}


/**
 * Create URL Meta Data
 */

add_action( 'wp_ajax_create_OTU_meta', 'createOTUMeta' );
add_action( 'wp_ajax_nopriv_create_OTU_meta', 'createOTUMeta' );
function createOTUMeta() {
	if( !isset($_POST['fileId']) || $_POST['fileId'] == '' ) {
		echo json_encode( array("status" => "Error", "statusMsg" => "File ID is empty") );
		exit;
	}

	$validDays = get_option( 'valid_days', 1 );

	$fileId = $_POST['fileId'];
	$strKey = md5(microtime());

    addPost( $fileId, $strKey, 'yes', '', array( date('Y-m-d H:i:s'), $validDays ) );

	echo json_encode( array("status" => "OK", "data" => $fileId) );
	exit;
}


/**
 * Save URL Meta
 */

add_action( 'wp_ajax_save_OTU_item', 'saveOTUMeta' );
add_action( 'wp_ajax_nopriv_save_OTU_item', 'saveOTUMeta' );
function saveOTUMeta() {
    if( !isset($_POST['ID']) || $_POST['ID'] == '' ||
        !isset($_POST['validDays']) || $_POST['validDays'] == '' ||
        !isset($_POST['http_referer']) || $_POST['http_referer'] == '' ||
        !isset($_POST['referer_required']) || $_POST['referer_required'] == '' ) {
            echo json_encode( array("status" => "Error", "statusMsg" => "Missing some data") );
            exit;
    }
    $ID = sanitize_text_field( $_POST['ID'] );
    $refererReq  = sanitize_text_field( $_POST['referer_required'] );
    $httpReferer = sanitize_text_field( $_POST['http_referer'] );

    $created = getPostMeta($ID, 'created');
    $created[1] = sanitize_text_field( $_POST['validDays'] );

    updatePostMeta( $ID, 'created', $created );
    updatePostMeta( $ID, 'referer_req', $refererReq );
    updatePostMeta( $ID, 'http_referer', $httpReferer );

    echo json_encode( array("status" => "OK", "data" => $ID) );
    exit;
}


/**
 * Delete URL Meta Data
 */

add_action( 'wp_ajax_delete_OTU_meta', 'deleteOTUMeta' );
add_action( 'wp_ajax_nopriv_delete_OTU_meta', 'deleteOTUMeta' );
function deleteOTUMeta() {
	if( !isset($_POST['ID']) || $_POST['ID'] == '' ) {
		echo json_encode( array("status" => "Error", "statusMsg" => "File ID is empty") );
		exit;
	}

	$ID = $_POST['ID'];
    deletePost($ID);

	echo json_encode( array("status" => "OK", "data" => $ID) );
	exit;
}


/**
 * Get Post ID By File ID
 */

function getPostId( $metaValue ) {
    global $wpdb;
    $tableName = TABLE_NAME;

    $ID = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $tableName WHERE str_key = %s", $metaValue) );
    if( $ID != '' )
        return $ID;

    return false;
}


/**
 * Register Shortcode
 */

add_shortcode( 'one_time_url', 'oneTimeUrlShortcode' );
function oneTimeUrlShortcode( $atts ) {
    $atts = shortcode_atts(array(
        'id'        => '',
        'referer'   => '',
        'title'     => 'Download'
    ), $atts, 'one_time_url');

    $secret = md5(get_option( 'salt_str' ) . getUserIP() . $atts['referer']);

    return '<a href="' . get_bloginfo('url') . '/fileid/' . $atts['id'] . '/' . $secret . '">' . $atts['title'] . '</a>';
}


/**
 * Get Users IP
 */

function getUserIP() {
    $ip = 'unknown';

    if ( strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile' ) || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'android') ) {
        $ip = "mobile";
    } elseif ( !empty($_SERVER['HTTP_CLIENT_IP']) ) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
        $ipArray = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
        $ip = trim($ipArray[count($ipArray) - 1]);
    } elseif ( !empty($_SERVER['REMOTE_ADDR']) ) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}


/**
 * Add New URL
 */

function addPost( $postID, $strKey = '', $refererReq = 'yes', $httpReferer = '', $created = array() ) {
    global $wpdb;
    $wpdb->insert( TABLE_NAME, array(
        'post_id'       => $postID,
        'str_key'       => $strKey,
        'referer_req'   => $refererReq,
        'http_referer'  => $httpReferer,
        'created'       => serialize($created) )
    );
}


/**
 * Delete URL
 */

function deletePost( $ID ) {
    global $wpdb;
    $wpdb->delete( TABLE_NAME, array( 'ID' => $ID ), array( '%d' ) );
}


/**
 * Get URL Meta
 */

function getPostMeta( $ID, $metaKey ) {
    global $wpdb;
    $tableName = TABLE_NAME;

    $result = $wpdb->get_var( $wpdb->prepare("SELECT $metaKey FROM $tableName WHERE ID = %d", $ID) );

    if( $result ) {
        if($metaKey == 'created') {
            $result = unserialize($result);
        }

        return $result;
    }

    return false;
}


/**
 * Update URL
 */

function updatePostMeta( $ID, $metaKey, $metaValue ) {
    global $wpdb;

    if( gettype($metaValue) == 'array' ) {
        $metaValue = serialize($metaValue);
    }

    $wpdb->update( TABLE_NAME, array( $metaKey => $metaValue ), array( 'ID' => $ID ) );
}


/**
 * Add Shortcode Button To Page/Post Content Editor Toolbar
 */

add_action( 'media_buttons', 'addOTUBtn', 15 );
function addOTUBtn() { ?>
    <a href="#TB_inline?&inlineId=one-time-url&height=500&width=730" id="one-time-url-insert" class="thickbox button"><span class="wp-media-buttons-icon"></span> Insert One Time URL</a>
    <div id="one-time-url" style="display:none;">
        <h4>Click the shortcode to insert</h4>
        <div id="one-time-url-list">
            <ul>
                <?php global $wpdb;
                $results = $wpdb->get_results('SELECT * FROM ' . TABLE_NAME);

                foreach( $results as $res ) {
                    $postId = $res->post_id;
                    $ID = $res->ID;
                    $fileName = basename( get_attached_file($postId) );
                    $fileIdHash = getPostMeta($ID, 'str_key');
                    $httpReferer = getPostMeta($ID, 'http_referer'); ?>

                    <li><?php echo generateOTUShortcode( $fileIdHash, $fileName, $httpReferer ); ?></li>

                <?php }; ?>
            </ul>
        </div>
    </div>
<?php }


/**
 * Shortcode Button Handler Script
 */

add_action( 'wp_enqueue_media', 'OTUMediaButtonJs' );
function OTUMediaButtonJs() {
    wp_enqueue_script( 'media_button', plugin_dir_url( __FILE__ ) . 'js/insert_shortcode_content.js', array('jquery'), VERSION );
}