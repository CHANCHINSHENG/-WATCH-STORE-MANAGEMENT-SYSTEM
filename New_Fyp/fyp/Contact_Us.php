<?php
session_start();
include 'db.php'; 

$errors = [];
$success_message = '';
$form_data = [];

$my_inquiries = []; 
if (isset($_SESSION['customer_id'])) 
{
    $customer_id_logged_in = (int)$_SESSION['customer_id']; 

    $sql_my_inquiries = "SELECT id, message_subject, message_content, submission_date, admin_reply_content, replied_at 
                         FROM 16_customer_inquiries 
                         WHERE customer_id = ? 
                         ORDER BY submission_date DESC";
    
    if ($stmt_my_inquiries = $conn->prepare($sql_my_inquiries)) 
    {
        $stmt_my_inquiries->bind_param("i", $customer_id_logged_in);
        $stmt_my_inquiries->execute();
        $result_my_inquiries = $stmt_my_inquiries->get_result();
        
        while ($row = $result_my_inquiries->fetch_assoc()) 
        {
            $my_inquiries[] = $row; 
        }
        $stmt_my_inquiries->close();
    } 
    else 
    {
        // error
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $message_subject = trim($_POST['message_subject'] ?? '');
    $message_content = trim($_POST['customer_message'] ?? '');

    $form_data = 
    [
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'message_subject' => $message_subject,
        'customer_message' => $message_content,
    ];

    if (empty($customer_name)) 
    {
        $errors[] = "Your Name is required.";
    }
    if (empty($customer_email)) 
    {
        $errors[] = "Your Email is required.";
    } 
    elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) 
    {
        $errors[] = "Invalid email format. Please enter a valid email address.";
    }
    if (empty($message_subject)) 
    {
        $errors[] = "Subject is required.";
    }
    if (empty($message_content)) 
    {
        $errors[] = "Message cannot be empty.";
    }

    if (empty($errors)) 
    {
        $customer_id_logged_in = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
        $sql = "INSERT INTO 16_customer_inquiries (customer_id, customer_name, customer_email, message_subject, message_content) VALUES (?,?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) 
        {
            $stmt->bind_param("issss", $customer_id_logged_in, $customer_name, $customer_email, $message_subject, $message_content);
            
            if ($stmt->execute()) 
            {
                $_SESSION['form_submission_status'] = 'success';
                $_SESSION['form_submission_message'] = 'Thank you! Your message has been sent successfully. We will get back to you soon.';

                header("Location: " . $_SERVER['PHP_SELF']);
                exit();

            } 
            else 
            {
                $errors[] = 'Oops! Something went wrong on our end. Please try again later. (Error: DB execute)';
            }
            $stmt->close();
        } 
        else 
        {
            $errors[] = 'Oops! Something went wrong on our end. Please try again later. (Error: DB prepare)';
        }
    }
    
    if (!empty($errors)) 
    {
        $_SESSION['form_submission_status'] = 'error';
        $_SESSION['form_submission_errors'] = $errors;
        $_SESSION['form_submission_data'] = $form_data; 
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$display_status = $_SESSION['form_submission_status'] ?? null;
$display_message = $_SESSION['form_submission_message'] ?? ''; 
$display_errors = $_SESSION['form_submission_errors'] ?? [];    
$form_data_repopulate = $_SESSION['form_submission_data'] ?? [];  
  
unset($_SESSION['form_submission_status']);
unset($_SESSION['form_submission_message']);
unset($_SESSION['form_submission_errors']);
unset($_SESSION['form_submission_data']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - TGO Fashion Watch</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.7;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #343a40;
        }
        .container {
            max-width: 900px;
            margin: 25px auto;
            background: #ffffff;
            padding: 25px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #0056b3;
            margin-bottom: 15px;
            font-size: 2.2em;
        }
        .intro-text {
            text-align: center;
            font-size: 1.1em;
            color: #555;
            margin-bottom: 35px;
        }
        h2 {
            color: #007bff;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
            margin-top: 30px;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        h3 {
            color: #17a2b8;
            font-size: 1.3em;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .message-box 
        {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
            border: 1px solid transparent;
            font-size: 1em;
        }

        .message-box.success 
        {
            color: #155724; 
            background-color: #d4edda; 
            border-color: #c3e6cb; 
        }

        .message-box.error 
        {
            color: #721c24; 
            background-color: #f8d7da; 
            border-color: #f5c6cb; 
        }

        .message-box.error ul
        {
            margin-top: 10px;
            margin-bottom: 0;
            padding-left: 20px;
            list-style-type: disc; 
        }

        .message-box.error p strong 
        {
            color: inherit; 
        }

        .contact-methods-grid 
        {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
        }

        @media (min-width: 768px) 
        {
            .contact-methods-grid 
            {
                grid-template-columns: 1fr 1fr;
            }
        }

        .form-group 
        {
            margin-bottom: 20px;
        }

        .form-group label 
        {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea 
        {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group textarea:focus 
        {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .form-group textarea 
        {
            resize: vertical;
        }

        .submit-button 
        {
            display: inline-block;
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1.05em;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .submit-button:hover 
        {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .form-note 
        {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 15px;
            font-style: italic;
        }

        .info-block 
        {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }

        .info-block h3 
        {
            margin-top: 0;
        }

        .info-block p 
        {
            margin-bottom: 8px;
            font-size: 1em;
        }

        .info-block a 
        {
            color: #007bff;
            text-decoration: none;
        }

        .info-block a:hover 
        {
            text-decoration: underline;
        }

        .info-block small 
        {
            font-size: 0.9em;
            color: #6c757d;
        }

        .button-container-back 
        {
            text-align: center;
            padding: 30px 0 10px 0;
        }

        .back-to-main-button 
        {
            display: inline-block;
            padding: 12px 25px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-to-main-button:hover 
        {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .back-to-main-button:active 
        {
            background-color: #495057;
            transform: translateY(0);
        }

        .emoji 
        {
            margin-right: 8px;
            vertical-align: middle;
        }

        .placeholder-text 
        {
            color: #007bff;
            font-weight: bold;
            font-style: italic;
        }

        .inquiry-history-section 
        {
        margin-top: 40px; 
        padding-top: 30px; 
        border-top: 1px solid #dee2e6; 
        }

        .inquiry-item 
        {
            background-color: #ffffff; 
            border: 1px solid #e0e0e0; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 25px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
        }

        .inquiry-header 
        {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #ced4da; 
        }

        .inquiry-subject 
        {
            font-size: 1.15em; 
            color: #0056b3; 
            font-weight: 600; 
        }

        .inquiry-date {
            font-size: 0.9em;
            color: #6c757d; 
        }

        .inquiry-content p, 
        .admin-reply-content p 
        {
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: 500; 
            color: #343a40;
        }

        .message-text 
        {
            background-color: #f8f9fa; 
            padding: 12px 15px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            white-space: pre-wrap; 
            margin-bottom: 15px; 
            color: #495057;
            line-height: 1.6;
        }

        .admin-message-text 
        {
            background-color: #e6f7ff; 
            border-color: #b3d7ff;
            color: #004085; 
        }

        .admin-reply-content.no-reply p em 
        { 
            color: #6c757d;
            font-style: normal;
            display: block;
            padding: 10px 0;
        }

        .no-history-message 
        { 
            text-align: center;
            padding: 20px;
            background-color: #e9ecef;
            border-radius: 5px;
            color: #495057;
            font-size: 1.05em;
        }

        .inquiry-history-login-prompt
        {
            text-align: center;
            margin-top: 30px;
            padding: 25px;
            background-color: #fff3cd; 
            border: 1px solid #ffeeba;
            border-radius: 8px;
            color: #856404;
        }

        .inquiry-history-login-prompt a 
        {
            color: #007bff;
            font-weight: bold;
            text-decoration: none;
        }

        .inquiry-history-login-prompt a:hover 
        {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span class="emoji">📧</span> Contact Us</h1>

        <?php if ($display_status === 'success'): ?>
            <div class="message-box success">
                <?= htmlspecialchars($display_message); ?>
            </div>
        <?php elseif (!empty($display_errors)): ?>
            <div class="message-box error">
                <p><strong>Please correct the following errors:</strong></p>
                <ul>
                    <?php foreach ($display_errors as $error_item): ?>
                        <li><?= htmlspecialchars($error_item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>


        <p class="intro-text">
            We'd love to hear from you! Whether you have a question about our watches, an order, need some style advice, or just want to say hello, please feel free to reach out using one of the methods below.
        </p>

        <div class="contact-methods-grid">
            <div class="contact-form-section">
                <h2><span class="emoji">✍️</span> Send Us a Message</h2>
                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="contact-name">Your Name:</label>
                        <input type="text" id="contact-name" name="customer_name" value="<?= htmlspecialchars($form_data_repopulate['customer_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Your Email:</label>
                        <input type="email" id="contact-email" name="customer_email" value="<?= htmlspecialchars($form_data_repopulate['customer_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-subject">Subject:</label>
                        <input type="text" id="contact-subject" name="message_subject" value="<?= htmlspecialchars($form_data_repopulate['message_subject'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Message:</label>
                        <textarea id="contact-message" name="customer_message" rows="6" required><?= htmlspecialchars($form_data_repopulate['customer_message'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="submit-button">Send Message <span class="emoji">➤</span></button>
                </form>
                <p class="form-note">
                    <em>We'll do our best to get back to you as soon as possible!</em>
                </p>
            </div>

            <?php if (isset($_SESSION['customer_id'])):?>
            <div class="inquiry-history-section">
                <h2><span class="emoji">📜</span> Your Inquiry History</h2>
                <?php if (!empty($my_inquiries)): ?>
                    <?php foreach ($my_inquiries as $inquiry): ?>
                        <div class="inquiry-item">
                            <div class="inquiry-header">
                                <span class="inquiry-subject"><strong>Subject:</strong> <?= htmlspecialchars($inquiry['message_subject']); ?></span>
                                <span class="inquiry-date"><strong>Sent:</strong> <?= htmlspecialchars(date('Y-m-d H:i A', strtotime($inquiry['submission_date']))); ?></span>
                            </div>
                            <div class="inquiry-content">
                                <p><strong>Your Message:</strong></p>
                                <div class="message-text"><?= nl2br(htmlspecialchars($inquiry['message_content'])); ?></div>
                            </div>

                            <?php if (!empty($inquiry['admin_reply_content'])): ?>
                                <div class="admin-reply-content">
                                    <p><strong>Admin's Reply (<?= htmlspecialchars(date('Y-m-d H:i A', strtotime($inquiry['replied_at']))); ?>):</strong></p>
                                    <div class="message-text admin-message-text"><?= nl2br(htmlspecialchars($inquiry['admin_reply_content'])); ?></div>
                                </div>
                            <?php else: ?>
                                <div class="admin-reply-content no-reply">
                                    <p><em><span class="emoji">⏳</span> Awaiting admin response...</em></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-history-message">You have not made any inquiries yet. Feel free to use the form above if you have any questions!</p>
                <?php endif; ?>
            </div>
        <?php else:?>
            <div class="inquiry-history-login-prompt">
                <p><span class="emoji">🔑</span> Please <a href="customer_login.php">log in</a> to view your past inquiries and our replies.</p>
            </div>
        <?php endif; ?>

            <div class="other-contact-info">
                <h2><span class="emoji">🗣️</span> Other Ways to Reach Us</h2>

                <div class="info-block">
                    <h3><span class="emoji">📧</span> Email Us</h3>
                    <p>For general inquiries: <a href="mailto:[info@tgofashionwatch.com]"><span class="placeholder-text">info@tigofashionwatch.com</span></a></p>
                    <p>For customer support: <a href="mailto:[support@tgofashionwatch.com]"><span class="placeholder-text">support@tigofashionwatch.com</span></a></p>
                </div>

                <div class="info-block">
                    <h3><span class="emoji">📱</span> Call or WhatsApp Us</h3>
                    <p>Customer Service: <strong class="placeholder-text">+60 145236587</strong></p>
                    <p>WhatsApp: <a href="https://wa.me/[YourWhatsAppNumberWithoutPlus]"><span class="placeholder-text">+60 1863745</span></a></p>
                    <p><small>(Our Operating Hours: <span class="placeholder-text">Monday - Friday, 9:00 AM - 6:00 PM </span>)</small></p>
                </div>

                <div class="info-block">
                    <h3><span class="emoji">🏢</span> Our Business Location (Optional)</h3>
                    <p><strong class="placeholder-text">[TIGO Fashion Watch]</strong></p>
                    <p><span class="placeholder-text">No. 88, Lorong Hang Jebat</span></p>
                    <p><span class="placeholder-text">75200 Melaka City, Melaka</span></p>
                    <p><span class="placeholder-text">Malaysia</span></p>
                    <p><small><em><span class="placeholder-text">Please note: Visits by appointment only / We are an online-only store at the moment.</span></em></small></p>
                </div>

                 <div class="info-block">
                    <h3><span class="emoji">🌐</span> Connect With Us (Optional)</h3>
                    <p>
                        <a href="[Your Facebook Page URL]" target="_blank" title="Follow us on Facebook"><span class="placeholder-text">Facebook</span></a> |
                        <a href="[Your Instagram Profile URL]" target="_blank" title="Follow us on Instagram"><span class="placeholder-text">Instagram</span></a>
                        </p>
                </div>
            </div>
        </div>
        
        <div class="button-container-back">
             <a href="customermainpage.php" class="back-to-main-button">
                 <span class="emoji">⬅️</span> Back to Main Page
             </a>
        </div>
    </div>
</body>
</html>