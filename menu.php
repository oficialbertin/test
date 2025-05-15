<?php
require_once 'sms.php';
require_once 'db.php';
require_once 'util.php';

class Menu {
    protected $text;
    protected $sessionId;
    protected $phoneNumber;
    protected $conn;

    function __construct($text, $sessionId, $phoneNumber, $conn) {
        $this->text = $text;
        $this->sessionId = $sessionId;
        $this->phoneNumber = $phoneNumber;
        $this->conn = $conn;
    }

    public function mainMenuUnregistered() {
        echo "CON Welcome to Umuganda Connect\n";
        echo "1. Register Citizen\n";
        echo "2. View Upcoming Events\n";
        echo "99. Exit";
    }

    public function mainMenuRegistered() {
        echo "CON Welcome back to Umuganda Connect\n";
        foreach (Util::$mainMenuOptions as $key => $value) {
            echo "$key. $value\n";
        }
    }

    public function menuRegister($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            echo "CON Enter your full name";
        } elseif ($level == 2) {
            echo "CON Enter your National ID";
        } elseif ($level == 3) {
            $name = trim($textArray[1]);
            $nationalId = trim($textArray[2]);

            // Check if phone is already registered
            $stmt = $this->conn->prepare("SELECT * FROM citizens WHERE phone = ?");
            $stmt->execute([$this->phoneNumber]);
            if ($stmt->rowCount() > 0) {
                echo "END This phone number is already registered.";
                return;
            }

            // Check if National ID is already registered
            $stmt = $this->conn->prepare("SELECT * FROM citizens WHERE national_id = ?");
            $stmt->execute([$nationalId]);
            if ($stmt->rowCount() > 0) {
                echo "END This National ID is already registered.";
                return;
            }

            // Insert into database
            $stmt = $this->conn->prepare("INSERT INTO citizens (name, phone, national_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $this->phoneNumber, $nationalId])) {
                $message = "Dear $name, you have successfully registered for Umuganda Connect.";
                $sms = new SMS();
                $sms->sendSMS($message, $this->phoneNumber);

                echo "END Dear $name, you have successfully registered for Umuganda Connect.";
            } else {
                echo "END Registration failed. Please try again.";
            }
        } else {
            echo "END Invalid input. Please try again.";
        }
    }

    public function menuViewEvents($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            $stmt = $this->conn->prepare("SELECT id, title, location, event_date FROM umuganda_events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 3");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "CON Upcoming Events:\n";
            foreach ($events as $event) {
                echo "- " . $event['title'] . "\n";
                echo "  Location: " . $event['location'] . "\n";
                echo "  Date: " . $event['event_date'] . "\n\n";
            }
            echo "98. Go Back\n";
            echo "99. Main Menu";
        }
    }

    public function menuConfirmAttendance($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            $stmt = $this->conn->prepare("
                SELECT e.id, e.title, e.event_date 
                FROM umuganda_events e 
                WHERE e.event_date = CURDATE()
            ");
            $stmt->execute();
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                echo "END No Umuganda event scheduled for today.";
                return;
            }

            echo "CON Confirm attendance for today's event:\n";
            echo $event['title'] . "\n";
            echo "1. Confirm Attendance\n";
            echo "2. Cancel\n";
            echo "98. Go Back\n";
            echo "99. Main Menu";
        } elseif ($level == 2) {
            if ($textArray[1] == "1") {
                try {
                    // First, get the citizen ID
                    $stmt = $this->conn->prepare("SELECT id FROM citizens WHERE phone = ?");
                    $stmt->execute([$this->phoneNumber]);
                    $citizen = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$citizen) {
                        echo "END You are not registered. Please register first.";
                        return;
                    }

                    // Get today's event
                    $stmt = $this->conn->prepare("SELECT id FROM umuganda_events WHERE event_date = CURDATE()");
                    $stmt->execute();
                    $event = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$event) {
                        echo "END No event found for today.";
                        return;
                    }

                    // Check if attendance record exists
                    $stmt = $this->conn->prepare("
                        SELECT id FROM attendance 
                        WHERE citizen_id = ? AND event_id = ?
                    ");
                    $stmt->execute([$citizen['id'], $event['id']]);
                    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($attendance) {
                        // Update existing record
                        $stmt = $this->conn->prepare("
                            UPDATE attendance 
                            SET attended = 1, check_in_time = CURRENT_TIMESTAMP 
                            WHERE id = ?
                        ");
                        $stmt->execute([$attendance['id']]);
                    } else {
                        // Insert new record
                        $stmt = $this->conn->prepare("
                            INSERT INTO attendance (citizen_id, event_id, attended, check_in_time) 
                            VALUES (?, ?, 1, CURRENT_TIMESTAMP)
                        ");
                        $stmt->execute([$citizen['id'], $event['id']]);
                    }

                    $message = "Thank you for confirming your attendance for today's Umuganda event.";
                    $sms = new SMS();
                    $sms->sendSMS($message, $this->phoneNumber);

                    echo "END Thank you for confirming your attendance!";
                } catch (PDOException $e) {
                    echo "END An error occurred. Please try again.";
                }
            } else {
                echo "END Operation cancelled.";
            }
        }
    }

    public function menuFeedback($textArray) {
        $level = count($textArray);

        if ($level == 1) {
            // Get today's event
            $stmt = $this->conn->prepare("
                SELECT e.id, e.title 
                FROM umuganda_events e 
                WHERE e.event_date = CURDATE()
            ");
            $stmt->execute();
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                echo "END No event found for today.";
                return;
            }

            echo "CON Please rate your experience for today's event:\n";
            echo $event['title'] . "\n\n";
            foreach (Util::$feedbackRatings as $key => $value) {
                echo "$key. $value\n";
            }
            echo "98. Go Back\n";
            echo "99. Main Menu";
        } elseif ($level == 2) {
            $rating = $textArray[1];
            if (!in_array($rating, array_keys(Util::$feedbackRatings))) {
                echo "END Invalid rating. Please try again.";
                return;
            }

            try {
                // Get citizen ID
                $stmt = $this->conn->prepare("SELECT id FROM citizens WHERE phone = ?");
                $stmt->execute([$this->phoneNumber]);
                $citizen = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$citizen) {
                    echo "END You are not registered. Please register first.";
                    return;
                }

                // Get today's event
                $stmt = $this->conn->prepare("SELECT id FROM umuganda_events WHERE event_date = CURDATE()");
                $stmt->execute();
                $event = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$event) {
                    echo "END No event found for today.";
                    return;
                }

                // Check if feedback already exists
                $stmt = $this->conn->prepare("
                    SELECT id FROM feedback 
                    WHERE citizen_id = ? AND event_id = ?
                ");
                $stmt->execute([$citizen['id'], $event['id']]);
                $existing_feedback = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_feedback) {
                    // Update existing feedback
                    $stmt = $this->conn->prepare("
                        UPDATE feedback 
                        SET rating = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$rating, $existing_feedback['id']]);
                } else {
                    // Insert new feedback
                    $stmt = $this->conn->prepare("
                        INSERT INTO feedback (event_id, citizen_id, rating) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$event['id'], $citizen['id'], $rating]);
                }

                echo "END Thank you for your feedback!";
            } catch (PDOException $e) {
                echo "END An error occurred. Please try again.";
            }
        }
    }

    public function middleware($text) {
        return $this->goBack($this->goBackMenu($text));
    }

    public function goBack($text) {
        $explodedText = explode("*", $text);
        while (array_search('98', $explodedText) != false) {
            $firstIndex = array_search('98', $explodedText);
            array_splice($explodedText, $firstIndex - 1, 2);
        }
        return join("*", $explodedText);
    }

    public function goBackMenu($text) {
        $explodedText = explode("*", $text);
        while (array_search('99', $explodedText) != false) {
            $firstIndex = array_search('99', $explodedText);
            $explodedText = array_slice($explodedText, $firstIndex + 1);
        }
        return join("*", $explodedText);
    }
} 