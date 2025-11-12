<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOrderStatusEmail($to_email, $to_name, $order_id, $order_status, $order_items, $subtotal, $discount_amount, $total_amount) {
	$subject = "Order #$order_id Status Update - NBA Shop";

	// Use 'PHP' currency label to avoid encoding issues in some clients
	// Also build a simple HTML body for better formatting
	$html  = "<p>Dear " . htmlspecialchars($to_name) . ",</p>";
	$html .= "<p>Your order <strong>#$order_id</strong> status has been updated to: <strong>" . htmlspecialchars($order_status) . "</strong></p>";
	$html .= "<h4 style=\"margin:0 0 6px\">Order Details</h4>";
	$html .= "<table cellpadding=\"6\" cellspacing=\"0\" border=\"1\" style=\"border-collapse:collapse;width:100%;max-width:600px\">";
	$html .= "<thead><tr><th align=\"left\">Product</th><th align=\"right\">Qty</th><th align=\"right\">Unit Price</th><th align=\"right\">Subtotal</th></tr></thead><tbody>";
	foreach ($order_items as $item) {
		$p = htmlspecialchars($item['product_name']);
		$q = intval($item['quantity']);
		$up = "PHP " . number_format($item['unit_price'], 2);
		$st = "PHP " . number_format($item['subtotal'], 2);
		$html .= "<tr><td>$p</td><td align=\"right\">$q</td><td align=\"right\">$up</td><td align=\"right\">$st</td></tr>";
	}
	$html .= "</tbody></table>";
	$html .= "<p style=\"margin-top:12px\">Subtotal: <strong>PHP " . number_format($subtotal, 2) . "</strong></p>";
	if ($discount_amount > 0) {
		$html .= "<p>Discount: <strong>- PHP " . number_format($discount_amount, 2) . "</strong></p>";
	}
	$html .= "<p>Grand Total: <strong>PHP " . number_format($total_amount, 2) . "</strong></p>";
	$html .= "<p>Thank you for shopping with NBA Shop!</p>";

	// Plain-text alternative
	$alt  = "Dear $to_name,\n\n";
	$alt .= "Your order #$order_id status has been updated to: $order_status\n\n";
	$alt .= "Order Details:\n";
	$alt .= "----------------------------------------\n";
	foreach ($order_items as $item) {
		$alt .= "Product: " . $item['product_name'] . "\n";
		$alt .= "Quantity: " . $item['quantity'] . "\n";
		$alt .= "Unit Price: PHP " . number_format($item['unit_price'], 2) . "\n";
		$alt .= "Subtotal: PHP " . number_format($item['subtotal'], 2) . "\n";
		$alt .= "----------------------------------------\n";
	}
	$alt .= "Subtotal: PHP " . number_format($subtotal, 2) . "\n";
	if ($discount_amount > 0) {
		$alt .= "Discount: - PHP " . number_format($discount_amount, 2) . "\n";
	}
	$alt .= "Grand Total: PHP " . number_format($total_amount, 2) . "\n\n";
	$alt .= "Thank you for shopping with NBA Shop!\n";

	try {
		$mail = new PHPMailer(true);
		$mail->isSMTP();
		$mail->Host = 'sandbox.smtp.mailtrap.io';
		$mail->SMTPAuth = true;
		$mail->Username = 'a5ef4344d2fe1d';
		$mail->Password = 'bf071af51636d9';
		$mail->Port = 2525;
		$mail->SMTPSecure = 'tls';
		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'base64';

		$mail->setFrom('noreply@nbashop.com', 'NBA Shop');
		$mail->addAddress($to_email, $to_name);
		$mail->Subject = $subject;
		$mail->isHTML(true);
		$mail->Body = $html;
		$mail->AltBody = $alt;

		return $mail->send();
	} catch (Exception $e) {
		error_log("Email sending failed: " . $e->getMessage());
		return false;
	}
}
?>

