<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $mobile = $_POST["mobile"];
    $subject = $_POST["subject"];
    $messageContent = $_POST["message"];

    // You should add more validation and sanitization here for security.

    $to = "info@theangelstones.com"; // Replace with your email address
	$headers = "From: $email\r\n";
	$headers .= "Cc: info@theangelstones.com \r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $message = "<html><body>";
	$message .= "<h1>The Angel Stones - Webiste Enquiry Form</h1>";
    $message .= "<p><strong>Name:</strong> $name</p>";
    $message .= "<p><strong>Email:</strong> $email</p>";
    $message .= "<p><strong>Mobile:</strong> $mobile</p>";
    $message .= "<p><strong>Subject:</strong> $subject</p>";
    $message .= "<p><strong>Message:</strong></p>";
    $message .= "<p>$messageContent</p>";
    $message .= "</body></html>";

    if (mail($to, $subject, $message, $headers)) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>
