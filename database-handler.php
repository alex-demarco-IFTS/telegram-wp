<?php
namespace IFQ\Telegram;
/**
 * file di gestione delle tabelle personalizzate
 */
//versione database
global $ifqtgbot_db_version;
$ifqtgbot_db_version = '0.7';

//creazione / aggiornamento tabella messaggi sul db
function db_install() {
    global $wpdb;
    global $ifqtgbot_db_version;
    $installed_ver = get_option( 'ifqtgbot_db_version' );
    $charset_collate = $wpdb->get_charset_collate();
    if ( $installed_ver != $ifqtgbot_db_version ) {
        $table_name = $wpdb->prefix . 'ifqtgbot_messages';
        $messages_table_create_sql =
            "CREATE TABLE {$table_name} (
                ID            MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                post_id       MEDIUMINT(9) NULL,
                content       VARCHAR(4000) DEFAULT '' NOT NULL,
                author_id     MEDIUMINT(9) NOT NULL,
                channel_id    VARCHAR(50) NOT NULL,
                status        ENUM('draft', 'sent', 'published', 'deleted') NOT NULL,
                schedule_time TIMESTAMP NULL,
                send_time     TIMESTAMP NULL,
                PRIMARY KEY  (ID)
            );";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $messages_table_create_sql );
        update_option( 'ifqtgbot_db_version', $ifqtgbot_db_version );
    }
}