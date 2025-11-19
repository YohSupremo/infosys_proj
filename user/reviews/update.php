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
    'a' => '[a@4áàâäãåā]',  
    'i' => '[i1!|íìîïī]',   
    'o' => '[o0óòôöõō]',   
    'e' => '[e3éèêëē]',    
    'u' => '[uüúùûū]',      
    's' => '[s5$z]',
    't' => '[t7+]',
    ];
   //gawa ng regex pattern kada-badword
        foreach ($bad_words as $word) {
            $escaped = preg_quote($word, '/');
          
    //w a l a n g \ s + h i y a
            $pattern_chars = '';
            foreach (str_split($escaped) as $ch) {
                $lower = strtolower($ch);
                if (isset($sub_map[$lower])) {
                    $pattern_chars .= $sub_map[$lower];
                } else {
                    $pattern_chars .= $ch;
                }
            }
              //finds white spaces then replace it with \s+ which is a pattern in regex for white space
            $pattern_chars = preg_replace('/\s+/', '\\s+', $pattern_chars);    
            $pattern = '/(?<!\w)' . $pattern_chars . '(?!\w)/iu'; //negative look behind, negative lookahead
            $replacement = str_repeat('*', mb_strlen($word));
            $text = preg_replace($pattern, $replacement, $text);  //kapag na-detect yung word or same sa pattern na ginawa, papalitan ng ast
        }

    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = intval($_POST['review_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if (!$review_id) {
        header('Location: ' . BASE_URL . '/user/products/index.php');
        exit();
    }
    
    // get review and verify ownership
    $review_stmt = $conn->prepare("SELECT r.*, p.product_id FROM product_reviews r JOIN products p ON r.product_id = p.product_id WHERE r.review_id = ? AND r.user_id = ?");
    $review_stmt->bind_param("ii", $review_id, $user_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    
    if ($review_result->num_rows === 0) {
        $review_stmt->close();
        header('Location: ' . BASE_URL . '/user/products/index.php');
        exit();
    }
    
    $review = $review_result->fetch_assoc();
    $review_stmt->close();
    
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = sanitize($_POST['review_text'] ?? '');
    
    // server-side validation
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Please select a valid rating (1-5 stars).';
        header('Location: edit.php?id=' . $review_id);
        exit();
    } elseif (empty($review_text)) {
        $_SESSION['error'] = 'Please write a review.';
        header('Location: edit.php?id=' . $review_id);
        exit();
    } elseif (strlen($review_text) < 10) {
        $_SESSION['error'] = 'Review must be at least 10 characters long.';
        header('Location: edit.php?id=' . $review_id);
        exit();
    }
    
    // function call for foul comment censor
    $filtered_text = censorText($review_text, $bad_words);
    
    $update_stmt = $conn->prepare("UPDATE product_reviews SET rating = ?, review_text = ? WHERE review_id = ? AND user_id = ?");
    $update_stmt->bind_param("isii", $rating, $filtered_text, $review_id, $user_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header('Location: ' . BASE_URL . '/user/products/view.php?id=' . $review['product_id'] . '&success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update review.';
        header('Location: edit.php?id=' . $review_id);
        exit();
    }
} else {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}
?>

