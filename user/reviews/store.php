<?php
include '../../config/config.php';
requireLogin();

$bad_words = [
    "fuck", "shit", "bitch", "asshole",
    "putangina", "gago", "punyeta", "tarantado",
    "hayop", "leche", "walang hiya", "pakshet"
];

function censorText($text, $bad_words) {
    $sub_map = [
        'a' => '[a@4]',
        'i' => '[i1!|]',
        'o' => '[o0]',
        'e' => '[e3]',
        's' => '[s5$z]',
        'u' => '[uüv]',
        't' => '[t7+]',
    ];

    foreach ($bad_words as $word) {
        $escaped = preg_quote($word, '/');
        $escaped = str_replace('\ ', '\s+', $escaped);

        $pattern_chars = '';
        foreach (str_split($escaped) as $ch) {
            $lower = strtolower($ch);
            if (isset($sub_map[$lower])) {
                $pattern_chars .= $sub_map[$lower];
            } else {
                $pattern_chars .= $ch;
            }
        }

        $pattern = '/(?<!\w)' . $pattern_chars . '(?!\w)/iu';
        $replacement = str_repeat('*', mb_strlen($word));
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $order_id = intval($_POST['order_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!$product_id || !$order_id) {
        header('Location: ' . BASE_URL . '/user/products/index.php');
        exit();
    }
    
    // Verify user has completed order for this product (MP4 Requirement)
    $order_check = $conn->prepare("SELECT o.order_id, o.order_status, oi.product_id 
                                    FROM orders o 
                                    JOIN order_items oi ON o.order_id = oi.order_id 
                                    WHERE o.order_id = ? AND o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Delivered'");
    $order_check->bind_param("iii", $order_id, $user_id, $product_id);
    $order_check->execute();
    $order_result = $order_check->get_result();
    
    if ($order_result->num_rows === 0) {
        $order_check->close();
        $_SESSION['error'] = 'You can only review products from completed orders.';
        header('Location: create.php?product_id=' . $product_id . '&order_id=' . $order_id);
        exit();
    }
    $order_check->close();
    
    // Check if review already exists
    $existing_review = $conn->prepare("SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
    $existing_review->bind_param("iii", $user_id, $product_id, $order_id);
    $existing_review->execute();
    $existing_result = $existing_review->get_result();
    
    if ($existing_result->num_rows > 0) {
        $review = $existing_result->fetch_assoc();
        $existing_review->close();
        header('Location: edit.php?id=' . $review['review_id']);
        exit();
    }
    $existing_review->close();
    
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = sanitize($_POST['review_text'] ?? '');
    
    // Server-side validation
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Please select a valid rating (1-5 stars).';
        header('Location: create.php?product_id=' . $product_id . '&order_id=' . $order_id);
        exit();
    } elseif (empty($review_text)) {
        $_SESSION['error'] = 'Please write a review.';
        header('Location: create.php?product_id=' . $product_id . '&order_id=' . $order_id);
        exit();
    } elseif (strlen($review_text) < 10) {
        $_SESSION['error'] = 'Review must be at least 10 characters long.';
        header('Location: create.php?product_id=' . $product_id . '&order_id=' . $order_id);
        exit();
    }
    
    // Apply regex filter for bad words (MP4 Requirement)
    $filtered_text = censorText($review_text, $bad_words);

    $insert_stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, order_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("iiiis", $product_id, $user_id, $order_id, $rating, $filtered_text);

    if ($insert_stmt->execute()) {
        $insert_stmt->close();
        header('Location: ' . BASE_URL . '/user/products/view.php?id=' . $product_id . '&success=1');
        exit();
    } else {
        $insert_stmt->close();
        $_SESSION['error'] = 'Failed to submit review.';
        header('Location: create.php?product_id=' . $product_id . '&order_id=' . $order_id);
        exit();
    }
} else {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}
?>

