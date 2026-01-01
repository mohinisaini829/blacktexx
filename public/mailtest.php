<?php
$to      = 'mohini.saini@emails.emizentech.com';
$subject = 'Test Email';
$message = 'Hello, this is a test email from PHP.';
$headers = 'From: you@example.com' . "\r\n" .
           'Reply-To: you@example.com' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "Test email sent successfully.";
} else {
    echo "Failed to send test email.";
}
?>
