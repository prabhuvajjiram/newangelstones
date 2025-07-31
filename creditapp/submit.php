<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// reCAPTCHA validation
$recaptchaSecret = 'YOUR_SECRET_KEY';
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
$recaptchaVerified = false;
if ($recaptchaSecret && $recaptchaResponse) {
    $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($recaptchaSecret) . '&response=' . urlencode($recaptchaResponse));
    $captcha = json_decode($verify, true);
    $recaptchaVerified = $captcha['success'] ?? false;
}
if (!$recaptchaVerified) {
    header('Location: index.php?status=error');
    exit;
}

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

$formData = [];
foreach ($_POST as $k => $v) {
    if (!in_array($k, ['g-recaptcha-response', 'signature1_image', 'signature2_image'])) {
        $formData[$k] = sanitize($v);
    }
}

$saveDir = __DIR__ . '/applications';
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0775, true);
}
$timestamp = time();
$base = $saveDir . '/creditapp_' . $timestamp;
$signaturePaths = [];

if (!empty($_POST['signature1_image'])) {
    $img = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $_POST['signature1_image']));
    $sig1 = $base . '_sig1.png';
    file_put_contents($sig1, $img);
    $signaturePaths[] = $sig1;
}
if (!empty($_POST['signature2_image'])) {
    $img = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $_POST['signature2_image']));
    $sig2 = $base . '_sig2.png';
    file_put_contents($sig2, $img);
    $signaturePaths[] = $sig2;
}

file_put_contents($base . '.txt', print_r($_POST, true));

// Load TCPDF
$tcpdfPaths = [
    __DIR__ . '/../crm/tcpdf/tcpdf.php',
    __DIR__ . '/crm/tcpdf/tcpdf.php',
    dirname(__DIR__) . '/crm/tcpdf/tcpdf.php',
    realpath(__DIR__ . '/../crm/tcpdf/tcpdf.php'),
];
$tcpdfFound = false;
foreach ($tcpdfPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $tcpdfFound = true;
        break;
    }
}
if (!$tcpdfFound) {
    header('Location: index.php?status=error');
    exit;
}

class CreditAppPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 10, 'Angel Stones Credit Application', 0, 1, 'C');
        $this->Ln(4);
    }
}

$pdf = new CreditAppPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);
foreach ($formData as $field => $val) {
    $pdf->MultiCell(0, 6, ucwords(str_replace('_', ' ', $field)) . ': ' . $val, 0, 1);
}
$pdf->Ln(5);
if (!empty($signaturePaths)) {
    foreach ($signaturePaths as $sig) {
        if (file_exists($sig)) {
            $pdf->Image($sig, '', '', 40, 20, 'PNG');
            $pdf->Ln(5);
        }
    }
}
$pdfFile = $base . '.pdf';
$pdf->Output($pdfFile, 'F');

// Email with PHPMailer (fallback to mail)
$phpmailer = __DIR__ . '/../crm/vendor/phpmailer/PHPMailer.php';
$emailSent = false;
if (file_exists($phpmailer)) {
    require_once $phpmailer;
    require_once __DIR__ . '/../crm/vendor/phpmailer/Exception.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->setFrom('noreply@theangelstones.com', 'Credit Application');
    $mail->addAddress('hr@theangelstones.com');
    $mail->Subject = 'New Credit Application';
    $mail->Body = "A new credit application has been submitted.";
    $mail->isHTML(true);
    $mail->addAttachment($pdfFile);
    foreach ($signaturePaths as $sig) {
        $mail->addAttachment($sig);
    }
    $emailSent = $mail->send();
}
if (!$emailSent) {
    // Simple mail() fallback
    $boundary = md5(time());
    $headers = "From: Credit Application <noreply@theangelstones.com>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";

    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $message .= "A new credit application has been submitted.";

    $attachments = array_merge([$pdfFile], $signaturePaths);
    foreach ($attachments as $file) {
        if (file_exists($file)) {
            $fileContent = chunk_split(base64_encode(file_get_contents($file)));
            $filename = basename($file);
            $message .= "\r\n--$boundary\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= "$fileContent";
        }
    }
    $message .= "\r\n--$boundary--";
    $emailSent = mail('hr@theangelstones.com', 'New Credit Application', $message, $headers);
}

header('Location: index.php?status=' . ($emailSent ? 'success' : 'error'));
exit;
?>
