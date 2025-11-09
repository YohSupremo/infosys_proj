<?php
// Email Configuration for Mailtrap (Term Test Requirement)
// Configured to use Mailtrap SMTP for testing

function sendOrderStatusEmail($to_email, $to_name, $order_id, $order_status, $order_items, $subtotal, $discount_amount, $total_amount) {
    // Mailtrap SMTP Configuration
    $smtp_host = 'smtp.mailtrap.io';
    $smtp_port = 2525;
    $smtp_user = 'your_mailtrap_username'; // Replace with your Mailtrap username
    $smtp_pass = 'your_mailtrap_password'; // Replace with your Mailtrap password
    
    // For local testing, you can use PHP's mail() function
    // In production, use PHPMailer or similar library
    
    $subject = "Order #$order_id Status Update - NBA Shop";
    
    // Build email body with product list, subtotal, and grand total
    $message = "Dear $to_name,\n\n";
    $message .= "Your order #$order_id status has been updated to: $order_status\n\n";
    $message .= "Order Details:\n";
    $message .= "================================\n\n";
    
    foreach ($order_items as $item) {
        $message .= "Product: " . $item['product_name'] . "\n";
        $message .= "Quantity: " . $item['quantity'] . "\n";
        $message .= "Unit Price: ₱" . number_format($item['unit_price'], 2) . "\n";
        $message .= "Subtotal: ₱" . number_format($item['subtotal'], 2) . "\n\n";
    }
    
    $message .= "================================\n";
    $message .= "Subtotal: ₱" . number_format($subtotal, 2) . "\n";
    if ($discount_amount > 0) {
        $message .= "Discount: -₱" . number_format($discount_amount, 2) . "\n";
    }
    $message .= "Grand Total: ₱" . number_format($total_amount, 2) . "\n\n";
    $message .= "Thank you for shopping with NBA Shop!\n";
    
    $headers = "From: NBA Shop <noreply@nbashop.com>\r\n";
    $headers .= "Reply-To: noreply@nbashop.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email using PHP mail() function
    // Note: For Mailtrap, you would typically use PHPMailer or SwiftMailer
    // This is a simplified version for the requirement
    return mail($to_email, $subject, $message, $headers);
}

// Alternative function using PHPMailer (if available)
// Uncomment and configure if you have PHPMailer installed
/*
function sendOrderStatusEmailPHPMailer($to_email, $to_name, $order_id, $order_status, $order_items, $subtotal, $discount_amount, $total_amount) {
    require_once 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Mailtrap SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_mailtrap_username';
        $mail->Password = 'your_mailtrap_password';
        $mail->Port = 2525;
        
        $mail->setFrom('noreply@nbashop.com', 'NBA Shop');
        $mail->addAddress($to_email, $to_name);
        
        $mail->isHTML(false);
        $mail->Subject = "Order #$order_id Status Update - NBA Shop";
        
        $message = "Dear $to_name,\n\n";
        $message .= "Your order #$order_id status has been updated to: $order_status\n\n";
        $message .= "Order Details:\n";
        $message .= "================================\n\n";
        
        foreach ($order_items as $item) {
            $message .= "Product: " . $item['product_name'] . "\n";
            $message .= "Quantity: " . $item['quantity'] . "\n";
            $message .= "Unit Price: ₱" . number_format($item['unit_price'], 2) . "\n";
            $message .= "Subtotal: ₱" . number_format($item['subtotal'], 2) . "\n\n";
        }
        
        $message .= "================================\n";
        $message .= "Subtotal: ₱" . number_format($subtotal, 2) . "\n";
        if ($discount_amount > 0) {
            $message .= "Discount: -₱" . number_format($discount_amount, 2) . "\n";
        }
        $message .= "Grand Total: ₱" . number_format($total_amount, 2) . "\n\n";
        $message .= "Thank you for shopping with NBA Shop!\n";
        
        $mail->Body = $message;
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
*/
?>

