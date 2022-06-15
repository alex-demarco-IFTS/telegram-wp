<?php
namespace IFQ\Telegram;
/**
 * file per le impostazioni del plugin
 */
/*
 * submenu impostazioni plugin
 */
//inizializzazione impostazioni
function settings_init() {
    register_setting( 'ifqtgbot_plugin_config', 'ifqtgbot_bot_token' , 'sanitize_text_field' );          //token del bot Telegram
    register_setting( 'ifqtgbot_plugin_config', 'ifqtgbot_default_channel_id' , 'sanitize_text_field' ); //id del canale Telegram di default
    register_setting( 'ifqtgbot_plugin_config', 'ifqtgbot_channel_ids' , 'sanitize_text_field' );        //id dei canali Telegram secondari
    register_setting( 'ifqtgbot_plugin_config', 'ifqtgbot_default_sending' );                            //impostazione di invio degli articoli di default
    add_settings_section( 'plugin_config_section', 'Impostazioni principali',
                        'IFQ\Telegram\plugin_config_section_callback', 'ifqtgbot_plugin_config' );
    add_settings_field( 'ifqtgbot_bot_token_field', 'Bot Token',
                        'IFQ\Telegram\bot_token_field_callback', 'ifqtgbot_plugin_config', 'plugin_config_section' );
    add_settings_field( 'ifqtgbot_channel_id_field', 'ID canale di default',
                        'IFQ\Telegram\channel_id_field_callback', 'ifqtgbot_plugin_config', 'plugin_config_section' );
    add_settings_field( 'ifqtgbot_channel_ids_field', 'ID canali secondari',
                        'IFQ\Telegram\channel_ids_field_callback', 'ifqtgbot_plugin_config', 'plugin_config_section' );
    add_settings_field( 'ifqtgbot_default_sending_field', 'Opzione di default di invio degli articoli ',
                        'IFQ\Telegram\default_sending_field_callback', 'ifqtgbot_plugin_config', 'plugin_config_section' );
    }
add_action( 'admin_init', 'IFQ\Telegram\settings_init' );

//HTML descrizione
function plugin_config_section_callback() {
    ?><p>Imposta i parametri di configurazione principali del plugin.</p><?php
}

//HTML input 'Bot Token'
function bot_token_field_callback() {
    $setting = get_option( 'ifqtgbot_bot_token' );
    ?><input type="password" size="36" name="ifqtgbot_bot_token" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>"><?php
}

//HTML input 'Default Channel ID'
function channel_id_field_callback() {
    $category_channel_id = get_option( 'ifqtgbot_default_channel_id' );
    ?><input type="text" name="ifqtgbot_default_channel_id" value="<?php echo isset( $category_channel_id ) ? esc_attr( $category_channel_id ) : ''; ?>"><?php
}

//HTML input 'Secondary Channel IDs'
function channel_ids_field_callback() {
    $channel_ids = get_option( 'ifqtgbot_channel_ids' );
    ?><textarea name="ifqtgbot_channel_ids" rows="8" cols="25" style="resize:none;"><?php echo isset( $channel_ids ) ? esc_attr( $channel_ids ) : ''; ?></textarea><?php
}

//HTML input 'Default post sending option'
function default_sending_field_callback() {
    $setting = get_option( 'ifqtgbot_default_sending' );
    ?>
    <input type="radio" name="ifqtgbot_default_sending" value="1" <?php echo intval( $setting ) ? "checked='checked'" : ""; ?>><label>Invia</label>
    <input type="radio" name="ifqtgbot_default_sending" value="0" <?php echo intval( $setting ) ? "" : "checked='checked'"; ?>><label>Non inviare</label>
    <?php
}

//aggiunge pagina opzioni
function options_page() {
    add_options_page(
        'IFQ Telegram Bot - Configurazione Plugin',
        'IFQ Telegram Bot Plugin',
        'manage_options',
        'ifqtgbot_configuration',
        'IFQ\Telegram\options_page_html'
    );
}
add_action( 'admin_menu', 'IFQ\Telegram\options_page' );

//HTML pagina opzioni
function options_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'ifqtgbot_messages', 'ifqtgbot_message', __( 'Settings Saved', 'ifqtgbot' ), 'updated' );
    }
    settings_errors( 'ifqtgbot_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'ifqtgbot_plugin_config' );
            do_settings_sections( 'ifqtgbot_plugin_config' );
            submit_button( 'Salva Impostazioni' );
            ?>
        </form>
    </div>
    <?php
}

/* 
* submenu - sezione statistiche messaggi (tabella database, programmati)
*/
//HTML pagina message statistics
function message_statistics_page_html() {
    ?>
    <div class="wrap">
        <h2>Visualizza statistiche messaggi</h1><br>
        <?php all_messages_field_html(); ?>
        <br><br>
        <?php
        scheduled_messages_field_html();
        messages_table_stylesheet_html();
        ?>
    </div>
    <?php
}

// HTML output 'Messages database table'
function all_messages_field_html() {
    $all_messages = Message::search_db();
    ?>
    <label>Tabella database messaggi</label>
    <br><br>
    <?php
    messages_table_html( $all_messages );
}

// HTML output 'Scheduled messages'
function scheduled_messages_field_html() {
    $search_param = array(
        'status' => 'published'/*,
        'schedule_time' => array(
                            'value' => date("Y-m-d H:i:s", (ifqtgbot_get_current_local_datetime('TIMESTAMP') + 120)),
                            'compare' => ">",
                        )
                    */);
    $scheduled_messages = Message::search_db( $search_param );
    // rimuove-esclude dalla lista i messaggi per i quali l'invio su Telegram è impostato a 'No'
    // presente BUG
    /*foreach ( $scheduled_messages as $index => $message ) {
        if ( $message->get_sendable() != 1 || $message->get_post_id() == 574 ) {

            error_log('post_id: '.print_r($message->get_post_id(),1).', sendable: '.print_r($message->get_sendable(),1));

            unset( $scheduled_messages[ $index ] );
        }
    }*/
    ?>
    <label>Coda messaggi in attesa di invio</label>
    <p>Messaggi totali: <?php echo esc_html( sizeof( $scheduled_messages ) ); ?></p>
    <?php
    messages_table_html( $scheduled_messages, false );
    /*?><br><form method="post">
        <input type="submit" name="ifqtgbot_run_scheduled_messages_sender_function_test_button" value="Run scheduled messages sender script"></input> <!--pulsante per inviare subito tutti i messaggi programmati-->
    </form><?php*/
}

/*TEST - pulsante per richiamare la funzione di invio messaggi schedulati
function ifqtgbot_run_scheduled_messages_sender_function_TEST() {
    if(current_user_can('publish_posts') && isset($_POST['ifqtgbot_send_all_scheduled_messages_test_button'])) {
        //ifqtgbot_find_and_send_scheduled_messages();
        add_action('admin_notices', 'ifqtgbot_all_scheduled_messages_sent_notice');
    }
}
add_action('admin_init', 'ifqtgbot_run_scheduled_messages_sender_function_TEST');

//wordpress notice invio completato
function ifqtgbot_all_scheduled_messages_sent_notice() {
    ?>
    <div class="updated notice">
        <p>All scheduled messages have been sent.</p>
    </div>
    <?php
}*/

//HTML tabella mesaggi
function messages_table_html( $messages_data, $show_send_time = true ) {
    ?>
    <table style="width: <?php echo $show_send_time ? "50%" : "44%"; ?>">
        <tr>
            <th>ID</th>
            <th>post_id</th>
            <th>content</th>
            <th>author_id</th>
            <th>channel_id</th>
            <th>status</th>
            <th>schedule_time&nbsp;&nbsp;&nbsp;&nbsp;</th>
            <?php if( $show_send_time ) : ?>
                <th>send_time&nbsp;&nbsp;&nbsp;&nbsp;</th>
            <?php endif; ?>
        </tr>
        <?php foreach ( $messages_data as $message ) : ?>
            <tr>
                <td><?php echo esc_html( $message->get_ID() ); ?></td>
                <td><?php echo $message->get_post_id() ? esc_html( $message->get_post_id() ) : "-"; ?></td>
                <td><?php echo esc_html( $message->get_content() ); ?></td>
                <td><?php echo esc_html( $message->get_author_id() ); ?></td>
                <td><?php echo esc_html( $message->get_channel_id()); ?></td>
                <td><?php echo esc_html( $message->get_status() ); ?></td>
                <td><?php echo $message->get_schedule_time() ? esc_html( $message->get_schedule_time() ) : "-"; ?></td>
                <?php if ( $show_send_time ) : ?>
                    <td><?php echo $message->get_send_time() ? esc_html( $message->get_send_time() ) : "-"; ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
}

//HTML foglio di stile tabella messaggi
function messages_table_stylesheet_html() {
    ?>
    <br><br>
    <style>
        label {
            font-weight: 600;
        }
        table {
            border: 1px solid #999;
            background: #fff;
            border-radius: 5px;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table tr:last-child > td {
            border-bottom: none;
        }
    </style>
    <?php
}

//aggiunge pagina submenu message statistics
function message_statistics_page() {
    add_submenu_page(
        'ifqtgbot_menu',
        'Visualizza statistiche messaggi',
        'Statistiche messaggi',
        'manage_options',
        'ifqtgbot_stats',
        'IFQ\Telegram\message_statistics_page_html',
        1
    );
}
add_action( 'admin_menu', 'IFQ\Telegram\message_statistics_page', 13 );

/*
* pagina aggiunta/modifica categorie - aggiunta opzione per associare un canale Telegram alle categorie
*/
//aggiunge campo per impostare il canale di default per la nuova categoria
function add_category_channel_field() {
    channel_selector_html( get_option( 'ifqtgbot_default_channel_id' ) );
    ?>
    <br><br>
    <script type="text/javascript">
        document.getElementById('ifqtgbot_channel_id').setAttribute('name', 'cat_meta[ifqtgbot_category_channel_id]');
    </script>
    <?php
}
add_action( 'category_add_form_fields', 'IFQ\Telegram\add_category_channel_field' );

//aggiunge campo per impostare il canale di default per la categoria esistente
function edit_category_channel_field( $term ) {
    $term_id = $term->term_id;
    $category_channel_id = get_term_meta( $term_id, 'ifqtgbot_channel_id', true );
    channel_selector_html( $category_channel_id );
    ?>
    <br><br>
    <style>
        label.ifqtgbot_channel_id_label {
            font-weight: 600;
            margin-right: 14%;
        }
    </style>
    <?php
}
add_action( 'category_edit_form', 'IFQ\Telegram\edit_category_channel_field' );

//salva il meta del canale di default per la categoria
function save_category_channel_meta( $term_id ) {
    if ( isset( $_POST['ifqtgbot_channel_id'] ) ) {
        update_term_meta( $term_id, 'ifqtgbot_channel_id', $_POST['ifqtgbot_channel_id'] );
    }
}
add_action( 'create_category', 'IFQ\Telegram\save_category_channel_meta' );
add_action( 'edited_category', 'IFQ\Telegram\save_category_channel_meta' );