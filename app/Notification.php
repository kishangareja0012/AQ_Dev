<?php

namespace App;

class Notification
{
    /**
     * Date : 27-11-2020, Friday
     */
    public function sendPushNotification($title, $description, $device_token, $image = '', $device_type = 0, $json_quote)
    {
        $quote_data = json_decode($json_quote);
        $url = 'https://fcm.googleapis.com/fcm/send';
        // $registration_ids = $device_token;
        $device_token = "cM6NtwpdRGCxtqyBcrDY0c:APA91bFSjKXOfPZTr-kFoMXLCa7-6gPduanYR3HL4owLjudwZfhCREfHLiWYVf2_CiMDYKoM9j98ZGfRI0fW_iACtdFneKOZ84azYk6HfenEsy0T-b84WpenAFlmRkX6_Iv_cQFDgyMs";
        $message = array(
            "title" => $title,
            'body' => $description,
            "description" => $description,
            "image" => "",
            'sound' => true,
            "item" => $quote_data->item,
            "item_description" => $quote_data->item_description,
            "location" => $quote_data->location,
            "image" => $quote_data->item_sample,
            "created_at" => $quote_data->created_at,
            "updated_at" => $quote_data->updated_at,
            "quote_id" => $quote_data->quote_id,
            "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
        );
        $extraNotificationData = [
            "message" => $message,
            "moredata" => '',
            "item" => $quote_data->item,
            "item_description" => $quote_data->item_description,
            "location" => $quote_data->location,
            "image" => $quote_data->item_sample,
            "created_at" => $quote_data->created_at,
            "updated_at" => $quote_data->updated_at,
            "quote_id" => $quote_data->quote_id,
            "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
        ];
        if ($device_type) {
            // Send IOS Notification
            /* $fields['notification'] = array
                (
                'title' => $title,
                'body' => $description,
                'sound' => 'mySound',
                "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
            );
            $fields['to'] = $device_token; */
            $fields = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $device_token, //single token
                'notification' => $message,
                'data' => $extraNotificationData,
                // 'quote_data' => $json_quote,
                "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
            ];
        } else {
            // Send ANDROID Notification
            $fields = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $device_token, //single token
                'notification' => $message,
                'data' => $extraNotificationData,
                "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
            ];
        }

        $headers = array(
            'Authorization:key=' . GOOGLE_API_KEY,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        // dd($result);
        if ($result === false) {
            die('Curl failed ' . curl_error());
        }
        return true;

        curl_close($ch);
        return $result;
        ob_flush();
    }
    public function sendPushNotificationToCustomer($title, $description, $device_token, $image = '', $device_type = 0, $json_quote)
    {
        $quote_data = json_decode($json_quote);
        $url = 'https://fcm.googleapis.com/fcm/send';
        // $registration_ids = $device_token;
        $device_token = "dMxMdg2wQQuwxkOy6b0Zso:APA91bE-dqkWFTProlA3khqj4gTbWn8BMBQEG8l_zuwb8JUITzMLjzRq5dYPufeGqtduQj9L8WDW_pQ5gn1EpMLCX71tL24cD4gtXfs5k6_RDcuLyvetaV_KMuhfMT7_8xGFU5iv714e";
        $message = array(
            "title" => $title,
            'body' => $description,
            "description" => $description,
            'sound' => true,
            "item" => $quote_data->item,
            "quote_id" => $quote_data->quote_id,
            "vendor_id" => $quote_data->vendor_id,
            'vendor_name' => $quote_data->vendor_name,
            'vendor_mobile' => $quote_data->vendor_mobile,
            'vendor_address' => $quote_data->vendor_address,
            'vendor_website' => $quote_data->vendor_website,
            "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
        );
        $extraNotificationData = [
            "message" => $message,
            "moredata" => '',
            "item" => $quote_data->item,
            "quote_id" => $quote_data->quote_id,
            "vendor_id" => $quote_data->vendor_id,
            'vendor_name' => $quote_data->vendor_name,
            'vendor_mobile' => $quote_data->vendor_mobile,
            'vendor_address' => $quote_data->vendor_address,
            'vendor_website' => $quote_data->vendor_website,
            "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
        ];
        if ($device_type) {
            // Send IOS Notification
            /* $fields['notification'] = array
                (
                'title' => $title,
                'body' => $description,
                'sound' => 'mySound',
                "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
            );
            $fields['to'] = $device_token; */
            $fields = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $device_token, //single token
                'notification' => $message,
                'data' => $extraNotificationData,
                // 'quote_data' => $json_quote,
                "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
            ];
        } else {
            // Send ANDROID Notification
            $fields = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $device_token, //single token
                'notification' => $message,
                'data' => $extraNotificationData,
                "click_action"=> "FLUTTER_NOTIFICATION_CLICK"
            ];
        }

        $headers = array(
            'Authorization:key=' . GOOGLE_API_CUSTOMER_KEY,
            'Content-Type: application/json',
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        
        //dd($result);
        if ($result === false) {
            die('Curl failed ' . curl_error());
        }
        return true;

        curl_close($ch);
        return $result;
        ob_flush();
    }
}