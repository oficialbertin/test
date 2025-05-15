<?php
require_once 'menu.php';
require_once 'util.php';

try {
    $conn = new PDO("mysql:host=" . Util::$host . ";dbname=" . Util::$db, Util::$user, Util::$pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Collect input from Africa's Talking
    $sessionId = $_POST["sessionId"];
    $serviceCode = $_POST["serviceCode"];
    $phoneNumber = $_POST["phoneNumber"];
    $text = $_POST["text"];

    // Middleware
    $menu = new Menu($text, $sessionId, $phoneNumber, $conn);
    $text = $menu->middleware($text);
    $textArray = explode("*", $text);

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM citizens WHERE phone = ?");
    $stmt->execute([$phoneNumber]);
    $user = $stmt->fetch();

    if (!$user) {
        if ($text == "") {
            $menu->mainMenuUnregistered();
        } else {
            switch ($textArray[0]) {
                case "1":
                    $menu->menuRegister($textArray);
                    break;
                case "2":
                    $menu->menuViewEvents($textArray);
                    break;
                default:
                    echo "END Invalid option. Please try again.";
                    break;
            }
        }
    } else {
        if ($text == "") {
            $menu->mainMenuRegistered();
        } else {
            switch ($textArray[0]) {
                case "1":
                    $menu->menuRegister($textArray);
                    break;
                case "2":
                    $menu->menuViewEvents($textArray);
                    break;
                case "3":
                    $menu->menuConfirmAttendance($textArray);
                    break;
                case "4":
                    $menu->menuFeedback($textArray);
                    break;
                default:
                    $menu->mainMenuRegistered();
                    break;
            }
        }
    }
} catch (Exception $e) {
    echo "END An error occurred. Please try again.";
} 