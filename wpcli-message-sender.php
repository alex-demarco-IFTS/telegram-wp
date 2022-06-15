<?php
namespace IFQ\Telegram;
/*
 * script di gestione invio messaggi a Telegram
 */
if ( php_sapi_name() == 'cli' ) {
    class Message_Sender_WPCLI  {

        static protected $_instance = null;

        public static function instance() {
            if ( is_null( static::$_instance ) ) {
                static::$_instance = new static();
            }
            return static::$_instance;
        }

        public function __construct() {
            add_action( 'init', array( __CLASS__, 'cli_init' ) );
        }

        public static function cli_init() {
            WP_CLI::add_command( 'ifqtgbot send-messages', array( __CLASS__, 'IFQ\Telegram\find_and_send_scheduled_messages' ) );
        }

        //cerca nel database tutti i messaggi con stato pubblicato e con timestamp uguale o precedente al momento corrente e li invia
        public static function find_and_send_scheduled_messages() {
            $search_param = array(
                'status' => 'published',
                'schedule_time' => array(
                                    'value'   => get_current_local_datetime( 'STRING' ),
                                    'compare' => '<=',
                                   )
            );
            $scheduled_messages = Message::search_db( $search_param );
            //$sent_messages_IDs = array();
            if( ! empty( $scheduled_messages ) ) {
                foreach ( $scheduled_messages as $scheduled_message ) {
                    $sendable = $scheduled_message->get_post_id() ? get_post_meta( $scheduled_message->get_post_id(), 'ifqtgbot_sendable', true ) : true; //se messaggio personalizzato: true, se articolo prende il valore di 'sendable' dai metadati
                    if ( $sendable ) {
                        /*$sent_messages_IDs[] = static::send_message($scheduled_message);*/
                        $scheduled_message->send();
                    }
                }
            }
            WP_CLI::log( "Done." );
            //return $sent_messages_IDs; //lista ID messaggi inviati
        }
    }

    Message_Sender_WPCLI::instance();
}