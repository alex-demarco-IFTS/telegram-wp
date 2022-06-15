<?php
namespace IFQ\Telegram;

// Telegram Bot API - invia messaggio ad una chat tramite bot - https://core.telegram.org/bots/api#sendmessage
// opzioni di formattazione: https://core.telegram.org/bots/api#formatting-options
function send_message_to_telegram_channel( $msg, $channel_id = null, $bot_token = null ) {
    $bot_token = is_null( $bot_token ) ? get_option( 'ifqtgbot_bot_token' ) : $bot_token;
    $api_url = 'https://api.telegram.org/bot' . $bot_token;
    $channel_id = is_null( $channel_id ) ? get_option( 'ifqtgbot_default_channel_id' ) : $channel_id;
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $api_url . '/sendMessage' );
    curl_setopt( $ch, CURLOPT_POST, 1 );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query(
        array(
            'chat_id'    => $channel_id,
            'text'       => $msg,
            'parse_mode' => 'HTML'
        )
    ));
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $server_output = curl_exec( $ch );
    curl_close( $ch );
    return $server_output;
}