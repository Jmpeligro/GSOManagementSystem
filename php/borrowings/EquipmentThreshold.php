<?php
    require_once 'notifications.php';

    if (isset($_GET['id'])) {
        $borrowingID = $_GET['id'];
        $user_id = $_GET['user_id'];
    }    

    require_once '../db_connection.php';
    require_once '../classes/User.php';
    $conn = $db->getConnection();

    $user = new User($conn);
    $user->load($user_id);
    $email = $user->getEmail();
    $name = $user->getFullName();    

    function checkIfQuantCrit($conn) {
        $sql = "SELECT equipment_id, name, available_quantity, quantity FROM equipment";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $emailHeader = "
            <B><U>THIS IS AN AUTOMATICALLY GENERATED REPORT | DO NOT REPLY</U></B> <BR>
            <hr>
            <p>List of equipment with critical level of available quantity:</p><br>
        ";

        $emailHeaderAlt = "
            THIS IS AN AUTOMATICALLY GENERATED REPORT | DO NOT REPLY \n
            List of equipment with critical level of available quantity:\n
            
        ";

        $emailBody = "";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['equipment_id'];
                $quant = $row['quantity'];
                $avbl_quant = $row['available_quantity'];
                $name = $row['name'];
                //echo "id: " . $id . " quantity: " . $quant . " available quantity: " . $avbl_quant . " Critical Level: " . (($avbl_quant < ($quant / 2) ? "Yes" : "No")) . "<br>\n"; // debug - ignore
                //($avbl_quant < ($quant / 2) ? ($emailBody . "Equipment Id: " . $id . " Quantity: " . $quant . " Available Quantity: " . $avbl_quant . " Quantity Level = Critical" . "<br>") : null);
                if ($avbl_quant < ($quant / 2)) {   
                    $emailBody .= "Equipment Id: " . $id . " Name: " . $name . " | Quantity: " . $quant . " | Available Quantity: " . $avbl_quant . " | Quantity Level = Critical" . "<br>\n";                    
                }
            }            
        }        

        $stmt->close();
        $result->free();
    }

    checkIfQuantCrit($conn);
?>

<script>
    window.close();
</script>