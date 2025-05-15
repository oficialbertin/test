<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'util.php';

use AfricasTalking\SDK\AfricasTalking;

class SMS {
    private $sms;

    public function __construct() {
        $username = Util::$username;
        $apiKey = Util::$apiKey;
        $AT = new AfricasTalking($username, $apiKey);
        $this->sms = $AT->sms();
    }

    public function sendSMS($message, $recipients) {
        $from = Util::$senderId;

        try {
            $result = $this->sms->send([
                'to'      => $recipients,
                'message' => $message,
                'from'    => $from
            ]);
            return true;
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return false;
        }
    }
} 