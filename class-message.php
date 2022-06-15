<?php
namespace IFQ\Telegram;
/*
 * classe Messaggio
 */
//require "MessageDatabaseInterface.php";
class Message /*implements IFQTGbot_MessageDatabaseInterface {*/{
    private $ID;            // auto increment: non salvare nel database
    private $post_id;
    private $content;
    private $author_id;
    private $channel_id;
    private $status;
    private $sendable;      // salvato nei postmeta, non nel database
    private $schedule_time;
    private $send_time;

    /* parametro $data:
     * numeric -> ID,
     * array (un solo valore) -> post_id,
     * array (più valori) -> messaggio intero, funzione populate (da array a Ifqtgbot_Message))
     */
     function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->get_by_ID( $data );
        } elseif ( is_array( $data ) ) {
            $this->populate( $data );
        }
    }

    protected function get_by_ID( $data ) {
        global $wpdb;
        $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ifqtgbot_messages WHERE ID = %d", $data ) );
        if ( empty( $item ) || is_wp_error( $item ) ) {
            error_log( 'Invalid telegram message ' . $data );
            throw new \Exception( "Invalid telegram message id in object creation" . $data );
        }
        $this->populate( $item );
    }
    public static function get_ID_from_post( $post_id ) {
        $search_param = array( "post_id" => $post_id );
        return Message::search_db( $search_param, 'ID' );
    }
    function get_ID() {
        return $this->ID;
    }
    function set_ID( $ID ) {
        $this->ID = $ID;
    }

    /*public static function get_post_id_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'post_id');
    }*/
    function get_post_id() {
        return $this->post_id;
    }
    function set_post_id( $post_id ) {
        $this->post_id = $post_id;
    }
/*
    public static function get_content_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'content');
    }
    public static function get_content_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'content');
    }*/
    function get_content() {
        return $this->content;
    }
    function set_content( $content ) {
        $this->content = $content;
    }

/*    public static function get_channel_id_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'channel_id');
    }
    public static function get_channel_id_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'channel_id');
    }*/
    function get_channel_id() {
        return $this->channel_id;
    }
    function set_channel_id( $channel_id ) {
        $this->channel_id = $channel_id;
    }
/*
    public static function get_author_id_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'author_id');
    }
    public static function get_author_id_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'author_id');
    }*/
    function get_author_id() {
        return $this->author_id;
    }
    function set_author_id( $author_id ) {
        $this->author_id = $author_id;
    }
/*
    public static function get_status_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'status');
    }
    public static function get_status_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'status');
    }
    */
    function get_status() {
        return $this->status;
    }
    function set_status( $status ) {
        $this->status = $status;
    }
/*
    public static function get_sendable_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'sendable');
    }
    public static function get_sendable_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'sendable');
    }*/
    function get_sendable() {
        return $this->sendable;
    }
    function set_sendable( $sendable ) {
        $this->sendable = $sendable;
    }
/*
    public static function get_schedule_time_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'schedule_time');
    }
    public static function get_schedule_time_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'schedule_time');
    }*/
    function get_schedule_time() {
        return $this->schedule_time;
    }
    function set_schedule_time( $schedule_time ) {
        $this->schedule_time = $schedule_time;
    }
/*
    public static function get_send_time_from_message($ID)
    {
        $search_param = "ID = $ID";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'send_time');
    }
    public static function get_send_time_from_post($post_id)
    {
        $search_param = "post_id = $post_id";
        return Ifqtgbot_Message::get_db_messagedata($search_param, 'send_time');
    }*/
    function get_send_time() {
        return $this->send_time;
    }
    function set_send_time( $send_time ) {
        $this->send_time = $send_time;
    }

    private function populate( $array ) {
        $this->ID = $array['ID'];
        $this->post_id = $array['post_id'];
        $this->content = $array['content'];
        $this->author_id = $array['author_id'];
        $this->channel_id = $array['channel_id'];
        $this->status = $array['status'];
        $sendable = null;
        $sendable_from_array = empty( $array['sendable'] ) ? null : $array['sendable'];
        if ( is_numeric( $sendable_from_array ) ) {
            $sendable = $sendable_from_array;
        } else {
            $sendable_from_post_meta = get_post_meta( $array['post_id'], 'ifqtgbot_sendable', true );
            if ( $sendable_from_post_meta !== '' ) {
                $sendable = $sendable_from_post_meta;
            }
        }
        $this->sendable = $sendable;
        $this->schedule_time = $array['schedule_time'];
        $this->send_time = $array['send_time'];
    }

    public function send() {
        $message_content = $this->content;
        if ( $this->post_id ) {
            $message_content .= '\n' . get_permalink( $this->post_id ); //formato testo articolo
        }
        send_message_to_telegram_channel( $message_content, $this->channel_id );
        $this->send_time = get_current_local_datetime( 'STRING' );
        $this->status = 'sent';
        $this->set_db_messagedata();
    }

    // recupera dati messaggio direttamente dalla tabella del db
    public static function search_db( $search_params = array(), $cols = array(), $limit = null, $return_type = 'object' ) {
        global $wpdb;
        if ( is_array( $cols ) && ! empty( $cols ) ) {
            $cols_string = implode( ',', $cols );
        } elseif ( empty( $cols ) ) {
            $cols_string = '*';
        }
        $where = array();
        foreach ( $search_params as $k => $v ) {
            if ( is_array( $v ) ) {
                $where[] = $k . " " . $v['compare'] . " " . ( is_int( $v['value'] ) ? $v['value'] : "'" . $v['value'] . "'");
            } elseif ( is_int( $v ) ) {
                $where[] = $k . " = " . $v;
            } else {
                $where[] = $k . " = '" . $v . "'";
            }
        }
        if ( ! empty( $where ) ) {
            $where = implode( ' AND ', $where );
            $where = "WHERE " . $where;
        } else {
            $where = "";
        }
        $tablename = $wpdb->prefix . 'ifqtgbot_messages';
        $query = "SELECT {$cols_string} FROM {$tablename} {$where}";
        $query .= " ORDER BY ID desc";
        if ( ! empty( $limit ) && is_numeric( $limit ) ) {
            $query .= " LIMIT " . $limit;
        }
        $results = $wpdb->get_results( $query, 'ARRAY_A' ); // possibili risultati: singola variabile (array con un array di dimensione 1), singola riga (array con un array di dimensione 9), righe multiple (array con più array di dimensione 8), colonne multiple (array con più array di dimensione 1)
        if ( empty( $results ) ) {
            return null;
        }
        if ( ! empty( $cols ) ) {
            return ($limit == 1) ? array_shift( $results ) : $results;
        }
        if ( $limit == 1 ) {
            return ($return_type == 'object') ? new Message( $results[0] ) : $results[0]; // singola riga - messaggio
        }
        if ( $return_type == 'object' ) {
            $messages = array();
            foreach ( $results as $row ) {
                $messages[] = new Message( $row );
            }
            return $messages; // più righe - messaggi (oggetti)
        }
        return $results; // più righe - messaggi (array)
    }

    // inserisce/aggiorna dati messaggio direttamente sulla tabella del db
    public function set_db_messagedata( $return_ID = false ) {
        global $wpdb;
        $tablename = $wpdb->prefix . 'ifqtgbot_messages';
        $data = array(
            'post_id'       => $this->post_id,
            'content'       => $this->content,
            'author_id'     => $this->author_id,
            'channel_id'    => $this->channel_id,
            'status'        => $this->status,
            'schedule_time' => $this->schedule_time,
            'send_time'     => $this->send_time
        );
        $format = array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' ); // post_id, content, author_id, channel_id, status, schedule_time, send_time
        if ( $this->ID ) {
            $where = array( 'ID' => $this->ID );
            $wpdb->update( $tablename, $data, $where, $format, array('%d') );
        } else {
            $wpdb->insert( $tablename, $data, $format );
        }
        if ( $return_ID ) {
            return $this->ID ? $this->ID : $wpdb->insert_id;
        }
    }
}
