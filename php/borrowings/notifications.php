<script>  /// request permission for push notif
    Notification.requestPermission();
    
    var currentScript = document.currentScript;
    currentScript.parentNode.removeChild(currentScript);
</script>

<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use PHPMailer\PHPMailer\SMTP;

    require 'PHPMailer-master/src/Exception.php';
    require 'PHPMailer-master/src/PHPMailer.php';
    require 'PHPMailer-master/src/SMTP.php';

    function sendEmail($recipientMail, $recipientName, $subject, $body, $altbody) {
        $mail = new PHPMailer(true);
        $sendingEmail = "email@somethingsomething.com";
        $smtpPass = "password";

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $sendingEmail;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;  /// note: do not use port 25 :D

            
            $mail->setFrom($sendingEmail, 'General Service Office');
            $mail->addAddress($recipientMail, $recipientName);

            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altbody;

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /// function to send pushnotif if permission approved
    function pushNotif($hdrTxt, $bodyTxt) {
        echo "<script>
            Notification.requestPermission().then(perm => {
            if (perm === 'granted') {
                const notification = new Notification('$hdrTxt', {
                    body: '$bodyTxt'
                })
            }
        })
        
        var currentScript = document.currentScript;
        currentScript.parentNode.removeChild(currentScript);
        </script>";
    }
?>
