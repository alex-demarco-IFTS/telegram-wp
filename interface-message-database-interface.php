<?php
namespace IFQ\Telegram;

interface Message_Database_Interface {

    /*public static function get_ID_from_post($post_id);
    //public static function get_post_id_from_message($ID);
    //public static function get_content_from_message($ID);
    public static function get_content_from_post($post_id);
    //public static function get_channel_id_from_message($ID);
    public static function get_channel_id_from_post($post_id);
    //public static function get_author_id_from_message($ID);
    public static function get_author_id_from_post($post_id);
    //public static function get_status_from_message($ID);
    public static function get_status_from_post($post_id);
    //public static function get_sendable_from_message($ID);
    public static function get_sendable_from_post($post_id);
    //public static function get_schedule_time_from_message($ID);
    //public static function get_schedule_time_from_post($post_id);
    //public static function get_send_time_from_message($ID);
    public static function get_send_time_from_post($post_id);*/
    public static function search_db($search_params, $cols=array(),$limit=null,$return_type='object');
    public function set_db_messagedata();
    
}