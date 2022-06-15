<?php
namespace IFQ\Telegram;
/**
 * @package IFQ Telegram Bot
 * @version 0.1
 */
/*
  Plugin Name: IFQ Telegram Bot
  Plugin URI: http://www.ilfattoquotidiano.it
  Description: Plugin per la piattaforma de Il Fatto Quotidiano. Consente di inoltrare articoli e messaggi ad uno o più canali Telegram. Destinato ad uso interno all'azienda.
  Version: 0.1
  Author: Alessandro De Marco
  Author URI: https://www.seif-spa.it/
*/
/*richiede WP CLI
if (!defined('WP_CLI') && WP_CLI) {
    //Then we don't want to load the plugin
    return;
}*/
//DEBUG
error_reporting( E_ALL ); //to set the level of errors to log, E_ALL sets all warning, info , error
ini_set( "log_errors", true );
ini_set( "error_log", "C:\xampp\htdocs\php-errors.log" ); //send error log to log file specified here.
//DEBUG

require_once 'plugin-settings.php';
require_once 'telegram-api-interface.php';
require_once 'post-features.php';
require_once 'database-handler.php';
require_once 'class-message.php';
require_once 'interface-message-database-interface.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once dirname( __FILE__ ) . '/wpcli-message-sender.php';
}

//aggiunge top-level menu funzionalità plugin
function top_menu_page() {
    add_menu_page(
        'IFQ Telegram',
        'IFQ Telegram Plugin',
        'manage_options',
        'ifqtgbot_menu'
    );
}
add_action( 'admin_menu', 'IFQ\Telegram\top_menu_page', 11 );

//HTML selettore (menu a tendina) dei canali Telegram
function channel_selector_html( $selected = null, $show_default = false ) {
    $default_channel_id = get_option( 'ifqtgbot_default_channel_id' );
    $channel_ids = explode( ',', get_option( 'ifqtgbot_channel_ids' ) ); //diventa array
    $channel_ids = array_map( 'trim', $channel_ids ); //trim per ogni elemento (elimina caratteri in eccesso)
    array_unshift( $channel_ids, $default_channel_id ); //inserisce elemento all'inizio dell'array
    ?>
    <label class="ifqtgbot_channel_id_label">Canale Telegram</label>
    <select name="ifqtgbot_channel_id" id="ifqtgbot_channel_id_select">
    <?php if ( $show_default ) : ?>
        <option value="" <?php empty( $selected ) ? 'selected="selected"' : ''; ?>>Default</option>
    <?php endif; ?>
    <?php foreach( $channel_ids as $channel_id ) : ?>
        <option value="<?php echo $channel_id; ?>" <?php echo $channel_id == $selected ? 'selected="selected"' : ''; ?>><?php echo $channel_id; ?></option>
    <?php endforeach; ?>
    </select>
    <?php
}

//restituisce la data-ora corrente con il fuso orario locale nel formato desiderato         SOSTITUIRE CON wp_date
function get_current_local_datetime( $format ) {
    $datetime = new \DateTime( "now", new \DateTimeZone( "Europe/Rome" ) );
    switch ( $format ) {
        case 'TIMESTAMP':
            return $datetime->getTimestamp() + $datetime->getOffset(); //workaround per ottenere il timestamp dell'ora locale
        case 'STRING':
            return $datetime->format( "Y-m-d H:i:s" );
        default:
            return $datetime;
    }
}

//plugin basic hooks
function activate() {
    db_install();
    //add_action('ifqtgbot_cron_hook', 'ifqtgbot_send_post_to_channel');
}
register_activation_hook( __FILE__, 'IFQ\Telegram\activate' );

function deactivate() {
    //unschedule all cron tasks
    //$timestamp = wp_next_scheduled('ifqtgbot_cron_hook');
    //wp_unschedule_event($timestamp, 'ifqtgbot_cron_hook');
}
register_deactivation_hook( __FILE__, 'IFQ\Telegram\deactivate' );