<?php
    require_once 'notifications.php';

    if (isset($_GET['id'])) {
        $borrowing_ID = $_GET['id'];
        $equipment_ID = $_GET['equipment_id'];
        $user_id = $_GET['user_id'];
    }    

    require_once '../db_connection.php';
    require_once '../classes/User.php';
    require_once '../classes/Equipment.php';
    $conn = $db->getConnection();

    function getBorrowedDueDate($borrowingID, $conn) {
        $sql = "SELECT due_date FROM borrowings WHERE borrowing_id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log('Error preparing statement in getBorrowedCount: ' . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("i", $borrowingID);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['due_date'];
    }

    $user = new User($conn);
    $user->load($user_id);
    $email = $user->getEmail();
    $name = $user->getFullName();    

    $equipment = new Equipment($conn);
    $dueDate = getBorrowedDueDate($borrowing_ID, $conn);
    $equipment->load($equipment_ID);
    $equipmentName = $equipment->getName();

    $emailBody = "
            <p>Dear $name,<br>
            This email serves as a friendly reminder that the equipment listed below was due for return on $dueDate:</p>
            Equipment Details:
            <ul>
                <li>Equipment Name: $equipmentName</li>
                <li>Original Due Date: $dueDate</li>
            </ul>
            We implore you to return the aforementioned equipment at your earliest convenience.<br><br>

            Thank you for your understanding.<br><br>
            Sincerely,<br>

            General Service Office

        ";

        $emailBodyAlt = "
            THIS IS AN AUTOMATICALLY GENERATED EMAIL | DO NOT REPLY
            The equipment you borrowed is past its due date, please return it\n
            at your soonest convinience.\n
            Equipment: '$equipmentName'\n            
        
        ";

        
        $sent = sendEmail($email, $name, "College Project Testing SMTP", $emailBody, $emailBodyAlt);
        if ($sent === true) {
            pushNotif("Email Sent", "An Email has been sent to remind " . $name . " of their due date for " . $equipmentName . " on " . $dueDate . " at " . $email);
        } else {
            pushNotif("Email Failed to Send", "There was an error sending an email to " . $name . ". Please try again later");
        }
        echo "<script>history.back();</script>";
        exit();
?>
