<?php
class Util {
    // Database configuration
    public static $host = "localhost";
    public static $user = "root";
    public static $pass = "";
    public static $db = "umuganda_connect_db";

    // Africa's Talking API configuration
    public static $apiKey = "atsk_dd62d69229f596a5080624a853ca4d81c98610b6ba9f0d50d7cb7471a130f911ef3b9997";
    public static $username = "sandbox";
    public static $senderId = "UMUGANDA Connect";

    // USSD Configuration
    public static $sessionTimeout = 60; // Session timeout in seconds
    public static $maxRetries = 3; // Maximum number of retries for invalid input

    // Menu Options
    public static $mainMenuOptions = [
        "1" => "Register for Umuganda",
        "2" => "View Upcoming Events",
        "3" => "Confirm Attendance",
        "4" => "Submit Feedback",
        "99" => "Exit"
    ];

    // Feedback Ratings
    public static $feedbackRatings = [
        "1" => "Very Poor",
        "2" => "Poor",
        "3" => "Average",
        "4" => "Good",
        "5" => "Excellent"
    ];

    // Navigation Options
    public static $navigationOptions = [
        "98" => "Go Back",
        "99" => "Main Menu"
    ];
} 