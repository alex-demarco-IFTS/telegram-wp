<?php
namespace IFQ\Telegram;
/**
 * file per le funzionalità di invio articoli / messaggi aggiuntive
 */
/* 
 * opzioni di invio custom (a canali Telegram) nella pagina di modifica di un articolo
 */
// implementazione metabox
function add_sending_options_box() {
    add_meta_box( 'ifqtgbot_sending_options', 'Opzioni di invio su Telegram aggiuntive', 'IFQ\Telegram\post_sending_options_metabox_html', ['post'] );
}
add_action( 'add_meta_boxes', 'IFQ\Telegram\add_sending_options_box' );

// HTML form opzioni di invio
function post_sending_options_metabox_html( $post ) {
    // metadati articolo
    $existing_message = Message::search_db( array( 'post_id' => $post->ID ), array(), 1 ); // recupero informazioni dal database se messaggio già memorizzato
    if ( ! empty( $existing_message ) && $existing_message->get_send_time() ) { // se articolo già inviato in precedenza
        // informazioni invio avvenuto e scelta reinvio
        resend_html( $existing_message->get_channel_id(), $existing_message->get_schedule_time(), $existing_message->get_send_time() );
    }
    else {
        // scelta invio a Telegram
        send_radio_html( empty( $existing_message ) ? null : $existing_message->get_sendable() );
    }
    // messaggio aggiuntivo
    additional_message_html( empty( $existing_message ) ? '' : $existing_message->get_content() );
    // selettore canali (menu a tendina), funzione in 'ifq-telegram-bot-plugin.php'
    channel_selector_html( empty( $existing_message ) ? null : $existing_message->get_channel_id(), true );
    // invio programmato
    schedule_send_html( empty( $existing_message ) ? false : $existing_message->get_schedule_time() );
    // foglio di stile
    post_sending_options_stylesheet_html();
    // hidden input - annota che le modifiche all'articolo vengono effettuate dai metabox del plugin
    ?>
    <input type="hidden" name="ifqtgbot_edits" value="1">
    <?php
}

// HTML informazioni invio con checkbox per reinvio
function resend_html( $channel_id, $schedule_time, $send_time ) {
    ?>
    <p>Questo articolo è stato inviato sul canale Telegram <?php echo esc_html( $channel_id ); ?><?php /*if( $schedule_time ) { echo "by schedule"; }*/ ?> il <?php echo esc_html( wp_date( 'd M Y', strtotime( $send_time ) ) ); ?> alle ore <?php echo esc_html( wp_date( 'H:i', strtotime( $send_time ) ) ); ?>.</p>
    <input type="checkbox" class="ifqtgbot_resend_checkbox" name="ifqtgbot_resend" value="1" style="margin-top: 1px; margin-right: 7px"><label>Invia nuovamente</label><br><br>
    <?php
}

// HTML radio buttons scelta invio a Telegram
function send_radio_html( $sendable = null ) {
    if ( $sendable == null ) {
        $sendable = get_option( 'ifqtgbot_default_sending' );
    }
    ?>
    <p>Invia articolo tramite Telegram</p>
    <input type="radio" name="ifqtgbot_sendable" value="1" <?php echo $sendable ? "checked='checked'" : ""; ?>><label>Si</label><br>
    <input type="radio" name="ifqtgbot_sendable" value="0" <?php echo $sendable ? "" : "checked='checked'"; ?>><label>No</label><br>
    <?php
}

// HTML messaggio aggiuntivo
function additional_message_html( $content = '' ) {
    ?>
    <p>Messaggio aggiuntivo</p>
    <textarea class="ifqtgbot_message_textarea" name="ifqtgbot_message" rows="3" cols="40"><?php echo esc_textarea( $content ); ?></textarea><br><br>
    <?php
}

// HTML invio programmato (radio buttons e datetime picker)
function schedule_send_html( $schedule_time ) {
    $today_date = wp_date( 'Y-m-d\TH:i' );
    $datetime_picker_value_date = $schedule_time ? wp_date( 'Y-m-d\TH:i', strtotime( $schedule_time ) ) : $today_date;
    ?>
    <br><br>
    <input type="radio" name="ifqtgbot_schedule_send" value="0" <?php echo $schedule_time ? "" : "checked='checked'"; ?>><label>Invio immediato</label><br>
    <input type="radio" name="ifqtgbot_schedule_send" value="1" <?php echo $schedule_time ? "checked='checked'" : ""; ?>><label>Invio programmato</label>
    <input type="datetime-local" class="ifqtgbot_schedule_send_datetime_picker" id="ifqtgbot_schedule_send_time" name="ifqtgbot_schedule_send_time"
        min="<?php echo esc_attr( $today_date ); ?>" value="<?php echo esc_attr( $datetime_picker_value_date ); ?>">
    <?php
}

// foglio di stile per le opzioni di invio aggiuntive dell'articolo
function post_sending_options_stylesheet_html() {
    ?>
    <style>
        .ifqtgbot_message_textarea {
            resize: none;
        }
        label.ifqtgbot_channel_id_label {
            margin-right: 5px;
        }
        .ifqtgbot_schedule_send_datetime_picker {
            display: inline-block;
            margin-left: 5px
        }
    </style>
    <?php
}

// salva metadati articolo
function save_sendable_meta( $id ) {
    if ( isset( $_POST['ifqtgbot_sendable'] ) ) {
        $sendable = $_POST['ifqtgbot_sendable'];
    } else {
        $sendable = get_option( 'ifqtgbot_default_sending' );
    }
    update_post_meta( $id, 'ifqtgbot_sendable', $sendable ); // flag inviabile a Telegram
    remove_action( 'save_post', 'ifqtgbot_save_sendable_meta', 3 ); // impedisce la sovrascrittura involontaria
}
add_action( 'save_post', 'ifqtgbot_save_sendable_meta', 3 );
add_action( 'publish_post', 'ifqtgbot_save_sendable_meta', 3);

// (massima priorità) gestione reinvio: elimina il timestamp di invio precedente
function maybe_resend( $id ) {
    if ( ! empty( $_POST['ifqtgbot_resend'] ) && intval( $_POST['ifqtgbot_resend'] ) == 1 ) {
        delete_post_meta( $id, 'ifqtgbot_sent' ); // timestamp articolo inviato
    }
}
add_action( 'publish_post', 'IFQ\Telegram\maybe_resend', 1 );

// salva dati messaggio nella tabella del db - articoli e messaggi personalizzati
function save_db_messagedata( $is_post = true/*, $return_message_object = false*/ ) {
    $post_id = $is_post ? get_the_ID() : null;
    if ( ! $is_post || get_post_status( $post_id ) ) { // previene la chiamata appena si sceglie di comporre un nuovo articolo
        $search_param = array(
            'post_id' => $post_id,
            'status'  => array(
                'value'   => 'sent',
                'compare' => '!='
            )
        ); // parametri ricerca messaggio esistente
        $message = Message::search_db( $search_param, array(), 1 ); // object
        $new_message_status = $is_post ? corresponding_message_status( get_post_status( $post_id ) ) : 'published';
        $selected_channel_id = fetch_channel_id();
        $selected_schedule_time = $_POST['ifqtgbot_schedule_send'] ? $_POST['ifqtgbot_schedule_send_time'] : null;
        $update_author = true;
        if ( $message ) { // se messaggio già esistente
            if ( ! ( $message->get_content() != $_POST['ifqtgbot_message'] ||
                 $message->get_channel_id() != $selected_channel_id ||
                 $message->get_schedule_time() != $selected_schedule_time ) ) {
                if ( $message->get_status() == $new_message_status ) { // se nessun campo è stato modificato e stato invariato esce dalla funzione
                    return;
                }
                $update_author = false; // se i campi non sono stati modificati non aggiorna l'autore
            }
            $message->set_schedule_time(
                update_existing_message_scheduling(
                    $message->get_status(), $message->get_schedule_time(), $new_message_status, $selected_schedule_time ) ); // gestione schedulazione
        } else { // se messaggio nuovo
            $message = new Message();
            $message->set_post_id( $post_id ); // se messaggio personalizzato = null
            if ( ! $is_post || ( $is_post && $new_message_status == 'published' && ! $selected_schedule_time ) ) { // se nuovo messaggio personalizzato oppure nuovo articolo pubblicato con 'send now' imposta data-ora di schedulazione correnti
                $selected_schedule_time = get_current_local_datetime( 'STRING' );
            }
            $message->set_schedule_time( $selected_schedule_time );
        }
        if ( ! $is_post || ( $new_message_status != 'deleted' && ( ! empty( $_POST['ifqtgbot_edits'] ) && $_POST['ifqtgbot_edits'] == 1 ) ) ) { // controllo modifiche campi
            $message->set_content( sanitize_textarea_field( wp_unslash( $is_post ?  $_POST['ifqtgbot_message'] : $_POST['ifqtgbot_custom_message'] ) ) );
            $message->set_channel_id( $selected_channel_id );
            if ( $update_author ) {
                $message->set_author_id( get_current_user_id() );
            }
            $message->set_send_time( null ); // delegato allo script
        }
        $message->set_status( $new_message_status );
        /*$message_ID = */$message->set_db_messagedata( /*true*/ ); // inserisce/aggiorna sul database
        remove_action( 'save_post', 'IFQ\Telegram\save_db_messagedata', 3 );
        /*if($return_message_object) {
            return $message;
        }*/
        // return $message_ID;
    }
}
add_action( 'save_post', 'IFQ\Telegram\save_db_messagedata', 12 );
add_action( 'publish_post', 'IFQ\Telegram\save_db_messagedata', 12 );

// preleva l'id del canale Telegram
function fetch_channel_id() {
    // quando selezionato dall'autore
    if ( ! empty($_POST['ifqtgbot_channel_id'] ) ) {
        return $_POST['ifqtgbot_channel_id'];
    }
    // ottiene opzione di default (da categoria o opzione)
    $current_post_selected_category_related_channel_id = get_term_meta( get_the_category( get_the_ID() )[0]->term_id, 'ifqtgbot_channel_id', true );
    return $current_post_selected_category_related_channel_id ? $current_post_selected_category_related_channel_id : get_option( 'ifqtgbot_default_channel_id' );
}

// gestisce aggiornamento di data-ora di schedulazione
function update_existing_message_scheduling( $old_message_status, $old_schedule_time, $new_message_status, $new_schedule_time ) {
    if ( $old_message_status == 'published' ) { // published -> ...
        if ( $new_message_status == 'published' && $old_schedule_time != $new_schedule_time ) { // published -> published e data-ora modificata
            $new_schedule_time = maybe_set_schedule_time( $old_schedule_time, $new_schedule_time );
            if ( ! $new_schedule_time ) {
                return $old_schedule_time;
            }
        }
    } else { // draft, trash -> ...
        if ( $new_message_status == 'published' ) { // draft, trash -> published
            $new_schedule_time = get_current_local_datetime( 'STRING' );
        }
    }
    return $new_schedule_time;
}

// effettua verifiche sulla schedulazione - previene invio ripetuto del messaggio
function maybe_set_schedule_time( $old_schedule_time, $new_schedule_time ) {
    if ( ! ( abs( strtotime( $old_schedule_time ) - get_current_local_datetime( 'TIMESTAMP' ) ) < ( 300 ) ) ) { // prevenzione invio ripetuto se messaggio schedulato /inviato da meno di 5 minuti
        if ( $new_schedule_time ) { // data-ora 'schedule send' selezionata o modificata
            if ( ! ( abs( strtotime( $old_schedule_time ) - strtotime( $new_schedule_time ) ) < ( 300 ) ) ) {
                return $new_schedule_time;
            }
        } else { // 'schedule send' deselezionata
            return get_current_local_datetime( 'STRING' );
        }
    }   
    return false;
}

// stabilisce lo stato del messaggio a partire da quello dell'articolo
function corresponding_message_status( $post_status ) {
    switch ( $post_status ) {
        case 'draft':
            return 'draft';
        case 'publish':
        case 'future':
            return 'published';
        case 'trash':
            return 'deleted';
        default:
            return null;
    }
}

/* 
* submenu per gestire l'invio di messaggi personalizzati ai canali Telegram
*/
// HTML pagina submenu custom message
function custom_message_page_html() {
    check_input_fields_script();
    ?>
    <div class="wrap">
        <h2>Invia messaggio personalizzato ad un canale Telegram</h1><br>
        <form id="ifqtgbot_custom_message_form" method="post" onsubmit="check_input_fields(event);">
        <!--<textarea id="ifqtgbot_custom_message" name="ifqtgbot_custom_message" rows="6" cols="63" style="resize:none;"></textarea><br><br>-->
        <?php wp_editor( 'Scrivi un messaggio...', 'ifqtgbot_custom_message' ); // editor di testo con opzioni di formattazione ?>
        <br>
        <?php channel_selector_html(); // funzione selettore canali (menu a tendina= in 'ifq-telegram-bot-plugin.php' ?>
        <br><br>
        <input type="submit" name="ifqtgbot_send_custom_message">
        </form>
    </div>
    <?php
}

// script controllo campi
function check_input_fields_script() {
    ?>
    <script type="text/javascript"> // script JavaScript per controllo campi
        function check_input_fields(event) {
            if(document.getElementById('ifqtgbot_custom_message').value == '') {
                alert('Inserisci messaggio');
                event.preventDefault();
                return false;
            }
            if(document.getElementById('ifqtgbot_channel_id').value == '') {
                alert('Inserisci id del canale');
                event.preventDefault();
                return false;
            }
            if(!confirm('Sei sicuro di voler inviare il messaggio al canale ' + document.getElementById('ifqtgbot_channel_id').value + '?')) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    </script>
    <?php
}

// aggiunge pagina submenu custom message
function custom_message_page() {
    add_submenu_page(
        'ifqtgbot_menu',
        'Invia messaggio personalizzato ad un canale Telegram',
        'Messaggio personalizzato',
        'publish_posts', // capability pubblicazione post
        'ifqtgbot_menu',
        'IFQ\Telegram\custom_message_page_html',
        0
    );
}
add_action( 'admin_menu', 'IFQ\Telegram\custom_message_page', 12 );

// form submit
function send_custom_message() {
    if ( current_user_can( 'publish_posts' ) && isset( $_POST['ifqtgbot_send_custom_message'] ) ) {
        if ( empty( $_POST['ifqtgbot_custom_message'] ) || empty( $_POST['ifqtgbot_channel_id'] ) ) {
            // messaggio di errore
            add_action( 'admin_notices', 'IFQ\Telegram\custom_message_error_notice' );
        } else {
            /*$custom_message_ID = ifqtgbot_save_db_messagedata(false);
            $custom_message = IFQTGbot_Message::search_db(array('ID' => $custom_message_ID), null, 1);
            ifqtgbot_send_message($custom_message);*/
            do_action( 'publish_post' );
            // messaggio inviato
            add_action( 'admin_notices', 'IFQ\Telegram\custom_message_update_notice' );
        }
    }
}
add_action( 'admin_init', 'IFQ\Telegram\send_custom_message' );

// wordpress notice errore di invio
function custom_message_error_notice() {
    ?>
    <div class="error notice">
        <p>Messaggio non inviato: campi non compilati correttamente.</p>
    </div>
    <?php
}

// wordpress notice invio completato
function custom_message_update_notice() {
    ?>
    <div class="updated notice">
        <p>Messaggio inviato.</p>
    </div>
    <?php
}