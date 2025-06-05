<?php
session_start();
if (!isset($_SESSION['customer_id'])) 
{
    header("Location: customer_login.php");
    exit();
}
require_once 'db.php'; 

$CustomerID = $_SESSION['customer_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$stmt_order = $conn->prepare("SELECT Customer_Review_Status, Admin_Payment_Confirmation FROM `07_order` WHERE OrderID = ? AND CustomerID = ?");
$stmt_order->bind_param("ii", $order_id, $CustomerID);
$stmt_order->execute();
$order_info = $stmt_order->get_result()->fetch_assoc();
$stmt_order->close();

if (!$order_info) 
{
    echo "The order was not found or you do not have permission to access it.";
    exit();
}

$review_status = $order_info['Customer_Review_Status'];
$admin_payment_confirmed = ($order_info['Admin_Payment_Confirmation'] === 'Confirmed');

$existing_review = null;
$stmt_review_exist = $conn->prepare("SELECT * FROM `17_reviews` WHERE OrderID = ? AND CustomerID = ?");
$stmt_review_exist->bind_param("ii", $order_id, $CustomerID);
$stmt_review_exist->execute();
$existing_review = $stmt_review_exist->get_result()->fetch_assoc();
$stmt_review_exist->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    if (isset($_POST['action']) && $_POST['action'] === 'delete_review') 
    {
        if ($existing_review) 
        {
            // delete review
            $stmt_delete = $conn->prepare("DELETE FROM `17_reviews` WHERE ReviewID = ? AND CustomerID = ? AND OrderID = ?");
            $stmt_delete->bind_param("iii", $existing_review['ReviewID'], $CustomerID, $order_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // after delete, customer can review again
            $stmt_update_order_status_after_delete = $conn->prepare("UPDATE `07_order` SET `Customer_Review_Status` = 'Eligible' WHERE OrderID = ?");
            $stmt_update_order_status_after_delete->bind_param("i", $order_id);
            $stmt_update_order_status_after_delete->execute();
            $stmt_update_order_status_after_delete->close();
            
            header("Location: order_view.php?order_id=" . $order_id . "&msg=" . urlencode("Comment successfully deleted!"));
            exit();
        }
    } 
    else 
    {
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = $_POST['comment'];

        if ($rating < 1 || $rating > 5) 
        {
            echo "Invalid rating value. Please select 1 to 5 stars.";
            exit();
        }

        if ($existing_review) 
        {
            $stmt_update = $conn->prepare("UPDATE `17_reviews` SET Rating = ?, Comment = ?, ReviewDate = NOW() WHERE ReviewID = ?");
            $stmt_update->bind_param("isi", $rating, $comment, $existing_review['ReviewID']);
            $stmt_update->execute();
            $stmt_update->close();
            $message = "Comment updated successfully!";
        } 
        else 
        {
            if ($review_status === 'Eligible' && $admin_payment_confirmed) 
            {
                $stmt_insert = $conn->prepare("INSERT INTO `17_reviews` (OrderID, CustomerID, Rating, Comment, ReviewDate) VALUES (?, ?, ?, ?, NOW())");
                $stmt_insert->bind_param("iiis", $order_id, $CustomerID, $rating, $comment); 
                $stmt_insert->execute();
                $stmt_insert->close();
                $message = "Comment submitted successfully!";

                $stmt_update_order_status = $conn->prepare("UPDATE `07_order` SET `Customer_Review_Status` = 'Reviewed' WHERE OrderID = ?");
                $stmt_update_order_status->bind_param("i", $order_id);
                $stmt_update_order_status->execute();
                $stmt_update_order_status->close();
            } 
            else 
            {
                echo "The current order status does not allow new reviews to be submitted.";
                exit();
            }
        }
        header("Location: order_view.php?order_id=" . $order_id . "&msg=" . urlencode($message));
        exit();
    }
}

$initial_rating = $existing_review ? $existing_review['Rating'] : 0;
$initial_comment = $existing_review ? $existing_review['Comment'] : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $existing_review ? 'View/Edit Review' : 'Submit Review' ?></title>
    <style>
    .review-form-container 
    {
        background-color: #282828;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        max-width: 600px;
        margin: 50px auto;
        color: #e0e0e0;
    }

    .review-form-container h2 
    {
        color: #4CAF50;
        margin-bottom: 25px;
        text-align: center;
    }

    .review-form-container label 
    {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .review-form-container textarea 
    {
        width: calc(100% - 20px);
        padding: 10px;
        margin-bottom: 20px;
        border: 1px solid #444;
        border-radius: 5px;
        background-color: #333;
        color: #e0e0e0;
        resize: vertical;
        min-height: 100px;
    }

    .review-form-container button[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.1em; 
        transition: background-color 0.3s ease;
        display: block;
        width: 100%;
        margin-top: 20px;
    }

    .review-form-container button[type="submit"]:hover 
    {
        background-color: #43A047;
    }

    .stars-container 
    {
        font-size: 2.5em;
        color: #888;
        cursor: pointer;
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-bottom: 25px;
    }

    .stars-container .star 
    {
        transition: color 0.2s ease, transform 0.1s ease;
    }

    .stars-container .star:hover 
    {
        transform: scale(1.1);
    }

    .stars-container .star.selected,
    .stars-container .star.hover-active 
    {
        color: #FFD700;
    }

    .submitted-review-display 
    {
        padding: 20px;
        background-color: #333333;
        border: 1px solid #444444;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .submitted-review-display p 
    {
        margin-bottom: 10px;
    }

    .submitted-review-display strong 
    {
        color: #B0BEC5;
    }

    .submitted-review-display .rating-stars-display 
    {
        color: #FFD700;
        font-size: 1.8em;
    }

    .review-actions 
    {
        display: flex; 
        justify-content: space-between;
        gap: 10px; 
        margin-top: 20px; 
        flex-wrap: wrap; 
    }

    .review-actions button 
    {
        flex: 1;
        min-width: 120px;
        padding: 12px 15px; 
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        font-size: 1.1em; 
        font-weight: bold; 
        white-space: nowrap; 
        box-sizing: border-box; 
    }

    .review-actions button:hover 
    {
        transform: translateY(-2px); 
    }

    .edit-review-button 
    {
        background-color: #64B5F6; 
        color: white;
    }

    .edit-review-button:hover 
    {
        background-color: #42A5F5;
    }

    .delete-review-button 
    {
        background-color: #FF6347; 
        color: white;
    }

    .delete-review-button:hover 
    {
        background-color: #E04B30;
    }

    #editReviewForm h3 
    {
        color: #FFD700;
        margin-top: 25px;
        margin-bottom: 20px;
        text-align: center;
    }

    .back-button 
    {
        display: inline-block; 
        background-color: #4CAF50; 
        color: #ffffff;
        border: none;
        padding: 12px 25px; 
        border-radius: 6px;
        text-decoration: none; 
        margin-top: 30px;
        font-size: 1.1em;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        display: block; 
        margin-left: auto;
        margin-right: auto;
        width: fit-content; 
    }

    .back-button:hover 
    {
        background-color: #43A047;
        transform: translateY(-2px);
    }

    .back-button:active 
    {
        transform: translateY(0);
    }

    a {
        color: #64B5F6; 
        text-decoration: none;
    }

    a:hover
    {
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <div class="review-form-container">
        <?php if ($existing_review): ?>
            <h2>Your Review for Order #<?= htmlspecialchars($order_id) ?></h2>
            <div class="submitted-review-display">
                <p><strong>Rating:</strong> 
                    <span class="rating-stars-display">
                        <?php for ($i = 0; $i < $existing_review['Rating']; $i++): ?>★<?php endfor; ?>
                    </span>
                    (<?= htmlspecialchars($existing_review['Rating']) ?>/5)
                </p>
                <p><strong>Comment:</strong> <?= nl2br(htmlspecialchars($existing_review['Comment'])) ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($existing_review['ReviewDate']) ?></p>
                
                <?php if ($admin_payment_confirmed): ?>  
                    <button type="button" class="edit-review-button" onclick="showEditForm()">Edit Review</button>
                    <button type="button" class="delete-review-button" onclick="confirmDelete()">Delete Review</button>
                <?php endif; ?>
            </div>
            
            <form action="submit_review.php?order_id=<?= htmlspecialchars($order_id) ?>" method="POST" id="editReviewForm" style="display: none;">
                <h3>Edit Your Review</h3>
                <label for="ratingEdit">Rating:</label>
                <div class="stars-container" id="ratingStarsEdit">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <input type="hidden" name="rating" id="hiddenRatingInputEdit" value="<?= htmlspecialchars($initial_rating) ?>">

                <label for="commentEdit">Comment:</label>
                <textarea id="commentEdit" name="comment" rows="5" required><?= htmlspecialchars($initial_comment) ?></textarea><br>
                <button type="submit">Update Review</button>
            </form>

            <form id="deleteReviewForm" action="submit_review.php?order_id=<?= htmlspecialchars($order_id) ?>" method="POST" style="display: none;">
                <input type="hidden" name="action" value="delete_review">
            </form>

        <?php elseif ($review_status === 'Eligible' && $admin_payment_confirmed): ?>
            <h2>Submit Review for Order 
            <form action="submit_review.php?order_id=<?= htmlspecialchars($order_id) ?>" method="POST">
                <label for="rating">Rating:</label>
                <div class="stars-container" id="ratingStars">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <input type="hidden" name="rating" id="hiddenRatingInput" value="0">

                <label for="comment">Comment:</label>
                <textarea id="comment" name="comment" rows="5" required></textarea><br>
                <button type="submit">Submit Review</button>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: #a0a0a0;">This order is currently not eligible for reviews.</p>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 30px;"><a href="order_view.php?order_id=<?= htmlspecialchars($order_id) ?>" class="back-button">← Return to order details</a></p>
    </div>

    <script>
        function setupStars(containerId, hiddenInputId, initialRating) {
            const ratingStars = document.getElementById(containerId);
            const hiddenRatingInput = document.getElementById(hiddenInputId);
            const stars = ratingStars.querySelectorAll('.star');

            let currentRating = initialRating; 

            updateStars(currentRating);

            ratingStars.addEventListener('mouseover', (event) => {
                const hoveredStar = event.target.closest('.star');
                if (!hoveredStar) return;

                const hoverValue = parseInt(hoveredStar.dataset.value);
                stars.forEach(star => {
                    const starValue = parseInt(star.dataset.value);
                    if (starValue <= hoverValue) {
                        star.classList.add('hover-active');
                    } else {
                        star.classList.remove('hover-active');
                    }
                });
            });

            ratingStars.addEventListener('mouseout', () => {
                stars.forEach(star => {
                    star.classList.remove('hover-active');
                });
                updateStars(currentRating);
            });

            ratingStars.addEventListener('click', (event) => {
                const clickedStar = event.target.closest('.star');
                if (!clickedStar) return;

                currentRating = parseInt(clickedStar.dataset.value);
                hiddenRatingInput.value = currentRating;
                updateStars(currentRating);
            });

            function updateStars(rating) {
                stars.forEach(star => {
                    const starValue = parseInt(star.dataset.value);
                    if (starValue <= rating) {
                        star.classList.add('selected');
                    } else {
                        star.classList.remove('selected');
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('ratingStars')) {
                setupStars('ratingStars', 'hiddenRatingInput', 0); 
            }

            if (document.getElementById('ratingStarsEdit')) {
                const initialEditRating = parseInt(document.getElementById('hiddenRatingInputEdit').value);
                setupStars('ratingStarsEdit', 'hiddenRatingInputEdit', initialEditRating);
            }
        });

        function showEditForm() {
            const editForm = document.getElementById('editReviewForm');
            const submittedDisplay = document.querySelector('.submitted-review-display');
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
                submittedDisplay.style.display = 'none'; 
            } else {
                editForm.style.display = 'none';
                submittedDisplay.style.display = 'block';
            }
        }

        function confirmDelete() {
            if (confirm("Are you sure you want to delete this comment? Deleting this comment cannot be restored.")) {
                document.getElementById('deleteReviewForm').submit();
            }
        }
    </script>
</body>
</html>