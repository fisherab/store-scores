<?php

/**
 * The Store Scores plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://stevefisher.org.uk/
 * @since             1.0.0
 * @package           store_scores
 *
 * Plugin Name:       Store Scores
 * Plugin URI:        https://github.com/fisherab/store-scores 
 * Description:       For management of club competition
 * Version:           1.0.0
 * Author:            Steve Fisher
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       store-scores
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

function store_scores_log($msg) {
    if (is_array($msg) || is_object($msg)){
        $msg = print_r($msg,true);
    }
    $file = plugin_dir_path( __FILE__ ) . '/log.txt'; 
    $fo = fopen( $file, "a" ); 
    fputs($fo,$msg . PHP_EOL);
    fclose($fo);
}

function store_scores_generate_response($type, $message){
    if($type == "success") $response = "<div class='success'>{$message}</div>";
    else $response = "<div class='error'>{$message}</div>";
    return $response;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'STORE_SCORES_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-store-scores-activator.php
 */
function activate_store_scores() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-store-scores-activator.php';
    Store_Scores_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-store-scores-deactivator.php
 */
function deactivate_store_scores() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-store-scores-deactivator.php';
    Store_Scores_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_store_scores' );
register_deactivation_hook( __FILE__, 'deactivate_store_scores' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-store-scores.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_store_scores() {

    $plugin = new Store_Scores();
    $plugin->run();

}
run_store_scores();
