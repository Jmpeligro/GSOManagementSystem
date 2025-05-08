<?php
    require_once 'notifications.php';

    if (isset($_GET['id'])) {
        $equipmentID = $_GET['id'];
        $user_id = $_GET['user_id'];
    }    

    require_once '../db_connection.php';
    require_once '../classes/User.php';
    require_once '../classes/Equipment.php';
    $conn = $db->getConnection();

    $user = new User($conn);
    $user->load($user_id);
    $email = $user->getEmail();
    $name = $user->getFullName();    

    $equipment = new Equipment($conn);
    $equipment->load($equipmentID);
    $equipmentName = $equipment->getName();

    $emailBody = "
            <B><U>THIS IS AN AUTOMATICALLY GENERATED EMAIL | DO NOT REPLY</U></B> <BR>
            <hr>
            <p>The equipment you borrowed is past its due date, please return it at your soonest convinience. </p>
            <p>Equipment: '$equipmentName'</p>
        ";

        $emailBodyAlt = "
            THIS IS AN AUTOMATICALLY GENERATED EMAIL | DO NOT REPLY
            The equipment you borrowed is past its due date, please return it\n
            at your soonest convinience.\n
            Equipment: '$equipmentName'\n            
        
        ";

        pushNotif("Email Sent", "An Email has been sent to remind " . $name . " of their due date at " . $email);
        sendEmail($email, $name, "Equipment Return Due Date", $emailBody, $emailBodyAlt);
        echo "<script>history.back();</script>";
        exit();
?>