<?php
session_start();
require 'db.php';
require_once 'csrf_helper.php';

// Disable error display for production/AJAX to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Generate CSRF token for this session
$csrf_token = generate_csrf_token();

// LOGIC: Reset application on any page refresh/load (GET request), UNLESS we are doing AJAX/downloads, 
// or if the user just returned from a successful payment and needs to see the success screen once.
$is_ajax_or_download = isset($_REQUEST['ajax']) || isset($_GET['get_summary']) || isset($_GET['check_payment_status']);
$is_first_success_view = isset($_SESSION['payment_success_order_id']) && !isset($_SESSION['order_complete_shown']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$is_ajax_or_download && !$is_first_success_view) {
    session_unset();
    session_destroy();
    session_start();
    
    // Generate fresh CSRF token after destroy
    $csrf_token = generate_csrf_token();
}

// Handle create order request
if (isset($_POST['create_order'])) {
    try {
        // Get data from session
        $country_id = $_SESSION['country_id'] ?? 0;
        $country_name = $_SESSION['country_name'] ?? '';
        $total_people = $_SESSION['total_people'] ?? 1;
        $order_contact_email = $_SESSION['order_contact_email'] ?? '';
        $order_contact_phone = $_SESSION['order_contact_phone'] ?? '';

        if (!$country_id || !$order_contact_email) {
            echo json_encode(['success' => false, 'message' => 'Missing required information']);
            exit;
        }

        // Calculate payment amount dynamically
        $payment_info = calculatePaymentAmount($country_name, $total_people);
        $payment_amount = $payment_info['amount'];
        $currency = $payment_info['currency'];

        // Generate a temporary order ID (NOT saved in DB yet)
        $temp_order_id = 'TMP_' . time() . '_' . rand(1000, 9999);
        $_SESSION['current_temp_order_id'] = $temp_order_id;

        $collected_info = $_SESSION['collected_info'] ?? [];
        $visa_type_id = isset($_SESSION['selected_visa']['id']) ? $_SESSION['selected_visa']['id'] : null;

        // Store all application data in session (NO DB entry yet)
        $_SESSION['temp_application_data'] = [
            'country_id' => $country_id,
            'country_name' => $country_name,
            'visa_type_id' => $visa_type_id,
            'visa_type_name' => $_SESSION['selected_visa']['name'] ?? '',
            'processing_time' => $_SESSION['selected_visa']['processing_time'] ?? 'Standard Processing',
            'total_people' => $total_people,
            'order_contact_email' => $order_contact_email,
            'order_contact_phone' => $order_contact_phone,
            'payment_amount' => $payment_amount,
            'currency' => $currency,
            'collected_info' => $collected_info
        ];

        echo json_encode([
            'success' => true,
            'temp_order_id' => $temp_order_id,
            'payment_amount' => $payment_amount,
            'currency' => $currency,
            'redirect_url' => 'payment_page.php?temp_order_id=' . $temp_order_id
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error preparing payment: ' . $e->getMessage()]);
    }
    exit;
}

// Handle payment status check
if (isset($_GET['check_payment_status'])) {
    $response = ['success' => false, 'payment_status' => 'pending'];

    // Check session for payment status
    if (isset($_SESSION['payment_success_order_id'])) {
        $response = ['success' => true, 'payment_status' => 'paid', 'order_id' => $_SESSION['payment_success_order_id']];
    } else if (isset($_SESSION['payment_failed_order_id'])) {
        $response = ['success' => true, 'payment_status' => 'failed', 'order_id' => $_SESSION['payment_failed_order_id']];
    } else if (isset($_SESSION['current_temp_order_id'])) {
        $response = ['success' => true, 'payment_status' => 'pending_payment', 'temp_order_id' => $_SESSION['current_temp_order_id']];
    }

    echo json_encode($response);
    exit;
}

// Handle completion after payment
if (isset($_POST['complete_application'])) {
    // This function is no longer needed as verify_payment.php handles everything
    echo json_encode(['success' => false, 'message' => 'This function is deprecated. Payment completion is handled in verify_payment.php']);
    exit;
}

// Handle summary data request
if (isset($_GET['get_summary'])) {
    $data = [];

    // Check for successful payment order ID in session first
    $order_id = $_SESSION['payment_success_order_id'] ?? 0;

    // If no successful payment, check current order ID
    if (!$order_id) {
        $order_id = $_SESSION['current_order_id'] ?? 0;
    }

    if ($order_id) {
        // Fetch from database
        try {
            // Get order info with payment status
            $stmt = $pdo->prepare("SELECT 
                vo.id, 
                vo.email, 
                vo.phone, 
                vo.payment_status,
                vo.total_amount,
                vo.currency,
                vo.visa_status,
                vo.created_at,
                c.country_name 
                FROM visa_orders vo 
                JOIN countries c ON vo.country_id = c.id 
                WHERE vo.id = ?");
            $stmt->execute([$order_id]);
            $order_info = $stmt->fetch();

            if ($order_info) {
                $data['order_info'] = [
                    'id' => $order_info['id'],
                    'email' => $order_info['email'],
                    'phone' => $order_info['phone'],
                    'payment_status' => $order_info['payment_status'],
                    'total_amount' => $order_info['total_amount'],
                    'currency' => $order_info['currency'],
                    'visa_status' => $order_info['visa_status'],
                    'created_at' => $order_info['created_at']
                ];
                $data['country_name'] = $order_info['country_name'];

                // Get payment details if available
                $stmt = $pdo->prepare("SELECT 
                    provider,
                    provider_payment_id,
                    amount,
                    currency,
                    status,
                    created_at
                    FROM payments 
                    WHERE order_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1");
                $stmt->execute([$order_id]);
                $payment_info = $stmt->fetch();

                if ($payment_info) {
                    $data['payment_info'] = $payment_info;
                }

                // Get all applicants for this order with their answers
                $stmt = $pdo->prepare("SELECT id, applicant_no, applicant_email, applicant_phone, visa_status 
                    FROM applicants WHERE order_id = ? ORDER BY applicant_no ASC");
                $stmt->execute([$order_id]);
                $applicants = $stmt->fetchAll();

                // Count total people
                $data['order_info']['total_people'] = count($applicants);

                foreach ($applicants as $applicant) {
                    $applicant_key = "applicant_" . $applicant['applicant_no'];

                    $data[$applicant_key] = [
                        'email' => $applicant['applicant_email'],
                        'phone' => $applicant['applicant_phone'],
                        'visa_status' => $applicant['visa_status'],
                        'answers' => []
                    ];

                    // Get all answers for this applicant (both from applicant_answers and applicant_files)
                    // First get answers from applicant_answers table
                    $stmt = $pdo->prepare("SELECT 
                        aa.question_id,
                        q.label,
                        q.field_key,
                        q.field_type,
                        aa.answer_text,
                        aa.answer_type
                        FROM applicant_answers aa
                        JOIN country_questions q ON aa.question_id = q.id
                        WHERE aa.order_id = ? AND aa.applicant_id = ?
                        ORDER BY q.sort_order ASC");
                    $stmt->execute([$order_id, $applicant['id']]);
                    $answers = $stmt->fetchAll();

                    foreach ($answers as $answer) {
                        $data[$applicant_key]['answers'][$answer['question_id']] = $answer['answer_text'];
                        if (!isset($data['question_labels'][$answer['question_id']])) {
                            $data['question_labels'][$answer['question_id']] = $answer['label'];
                        }
                    }

                    // Also get files from applicant_files table
                    $stmt = $pdo->prepare("SELECT 
                        af.question_id,
                        q.label,
                        q.field_key,
                        q.field_type,
                        af.file_path
                        FROM applicant_files af
                        JOIN country_questions q ON af.question_id = q.id
                        WHERE af.order_id = ? AND af.applicant_id = ?
                        ORDER BY q.sort_order ASC");
                    $stmt->execute([$order_id, $applicant['id']]);
                    $files = $stmt->fetchAll();

                    foreach ($files as $file) {
                        $data[$applicant_key]['answers'][$file['question_id']] = $file['file_path'];
                        if (!isset($data['question_labels'][$file['question_id']])) {
                            $data['question_labels'][$file['question_id']] = $file['label'];
                        }
                    }
                }

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'country_name' => $data['country_name'] ?? '',
                    'order_info' => $data['order_info'] ?? [],
                    'payment_info' => $data['payment_info'] ?? null,
                    'question_labels' => $data['question_labels'] ?? []
                ]);
            } else {
                // If order not found in database, fall back to session data
                fallbackToSessionData();
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching summary: ' . $e->getMessage()]);
        }
    } else {
        // If no order ID in session or database, check session data
        fallbackToSessionData();
    }
    exit;
}

function fallbackToSessionData()
{
    global $pdo;

    // If no order ID in session, check if we're in the middle of a session
    if (isset($_SESSION['collected_info']) && !empty($_SESSION['collected_info'])) {
        $data = $_SESSION['collected_info'];

        // Get question labels for display
        $question_labels = [];
        if (isset($_SESSION['question_data'])) {
            foreach ($_SESSION['question_data'] as $q_id => $q_data) {
                $question_labels[$q_id] = $q_data['label'];
            }
        }

        // Get order contact info from session
        $order_contact_phone = '';
        if (isset($_SESSION['order_contact_phone'])) {
            $order_contact_phone = $_SESSION['order_contact_phone'];
        }

        echo json_encode([
            'success' => true,
            'data' => $data,
            'country_name' => $_SESSION['country_name'] ?? '',
            'order_info' => [
                'email' => $_SESSION['order_contact_email'] ?? '',
                'phone' => $order_contact_phone,
                'id' => $_SESSION['payment_success_order_id'] ?? ($_SESSION['current_order_id'] ?? 'TMP'),
                'total_people' => $_SESSION['total_people'] ?? 1,
                'payment_status' => isset($_SESSION['payment_success_order_id']) ? 'paid' : 'pending',
                'currency' => 'INR',
                'total_amount' => $_SESSION['payment_amount'] ?? 0,
                'visa_status' => 'submitted'
            ],
            'question_labels' => $question_labels
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data available']);
    }
}

// Initialize session variables if they don't exist
// Check if country is provided via GET (from landing page) - Priority over existing session
if (isset($_GET['country']) && !empty($_GET['country'])) {
    $country_name = trim($_GET['country']);

    try {
        // Try to find this country in the database - be resilient to missing columns
        $stmt = $pdo->prepare("SELECT * FROM countries WHERE country_name = ? OR country_name LIKE ? LIMIT 1");
        $stmt->execute([$country_name, "%$country_name%"]);
        $country = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($country) {
            // Check if we are resuming the same country application (and not finished)
            $is_resuming = (
                isset($_SESSION['country_name']) &&
                strcasecmp($_SESSION['country_name'], $country['country_name']) === 0 &&
                !isset($_SESSION['payment_success_order_id'])
            );

            if (!$is_resuming) {
                // Reset/Overwrite Session for new Country Selection
                $_SESSION['messages'] = []; // Clear history
                $_SESSION['collected_info'] = [];
                $_SESSION['step'] = 'country';
                unset($_SESSION['selected_visa']);
                unset($_SESSION['order_contact_email']);
                unset($_SESSION['order_contact_phone']);

                // Country found! Set up session as if user selected it
                $_SESSION['country_id'] = $country['id'];
                $_SESSION['country_name'] = $country['country_name'];

                // Fetch Visa Types for this country
                ensureVisaTypesTableExists($pdo);
                $stmt = $pdo->prepare("SELECT * FROM visa_types WHERE country_id = ?");
                $stmt->execute([$country['id']]);
                $visa_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $_SESSION['available_visa_types'] = $visa_types;

                // Construct initial messages
                $messages = [];

                // 1. User "message" (simulated)
                $messages[] = ['role' => 'user', 'text' => "I want to apply for a visa to " . $country['country_name']];

                // 2. Bot response
                if (empty($visa_types)) {
                    $messages[] = ['role' => 'bot', 'text' => "Great choice! However, I don't have any visa types configured for **" . $country['country_name'] . "** yet."];
                    $_SESSION['step'] = 'country'; // Fallback
                } else {
                    // Format for Frontend Dropdown (handled by JS)
                    $_SESSION['current_select_options'] = [];
                    foreach ($visa_types as $vt) {
                        $_SESSION['current_select_options'][] = [
                            'value' => $vt['id'], // Use ID as value
                            'label' => $vt['name'] . " - " . $vt['currency'] . " " . $vt['price']
                        ];
                    }

                    // We use 'visa_type' as a pseudo-question ID
                    $msg_text = "json_select:visa_type:Great! I can help you with a visa for **" . $country['country_name'] . "**.\n\nPlease select the type of visa you need:";

                    $messages[] = ['role' => 'bot', 'text' => $msg_text];
                    $_SESSION['step'] = 'visa_type';
                }

                $_SESSION['messages'] = $messages;
            }
        } else {
            // Country NOT found in DB
            // Reset/Overwrite Session to start fresh
            $_SESSION['messages'] = [];
            $_SESSION['collected_info'] = [];
            $_SESSION['step'] = 'country';
            unset($_SESSION['selected_visa']);
            unset($_SESSION['order_contact_email']);
            unset($_SESSION['order_contact_phone']);
            unset($_SESSION['country_id']);
            unset($_SESSION['country_name']);

            // Set initial message acknowledging the request but stating it's unsupported
            $_SESSION['messages'][] = ['role' => 'user', 'text' => "I want to apply for a visa to " . htmlspecialchars($country_name)];
            $_SESSION['messages'][] = ['role' => 'bot', 'text' => "I noticed you are interested in **" . htmlspecialchars($country_name) . "**.\n\nCurrently, we are not processing visas for **" . htmlspecialchars($country_name) . "** online. Please select another country from the list below or contact us for more information."];
        }
    } catch (Exception $e) {
        // Handle potential DB schema issues on live server
        error_log("Country Search Error: " . $e->getMessage());
        // Fallback: stay on country selection
        $_SESSION['step'] = 'country';
    }
}

// Ensure if payment was successful, we stay on finish screen (until refreshed)
if (isset($_SESSION['payment_success_order_id'])) {
    $_SESSION['step'] = 'finish';

    // Check if we already showed the success message
    $success_msg_shown = false;
    foreach ($_SESSION['messages'] as $m) {
        if (strpos($m['text'], 'Payment Successful!') !== false) {
            $success_msg_shown = true;
            break;
        }
    }

    if (!$success_msg_shown) {
        $order_id = $_SESSION['payment_success_order_id'];
        $_SESSION['messages'][] = [
            'role' => 'bot',
            'text' => "Payment Successful! Your Order ID is **#$order_id**.\n\nHere is your application summary and invoice:",
            'img' => "generate_invoice.php?order_id=$order_id",
            'is_pdf' => true,
            'pdf_name' => "Invoice_Order_#" . $order_id . ".pdf"
        ];
    }
}

if (!isset($_SESSION['messages'])) {
    // Default initialization
    $_SESSION['messages'] = [['role' => 'bot', 'text' => 'Hello! 👋 Which country are you applying for?']];
    $_SESSION['step'] = 'country';
}

if (!isset($_SESSION['selected_visa'])) {
    $_SESSION['selected_visa'] = [];
}

if (!isset($_SESSION['collected_info'])) {
    $_SESSION['collected_info'] = [];
}

if (!isset($_SESSION['q_idx'])) {
    $_SESSION['q_idx'] = 0;
}

if (!isset($_SESSION['current_person_num'])) {
    $_SESSION['current_person_num'] = 1;
}

// Handle return from payment page (when user clicks "Back to Application")
if (isset($_GET['return_from_payment']) && $_GET['return_from_payment'] == '1') {
    // Set step back to payment to show payment button again
    $_SESSION['step'] = 'payment';

    // Clear any temporary payment data
    if (isset($_SESSION['current_temp_order_id'])) {
        unset($_SESSION['current_temp_order_id']);
    }

    // Add a message to the chat
    $_SESSION['messages'][] = [
        'role' => 'bot',
        'text' => 'Welcome back! You can proceed with your payment using the button below.'
    ];
}

// Also add this check for payment failure from session
if (isset($_SESSION['payment_failed_temp_order_id'])) {
    $failed_temp_id = $_SESSION['payment_failed_temp_order_id'];
    unset($_SESSION['payment_failed_temp_order_id']);

    $_SESSION['messages'][] = [
        'role' => 'bot',
        'text' => "**Payment failed!** Please try again by clicking the payment button below. Temporary Order ID: #$failed_temp_id"
    ];

    // Set step back to payment
    $_SESSION['step'] = 'payment';
}

// Store successful payment order ID in a separate session variable that won't be unset
if (isset($_SESSION['payment_success_order_id'])) {
    $success_order_id = $_SESSION['payment_success_order_id'];

    // Store this permanently in session for summary access
    $_SESSION['last_success_order_id'] = $success_order_id;

    // Show success message in chat
    $_SESSION['messages'][] = [
        'role' => 'bot',
        'text' => "Payment successful! ✅ Your application has been submitted. Order ID: **#$success_order_id**"
    ];

    // Set step to finish
    $_SESSION['step'] = 'finish';

    // Also set as current order ID for summary
    $_SESSION['current_order_id'] = $success_order_id;
}

if (isset($_SESSION['payment_failed_order_id'])) {
    $failed_order_id = $_SESSION['payment_failed_order_id'];
    unset($_SESSION['payment_failed_order_id']);

    $_SESSION['messages'][] = [
        'role' => 'bot',
        'text' => "Payment failed for Order ID: **#$failed_order_id**. Please try again by clicking the payment button below."
    ];

    // Set step back to payment
    $_SESSION['step'] = 'payment';
}

// Helper function to format bold text for initial page load
function formatBold($text)
{
    return preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $text);
}

// Function to parse and apply validation rules from database
function applyValidationRules($value, $validation_rules, $question_label = '', $field_key = '', $context_data = [], $country_name = '')
{
    $errors = [];

    if (empty($validation_rules) || $validation_rules === '[]' || $validation_rules === '{}') {
        return $errors;
    }

    $rules = json_decode($validation_rules, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $errors;
    }

    if (isset($rules['required']) && $rules['required'] === true) {
        if (empty(trim($value))) {
            $errors[] = "This field is required.";
        }
    }

    if (isset($rules['min_length']) && strlen(trim($value)) < $rules['min_length']) {
        $errors[] = sprintf("Minimum %d characters required.", $rules['min_length']);
    }

    if (isset($rules['max_length']) && strlen(trim($value)) > $rules['max_length']) {
        $errors[] = sprintf("Maximum %d characters allowed.", $rules['max_length']);
    }

    if (isset($rules['regex']) && !empty($value)) {
        $regex_pattern = str_replace('\\\\', '\\', $rules['regex']);
        if (!preg_match('/' . $regex_pattern . '/', $value)) {
            if ($field_key === 'passport_number') {
                $errors[] = "Passport number must contain only uppercase letters and numbers (no spaces or special characters).";
            } elseif (in_array($field_key, ['first_name', 'last_name'])) {
                $errors[] = "Name can only contain letters, spaces, apostrophes and hyphens.";
            } elseif ($field_key === 'arrival_flight') {
                $errors[] = "Flight number must contain only letters and numbers.";
            } else {
                $errors[] = "Invalid format.";
            }
        }
    }

    if (isset($rules['date_format'])) {
        $date_error = validateDateWithRules($value, $rules, $context_data, $field_key);
        if ($date_error) {
            $errors[] = $date_error;
        }
    }

    return $errors;
}

function validateDateWithRules($date, $rules, $context_data = [], $field_key = '')
{
    if (empty($date)) {
        return null;
    }

    $d = DateTime::createFromFormat('Y/m/d', $date);
    if (!$d || $d->format('Y/m/d') !== $date) {
        return "Date must be in YYYY/MM/DD format (e.g., 2024/12/31).";
    }

    $date_obj = DateTime::createFromFormat('Y/m/d', $date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if (isset($rules['min_date'])) {
        $min_date_str = $rules['min_date'];
        if ($min_date_str === 'TODAY') {
            $min_date = clone $today;
        } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $min_date_str)) {
            $min_date = DateTime::createFromFormat('Y/m/d', $min_date_str);
            $min_date->setTime(0, 0, 0);
        } else {
            return null;
        }

        if ($date_obj < $min_date) {
            if ($rules['min_date'] === 'TODAY') {
                return "Date cannot be in the past.";
            } else {
                return sprintf("Date must be on or after %s.", $min_date_str);
            }
        }
    }

    if (isset($rules['max_date'])) {
        $max_date_str = $rules['max_date'];
        if ($max_date_str === 'TODAY') {
            $max_date = clone $today;
        } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $max_date_str)) {
            $max_date = DateTime::createFromFormat('Y/m/d', $max_date_str);
            $max_date->setTime(0, 0, 0);
        } else {
            return null;
        }

        if ($date_obj > $max_date) {
            if ($rules['max_date'] === 'TODAY') {
                return "Date cannot be in the future.";
            } else {
                return sprintf("Date must be on or before %s.", $max_date_str);
            }
        }
    }

    if (isset($rules['min_validity_days'])) {
        $days_required = (int) $rules['min_validity_days'];
        $future_date = clone $today;
        $future_date->modify("+$days_required days");

        if ($date_obj < $future_date) {
            return sprintf("Passport must be valid for at least %d more days.", $days_required);
        }
    }

    if ($field_key === 'passport_expiry_date') {
        foreach ($context_data as $key => $val) {
            if (strpos(strtolower($key), 'issue') !== false && preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $val)) {
                $issue_date = DateTime::createFromFormat('Y/m/d', $val);
                if ($issue_date && $date_obj <= $issue_date) {
                    return "Expiry date must be after issue date.";
                }
            }
        }
    }

    if ($field_key === 'passport_issue_date') {
        foreach ($context_data as $key => $val) {
            if (
                (strpos(strtolower($key), 'expiry') !== false || strpos(strtolower($key), 'expiration') !== false) &&
                preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $val)
            ) {
                $expiry_date = DateTime::createFromFormat('Y/m/d', $val);
                if ($expiry_date && $date_obj >= $expiry_date) {
                    return "Issue date must be before expiry date.";
                }
            }
        }
    }

    return null;
}

function validateFileUpload($file, $validation_rules, $field_key = '')
{
    $errors = [];

    // If rules are missing or invalid, enforce strict defaults for security
    $rules = [];
    if (!empty($validation_rules)) {
        $rules = json_decode($validation_rules, true);
    }

    if (empty($rules) || json_last_error() !== JSON_ERROR_NONE) {
        $rules = [
            'file_types' => ['image/jpeg', 'image/png', 'application/pdf'],
            'max_size' => 10485760, // 10MB
            'required' => true
        ];
    }

    if (isset($rules['required']) && $rules['required'] === true && empty($file['name'])) {
        $errors[] = "This file is required.";
        return $errors;
    }

    if (empty($file['name'])) {
        return $errors;
    }

    if (isset($rules['file_types']) && is_array($rules['file_types'])) {
        $file_type = mime_content_type($file['tmp_name']);
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = false;
        foreach ($rules['file_types'] as $allowed_type) {
            if ($file_type === $allowed_type) {
                $allowed = true;
                break;
            }
            if ($allowed_type === 'image/jpeg' && in_array($file_extension, ['jpg', 'jpeg'])) {
                $allowed = true;
                break;
            }
            if ($allowed_type === 'image/png' && $file_extension === 'png') {
                $allowed = true;
                break;
            }
            if ($allowed_type === 'application/pdf' && $file_extension === 'pdf') {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $allowed_types = [];
            foreach ($rules['file_types'] as $type) {
                if ($type === 'image/jpeg')
                    $allowed_types[] = 'JPG';
                if ($type === 'image/png')
                    $allowed_types[] = 'PNG';
                if ($type === 'application/pdf')
                    $allowed_types[] = 'PDF';
            }
            $errors[] = sprintf("Invalid file type. Allowed formats: %s.", implode(', ', array_unique($allowed_types)));
        }
    }

    if (isset($rules['max_size'])) {
        $max_size = (int) $rules['max_size'];
        if ($file['size'] > $max_size) {
            $mb_size = round($max_size / 1024 / 1024, 1);
            $errors[] = sprintf("File too large. Maximum size: %dMB.", $mb_size);
        }
    }

    return $errors;
}

function getSelectOptions($question_id, $pdo)
{
    $options = [];
    $stmt = $pdo->prepare("SELECT option_value, option_label FROM question_options WHERE question_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$question_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $options;
}

function validateSelectAnswer($value, $question_id, $pdo)
{
    if (empty($value)) {
        return "Please select an option.";
    }

    $options = getSelectOptions($question_id, $pdo);
    $valid_values = [];
    foreach ($options as $option) {
        $valid_values[] = strtolower($option['option_value']);
    }

    if (!in_array(strtolower($value), $valid_values)) {
        return "Please select a valid option from the list.";
    }

    return null;
}

function isDateQuestion($validation_rules)
{
    if (empty($validation_rules)) {
        return false;
    }

    $rules = json_decode($validation_rules, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return isset($rules['date_format']);
}

function getContextData($p_num, $collected_info, $question_data, $current_q_id)
{
    $context = [];

    if (isset($collected_info["applicant_$p_num"]['answers'])) {
        foreach ($collected_info["applicant_$p_num"]['answers'] as $q_id => $answer) {
            if ($q_id != $current_q_id && isset($question_data[$q_id])) {
                $context[$question_data[$q_id]['field_key']] = $answer;
                $context[$question_data[$q_id]['label']] = $answer;
            }
        }
    }

    return $context;
}

function validatePhoneNumber($phone, $country_name)
{
    if (empty($phone)) {
        return "Phone number is required.";
    }

    $cleaned_phone = preg_replace('/[^0-9\+]/', '', $phone);
    $country_lower = strtolower($country_name);

    if (strpos($country_lower, 'india') !== false) {
        if (!preg_match('/^[6-9][0-9]{9}$/', $cleaned_phone)) {
            return "Indian phone number must be 10 digits starting with 6, 7, 8, or 9.";
        }
    } elseif (strpos($country_lower, 'usa') !== false || strpos($country_lower, 'united states') !== false) {
        if (!preg_match('/^[0-9]{10}$/', $cleaned_phone)) {
            return "US phone number must be 10 digits.";
        }
    } elseif (strpos($country_lower, 'uk') !== false || strpos($country_lower, 'united kingdom') !== false) {
        if (!preg_match('/^(07[0-9]{9}|447[0-9]{9})$/', $cleaned_phone)) {
            return "UK phone number must start with 07 or +447 and be 10-11 digits.";
        }
    } else {
        if (!preg_match('/^\+?[0-9]{8,15}$/', $cleaned_phone)) {
            return "Phone number must be 8-15 digits, may start with +.";
        }
    }

    return null;
}

// Helper to ensure visa types table exists (User requested in-file logic)
function ensureVisaTypesTableExists($pdo)
{
    // 1. Create Table
    $sql = "CREATE TABLE IF NOT EXISTS visa_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        country_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'INR',
        description TEXT,
        processing_time VARCHAR(255) DEFAULT '3-5 days',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visa_types_country (country_id)
    )";
    $pdo->exec($sql);

    // 2. Check and Insert Dummy Data for Thailand
    // First, find Thailand's ID dynamically
    $stmt = $pdo->prepare("SELECT id FROM countries WHERE country_name = 'Thailand'");
    $stmt->execute();
    $thailand = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($thailand) {
        $thailand_id = $thailand['id'];

        $stmt = $pdo->prepare("SELECT count(*) FROM visa_types WHERE country_id = ?");
        $stmt->execute([$thailand_id]);

        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO visa_types (country_id, name, price, currency, description, processing_time) VALUES
                (?, 'Tourist Visa (30 Days)', 2500.00, 'INR', 'Official Tourist Visa', '3-4 days'),
                (?, 'Visa on Arrival', 2000.00, 'INR', 'Pay at airport', 'On Arrival'),
                (?, 'Business Visa', 5000.00, 'INR', 'For business purposes', '5-7 days'),
                (?, 'Digital Arrival Card', 100.00, 'INR', 'Official Govt. Arrival Card', 'Instant')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$thailand_id, $thailand_id, $thailand_id, $thailand_id]);
        }
    }
}

// Function to calculate payment amount
function calculatePaymentAmount($country_name, $num_applicants)
{
    // Check if a specific visa type was selected
    if (isset($_SESSION['selected_visa']) && !empty($_SESSION['selected_visa'])) {
        $visa = $_SESSION['selected_visa'];
        // Ensure price is treated as float
        $price = floatval($visa['price']);
        return ['amount' => $price * $num_applicants, 'currency' => $visa['currency']];
    }

    // Fallback logic if no visa type selected or found
    $per_applicant = 100; // Default fallback
    $total_amount = $num_applicants * $per_applicant;

    return ['amount' => $total_amount, 'currency' => 'INR'];
}

if (isset($_GET['ajax'])) {
    $msg = htmlspecialchars(trim($_POST['message'] ?? ''));
    $response = "";
    $img_path = "";
    $progress = 0;

    // Ensure we send JSON header
    header('Content-Type: application/json');

    // CSRF Protection for AJAX
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        echo json_encode(['text' => "Security Error: Invalid CSRF token. Please refresh the page.", 'is_finished' => false]);
        exit;
    }

    $is_select_selection = isset($_POST['select_value']) && $_POST['select_value'] !== '';
    /* We no longer overwrite $msg with the ID here, so the fallback logic can use the original label */

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $home_dir = dirname($_SERVER['DOCUMENT_ROOT']);
        $base_gov_id = $home_dir . '/gov_id/';

        if (!isset($_SESSION['order_folder_name'])) {
            $_SESSION['order_folder_name'] = 'TMP_' . time() . '_' . uniqid();
        }

        $p_num = $_SESSION['current_person_num'] ?? 1;
        
        // Use year then month then day then temporary folder then applicant number
        $sub_path = date('Y/m/d') . '/' . $_SESSION['order_folder_name'] . '/applicant_' . $p_num;
        $full_dir = $base_gov_id . $sub_path . '/';

        if (!is_dir($full_dir))
            mkdir($full_dir, 0775, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        // Determine filename based on applicant name and document type
        $first_name = '';
        if (isset($_SESSION['collected_info']["applicant_$p_num"]['answers'])) {
            foreach ($_SESSION['collected_info']["applicant_$p_num"]['answers'] as $q_id => $ans) {
                if (isset($_SESSION['question_data'][$q_id]) && $_SESSION['question_data'][$q_id]['field_key'] === 'first_name') {
                    $first_name = trim($ans);
                    break;
                }
            }
        }
        
        $clean_name = !empty($first_name) ? preg_replace('/[^a-zA-Z0-9]/', '_', $first_name) : 'applicant';
        
        $field_key = 'file';
        if ($_SESSION['step'] === 'details' && isset($_SESSION['db_questions'][$_SESSION['q_idx']])) {
            $current_q = $_SESSION['db_questions'][$_SESSION['q_idx']];
            $field_key = $current_q['field_key'] ?? 'file';
        }
        
        // Map common field keys to requested names
        $doc_type = $field_key;
        if ($field_key === 'passport_front') $doc_type = 'passportfront';
        if ($field_key === 'passport_back') $doc_type = 'passportback';
        
        $filename = $clean_name . '_' . $doc_type . '.' . $ext;
        $target = $full_dir . $filename;

        $upload_errors = [];
        if ($_SESSION['step'] === 'details' && isset($_SESSION['db_questions'][$_SESSION['q_idx']])) {
            $current_q = $_SESSION['db_questions'][$_SESSION['q_idx']];
            if ($current_q['field_type'] === 'file') {
                $validation_rules = $current_q['validation_rules'] ?? '';
                $upload_errors = validateFileUpload($_FILES['image'], $validation_rules, $current_q['field_key'] ?? '');
            }
        }

        if (empty($upload_errors)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $relative_file_path = $sub_path . '/' . $filename;
                $img_path = '/fetch_file.php?path=' . urlencode($relative_file_path);
            }
        } else {
            $response = implode("\n", $upload_errors);
        }
    }

    if ($msg !== '' || $img_path !== '' || $is_select_selection) {
        $p_num = $_SESSION['current_person_num'] ?? 1;

        if ($img_path !== '') {
            $is_pdf = (strpos(strtolower($img_path), 'pdf') !== false);
            $_SESSION['messages'][] = [
                'role' => 'user',
                'text' => $is_pdf ? "Uploaded PDF" : "Uploaded Image",
                'img' => $img_path,
                'is_pdf' => $is_pdf
            ];
        } else if ($msg !== '') {
            if ($is_select_selection) {
                $_SESSION['messages'][] = ['role' => 'user', 'text' => "Selected: " . ucfirst($msg)];
            } else {
                $_SESSION['messages'][] = ['role' => 'user', 'text' => $msg];
            }
        }

        switch ($_SESSION['step']) {
            case 'country':
                $stmt = $pdo->prepare("SELECT id, country_name FROM countries WHERE country_name LIKE ? LIMIT 1");
                $stmt->execute(["%$msg%"]);
                $country = $stmt->fetch();
                if ($country) {
                    $_SESSION['country_id'] = $country['id'];
                    $_SESSION['country_name'] = $country['country_name'];

                    // Fetch Visa Types for this country
                    $stmt = $pdo->prepare("SELECT id, name, price, currency, description, processing_time FROM visa_types WHERE country_id = ?");
                    $stmt->execute([$country['id']]);
                    $visa_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($visa_types) > 0) {
                        $_SESSION['available_visa_types'] = $visa_types;
                        $_SESSION['step'] = 'visa_type';

                        // Prepare options for select dropdown
                        $select_options = [];
                        $msg_text = "Great! You chose **" . $country['country_name'] . "**. Please select a visa type from the options below:\n\n";

                        foreach ($visa_types as $index => $vt) {
                            $price_text = $vt['currency'] . ' ' . number_format($vt['price'], 2);
                            $msg_text .= ($index + 1) . ". **" . $vt['name'] . "** (" . $price_text . ")";
                            if (!empty($vt['description'])) {
                                $msg_text .= " - " . $vt['description'];
                            }
                            $msg_text .= "\n";

                            // Add to options for dropdown
                            $select_options[] = [
                                'option_value' => $vt['id'],
                                'option_label' => $vt['name'] . ' - ' . $price_text
                            ];
                        }

                        // Trigger dropdown UI
                        $show_select_dropdown = true;
                        $response = "json_select:visa_type:" . $msg_text;
                    } else {
                        // No visa types found, proceed to details
                        $_SESSION['step'] = 'details';
                        $_SESSION['q_idx'] = 0;
                        $_SESSION['current_person_num'] = 1;
                        unset($_SESSION['selected_visa']);

                        // Fetch questions
                        $stmt = $pdo->prepare("SELECT * FROM country_questions WHERE country_id = ? ORDER BY sort_order ASC");
                        $stmt->execute([$country['id']]);
                        $_SESSION['db_questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $_SESSION['question_data'] = [];
                        foreach ($_SESSION['db_questions'] as $q) {
                            $_SESSION['question_data'][$q['id']] = $q;
                        }

                        if (empty($_SESSION['db_questions'])) {
                            $msg_text = "Sorry, applications for " . $country['country_name'] . " are not configured yet.";
                            $_SESSION['messages'][] = ['role' => 'bot', 'text' => $msg_text];
                            $response = $msg_text;
                            $_SESSION['step'] = 'country';
                        } else {
                            $first_q = $_SESSION['db_questions'][0];
                            $msg_text = "Great! You chose **" . $country['country_name'] . "**.\n\n" . formatBold($first_q['label']);
                            $_SESSION['messages'][] = ['role' => 'bot', 'text' => $msg_text];
                            $response = $msg_text;
                        }
                    }
                } else {
                    $msg_text = "Sorry, we don't support that country yet.";
                    $_SESSION['messages'][] = ['role' => 'bot', 'text' => $msg_text];
                    $response = $msg_text;
                }
                break;

            case 'visa_type':
                $visa_type_id = $_POST['select_value'] ?? '';
                $found_visa = null;
                $visa_types = $_SESSION['available_visa_types'] ?? [];

                // ROBUSTNESS: If available_visa_types is missing from session, re-fetch it BEFORE matching
                if (empty($visa_types) && isset($_SESSION['country_id'])) {
                    $stmt = $pdo->prepare("SELECT id, name, price, currency, description, processing_time FROM visa_types WHERE country_id = ?");
                    $stmt->execute([$_SESSION['country_id']]);
                    $visa_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $_SESSION['available_visa_types'] = $visa_types;
                }

                // Method 1: ID from Dropdown (json_select) or message fallback
                foreach ($visa_types as $vt) {
                    if ((!empty($visa_type_id) && $vt['id'] == $visa_type_id) || (string)$vt['id'] === trim($msg)) {
                        $found_visa = $vt;
                        break;
                    }
                }

                // Method 2: Text input or Index selection (Fallback)
                if (!$found_visa) {
                    $selection = trim($msg);
                    
                    // Handle "Selected: {Label}" format from frontend
                    if (stripos($selection, 'Selected: ') === 0) {
                        $selection = substr($selection, 10);
                    }
                    
                    // Extract name before separator (dash or bracket) if price is included
                    if (strpos($selection, ' - ') !== false) {
                        $selection = substr($selection, 0, strpos($selection, ' - '));
                    } elseif (strpos($selection, ' (') !== false) {
                        $selection = substr($selection, 0, strpos($selection, ' ('));
                    }
                    
                    $selection = trim($selection);

                    // Check if numeric index (1-based from list)
                    if (is_numeric($selection) && intval($selection) > 0 && intval($selection) <= count($visa_types)) {
                        $found_visa = $visa_types[intval($selection) - 1];
                    } else {
                        // Check if name selection (case insensitive)
                        foreach ($visa_types as $vt) {
                            if (strcasecmp(trim($vt['name']), $selection) === 0 || stripos($vt['name'], $selection) !== false) {
                                $found_visa = $vt;
                                break;
                            }
                        }
                    }
                }

                if ($found_visa) {
                    $_SESSION['selected_visa'] = $found_visa;
                    $_SESSION['selected_visa_type_id'] = $found_visa['id'];
                    $_SESSION['current_visa_type_name'] = $found_visa['name'];

                    // Proceed to ask "How many people?"
                    $_SESSION['step'] = 'how_many';
                    $response = "You have selected **" . $found_visa['name'] . "**. How many people are traveling?";

                    // Pre-fetch questions for the next step (details)
                    // We do this here so we know what they are later
                    $stmt = $pdo->prepare("SELECT id, label, field_type, validation_rules, field_key, is_required FROM country_questions WHERE country_id = ? AND is_active = 1 ORDER BY sort_order ASC");
                    $stmt->execute([$_SESSION['country_id']]);
                    $_SESSION['db_questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $_SESSION['question_data'] = [];
                    foreach ($_SESSION['db_questions'] as $q) {
                        $_SESSION['question_data'][$q['id']] = [
                            'label' => $q['label'],
                            'field_key' => $q['field_key'],
                            'field_type' => $q['field_type'],
                            'validation_rules' => $q['validation_rules'],
                            'is_required' => $q['is_required']
                        ];

                        if ($q['field_type'] === 'select') {
                            $_SESSION['question_data'][$q['id']]['options'] = getSelectOptions($q['id'], $pdo);
                        }
                    }

                    if (empty($_SESSION['db_questions'])) {
                        $msg_text = "Sorry, applications for " . $_SESSION['country_name'] . " are not configured yet.";
                        $_SESSION['messages'][] = ['role' => 'bot', 'text' => $msg_text];
                        $response = $msg_text;
                        $_SESSION['step'] = 'country'; // Go back
                        unset($_SESSION['selected_visa']);
                    }

                } else {
                    $response = "Please select a valid visa type from the options below.";
                }
                break;

            case 'how_many':
                if (is_numeric($msg) && (int) $msg > 0 && (int) $msg <= 20) {
                    $_SESSION['total_people'] = (int) $msg;
                    $_SESSION['current_person_num'] = 1;
                    $_SESSION['q_idx'] = 0;
                    $_SESSION['step'] = 'details';

                    $first_q = $_SESSION['db_questions'][0];
                    $first_q_id = $first_q['id'];
                    $first_q_label = $first_q['label'];
                    $first_field_type = $first_q['field_type'];

                    if ($first_field_type === 'select' && isset($_SESSION['question_data'][$first_q_id]['options'])) {
                        $options = $_SESSION['question_data'][$first_q_id]['options'];
                        $response = "json_select:" . $first_q_id . ":Applicant #1: **" . trim($first_q_label) . "**?";
                    } else {
                        $response = "Applicant #1. **" . trim($first_q_label) . "**?";
                    }
                } else {
                    $response = "Please enter a valid number between 1 and 20.";
                }
                break;

            case 'details':
                $questions = $_SESSION['db_questions'];
                $current_q = $questions[$_SESSION['q_idx']];
                $current_q_id = $current_q['id'];
                $current_q_label = $current_q['label'];
                $current_field_type = $current_q['field_type'];
                $validation_rules = $current_q['validation_rules'] ?? '';
                $field_key = $current_q['field_key'] ?? '';
                $is_required = (int)($current_q['is_required'] ?? 1);

                $is_skip = (strtolower(trim($msg)) === 'skip');

                if ($is_skip && !$is_required) {
                    $_SESSION['collected_info']["applicant_$p_num"]['answers'][$current_q_id] = null;
                    $_SESSION['q_idx']++;

                    if ($_SESSION['q_idx'] < count($questions)) {
                        $next_q = $questions[$_SESSION['q_idx']];
                        $next_q_id = $next_q['id'];
                        $next_q_label = $next_q['label'];
                        $next_field_type = $next_q['field_type'];

                        if ($next_field_type === 'select') {
                            $response = "json_select:" . $next_q_id . ":Applicant #$p_num: **" . trim($next_q_label) . "**?";
                        } else {
                            $response = "Next for Applicant #$p_num: **" . trim($next_q_label) . "**?";
                        }
                    } else {
                        $_SESSION['step'] = 'applicant_email';
                        $response = "Done with documents for Applicant #$p_num. What is **their email address**?";
                    }
                } elseif ($current_field_type === 'file' && !$img_path) {
                    $response = "I need a file for: **" . trim($current_q_label) . "**. Please use the 📎 icon.";
                } else {
                    $validation_errors = [];

                    if ($img_path === '') {
                        if ($current_field_type === 'select') {
                            $select_error = validateSelectAnswer($msg, $current_q_id, $pdo);
                            if ($select_error) {
                                $validation_errors[] = $select_error;
                            }
                        }

                        if (!empty($validation_rules)) {
                            $context_data = getContextData(
                                $p_num,
                                $_SESSION['collected_info'],
                                $_SESSION['question_data'],
                                $current_q_id
                            );

                            $db_errors = applyValidationRules(
                                $msg,
                                $validation_rules,
                                $current_q_label,
                                $field_key,
                                $context_data,
                                $_SESSION['country_name'] ?? ''
                            );

                            if (!empty($db_errors)) {
                                $validation_errors = array_merge($validation_errors, $db_errors);
                            }
                        }
                    }

                    if (!empty($validation_errors)) {
                        $response = implode("\n", $validation_errors);
                    } else {
                        $_SESSION['collected_info']["applicant_$p_num"]['answers'][$current_q_id] = $img_path ?: $msg;
                        $_SESSION['q_idx']++;

                        if ($_SESSION['q_idx'] < count($questions)) {
                            $next_q = $questions[$_SESSION['q_idx']];
                            $next_q_id = $next_q['id'];
                            $next_q_label = $next_q['label'];
                            $next_field_type = $next_q['field_type'];

                            if ($next_field_type === 'select') {
                                $response = "json_select:" . $next_q_id . ":Applicant #$p_num: **" . trim($next_q_label) . "**?";
                            } else {
                                $response = "Next for Applicant #$p_num: **" . trim($next_q_label) . "**?";
                            }
                        } else {
                            $_SESSION['step'] = 'applicant_email';
                            $response = "Done with documents for Applicant #$p_num. What is **their email address**?";
                        }
                    }
                }
                break;

            case 'applicant_email':
                if (!filter_var($msg, FILTER_VALIDATE_EMAIL)) {
                    $response = "Please enter a valid email address for Applicant #$p_num.";
                } else {
                    $_SESSION['collected_info']["applicant_$p_num"]['email'] = $msg;
                    $_SESSION['step'] = 'applicant_phone';
                    $response = "What is the **phone number** for Applicant #$p_num?";
                }
                break;

            case 'applicant_phone':
                if (isset($_SESSION['country_name'])) {
                    $validation_error = validatePhoneNumber($msg, $_SESSION['country_name']);
                } else {
                    $validation_error = "Please enter a valid phone number for Applicant #$p_num.";
                }

                if ($validation_error) {
                    $response = $validation_error;
                } else {
                    $_SESSION['collected_info']["applicant_$p_num"]['phone'] = $msg;
                    if ($_SESSION['current_person_num'] < $_SESSION['total_people']) {
                        $_SESSION['current_person_num']++;
                        $_SESSION['q_idx'] = 0;
                        $_SESSION['step'] = 'details';
                        $p = $_SESSION['current_person_num'];

                        $first_q = $_SESSION['db_questions'][0];
                        $first_q_id = $first_q['id'];
                        $first_q_label = $first_q['label'];
                        $first_field_type = $first_q['field_type'];

                        if ($first_field_type === 'select') {
                            $response = "json_select:" . $first_q_id . ":Next: Applicant #$p. **" . trim($first_q_label) . "**?";
                        } else {
                            $response = "Next: Applicant #$p. **" . trim($first_q_label) . "**?";
                        }
                    } else {
                        $_SESSION['step'] = 'order_email';
                        $response = "All applicant details captured. Now, please provide the **Primary Contact Email** for this order.";
                    }
                }
                break;

            case 'order_email':
                if (!filter_var($msg, FILTER_VALIDATE_EMAIL)) {
                    $response = "Please enter a valid email address for the primary contact.";
                } else {
                    $_SESSION['order_contact_email'] = $msg;
                    $_SESSION['step'] = 'order_phone';
                    $response = "Now, what is the **Primary Contact Phone Number** for the order?";
                }
                break;

            case 'order_phone':
                if (isset($_SESSION['country_name'])) {
                    $validation_error = validatePhoneNumber($msg, $_SESSION['country_name']);
                } else {
                    $validation_error = "Please enter a valid phone number for the primary contact.";
                }

                if ($validation_error) {
                    $response = $validation_error;
                } else {
                    $_SESSION['order_contact_phone'] = $msg;

                    $payment_info = calculatePaymentAmount(
                        $_SESSION['country_name'] ?? 'India',
                        $_SESSION['total_people'] ?? 1
                    );

                    $_SESSION['payment_amount'] = $payment_info['amount'];
                    $_SESSION['currency'] = $payment_info['currency'];

                    $_SESSION['step'] = 'payment';
                    $response = "Contact information saved! Please proceed to payment. The total amount is **" . $payment_info['currency'] . " " . $payment_info['amount'] . "** for " . ($_SESSION['total_people'] ?? 1) . " applicant(s). Click the payment button below to complete your payment.";
                }
                break;

            case 'finish':
                if (filter_var($msg, FILTER_VALIDATE_EMAIL)) {
                    $order_id = $_SESSION['payment_success_order_id'] ?? ($_SESSION['current_order_id'] ?? 0);
                    if ($order_id) {
                        $sent = sendOrderConfirmationEmail($order_id, $msg);
                        if ($sent) {
                            $response = "Invoice sent successfully to **$msg**! ✅";
                        } else {
                            $response = "Sorry, I couldn't send the email right now. Please check your SMTP settings in `config.php`.";
                        }
                    } else {
                        $response = "I couldn't find a completed order to send an invoice for.";
                    }
                } else {
                    $response = "If you'd like me to send the invoice to another email address, please enter a valid email. Otherwise, you can download it using the link above.";
                }
                break;
        }

        if ($response) {
            $_SESSION['messages'][] = ['role' => 'bot', 'text' => $response];
        }
    }

    $allow_upload = false;
    $step_label = "";
    $progress = 0;
    $show_date_calendar = false;
    $show_select_dropdown = false;
    $select_options = [];
    $show_payment_button = false;
    $payment_amount = 0;
    $show_payment_button = false;
    $payment_amount = 0;
    $currency = 'INR';

    // Auto-detect json_select
    if (strpos($response, 'json_select:') === 0) {
        $show_select_dropdown = true;
    }

    switch ($_SESSION['step']) {
        case 'country':
            $progress = 0;
            $step_label = "Country Selection";
            break;

        case 'visa_type':
            $progress = 5;
            $step_label = "Visa Type Selection";

            // Ensure table exists and has data (Auto-migration requested by user)
            ensureVisaTypesTableExists($pdo);

            // Fetch Visa Types for the selected country
            $country_name = $_SESSION['country_name'] ?? '';
            $country_id = $_SESSION['country_id'] ?? 0;

            if (!$country_id && !empty($country_name)) {
                // Get Country ID if missing but name exists
                $stmt = $pdo->prepare("SELECT id FROM countries WHERE country_name = ?");
                $stmt->execute([$country_name]);
                $country = $stmt->fetch();
                if ($country) {
                    $country_id = $country['id'];
                    $_SESSION['country_id'] = $country_id;
                }
            }

            if ($country_id) {
                $stmt = $pdo->prepare("SELECT id, name, price, currency, description FROM visa_types WHERE country_id = ?");
                $stmt->execute([$country_id]);
                $visa_types = $stmt->fetchAll();

                $show_select_dropdown = true;
                $select_options = [];
                foreach ($visa_types as $vt) {
                    $select_options[] = [
                        'option_value' => $vt['id'],
                        'option_label' => $vt['name'] . ' - ' . $vt['currency'] . ' ' . $vt['price']
                    ];
                }
            }
            break;

        case 'how_many':
            $progress = 10;
            $step_label = "Applicant Count";
            break;

        case 'details':
            if (isset($_SESSION['db_questions'][$_SESSION['q_idx']])) {
                $current_q = $_SESSION['db_questions'][$_SESSION['q_idx']];
                $allow_upload = ($current_q['field_type'] === 'file');
                $show_skip_button = !($current_q['is_required'] ?? 1);
                $total_questions = count($_SESSION['db_questions']);
                $progress = round((($_SESSION['q_idx']) / $total_questions) * 70 + 10);
                $step_label = "Document " . ($_SESSION['q_idx'] + 1) . " of " . $total_questions;

                $validation_rules = $current_q['validation_rules'] ?? '';
                if (isDateQuestion($validation_rules)) {
                    $show_date_calendar = true;
                }

                if ($current_q['field_type'] === 'select') {
                    $show_select_dropdown = true;
                    if (isset($_SESSION['question_data'][$current_q['id']]['options'])) {
                        $select_options = $_SESSION['question_data'][$current_q['id']]['options'];
                    }
                }
            }
            break;

        case 'applicant_email':
            $progress = 85;
            $step_label = "Applicant #" . ($_SESSION['current_person_num'] ?? 1) . " Details";
            break;

        case 'applicant_phone':
            $progress = 90;
            $step_label = "Applicant #" . ($_SESSION['current_person_num'] ?? 1) . " Details";
            break;

        case 'order_email':
            $progress = 95;
            $step_label = "Order Contact";
            break;

        case 'order_phone':
            $progress = 97;
            $step_label = "Order Contact";
            break;

        case 'payment':
            $progress = 99;
            $step_label = "Payment";
            $show_payment_button = true;

            // Ensure payment amount is set, even if session was cleared
            if (!isset($_SESSION['payment_amount']) || $_SESSION['payment_amount'] <= 0) {
                $payment_info = calculatePaymentAmount(
                    $_SESSION['country_name'] ?? 'India',
                    $_SESSION['total_people'] ?? 1
                );
                $_SESSION['payment_amount'] = $payment_info['amount'];
                $_SESSION['currency'] = $payment_info['currency'];
            }

            $payment_amount = $_SESSION['payment_amount'];
            $currency = $_SESSION['currency'] ?? 'INR';
            break;

        case 'finish':
            $progress = 100;
            $step_label = "Order Complete";
            // Mark that we have shown the finish screen, so next refresh clears data
            $_SESSION['order_complete_shown'] = true;
            break;
    }

    $step_count = "Step ";
    switch ($_SESSION['step']) {
        case 'country':
            $step_count .= "1/10";
            break;
        case 'visa_type':
            $step_count .= "2/10";
            break;
        case 'how_many':
            $step_count .= "3/10";
            break;
        case 'details':
            $step_count .= "4/10";
            break;
        case 'applicant_email':
            $step_count .= "5/10";
            break;
        case 'applicant_phone':
            $step_count .= "6/10";
            break;
        case 'order_email':
            $step_count .= "7/10";
            break;
        case 'order_phone':
            $step_count .= "8/10";
            break;
        case 'payment':
            $step_count .= "9/10";
            break;
        case 'finish':
            $step_count .= "10/10";
            break;
        default:
            $step_count .= "1/10";
    }

    echo json_encode([
        'text' => formatBold($response),
        'is_finished' => ($_SESSION['step'] === 'finish'),
        'progress' => $progress,
        'allow_upload' => $allow_upload,
        'img_path' => $img_path,
        'step_label' => $step_label,
        'step_count' => $step_count,
        'current_person' => $_SESSION['current_person_num'] ?? 1,
        'total_people' => $_SESSION['total_people'] ?? 1,
        'show_date_calendar' => $show_date_calendar,
        'show_select_dropdown' => $show_select_dropdown,
        'select_options' => $select_options,
        'current_question_id' => isset($_SESSION['db_questions'][$_SESSION['q_idx']]['id']) ? $_SESSION['db_questions'][$_SESSION['q_idx']]['id'] : null,
        'order_id' => $_SESSION['payment_success_order_id'] ?? ($_SESSION['current_order_id'] ?? null),
        'show_payment_button' => $show_payment_button,
        'payment_amount' => $payment_amount,
        'currency' => $currency,

        'step' => $_SESSION['step'],
        'csrf_token' => $csrf_token, // Send token to frontend
        'show_skip_button' => $show_skip_button ?? false
    ]);
    exit;
}

if (isset($_POST['reset'])) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Ask Visa Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#c62a2a",
                        "background-light": "#f8f6f6",
                        "background-dark": "#1a0f0f",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.5rem", "lg": "1rem", "xl": "1.5rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .glass-effect, .glass {
            background: rgba(45, 26, 26, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(198, 42, 42, 0.3);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(198, 42, 42, 0.6);
        }
        
        .chat-container {
            /* Ensuring enough space for bottom floating input */
            height: calc(100vh - 120px) !important;
        }
    </style>
</head>

<body id="body" class="dark bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex flex-col md:flex-row font-display relative selection:bg-primary/30">
    
    <!-- Custom Travel Background -->
    <div class="absolute inset-0 z-0 bg-cover bg-fixed bg-center bg-[url('assets/Background.jpeg')] before:absolute before:inset-0 before:bg-background-dark/85"></div>
    
    <!-- Background Map Decor (Subtle overlay) -->
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none flex items-center justify-center overflow-hidden z-0">
        <span class="material-symbols-outlined text-[100vw] opacity-10">public</span>
    </div>

    <div class="app-container relative z-10 flex w-full h-screen mx-auto max-w-7xl">

        <!-- Sidebar -->
        <aside class="w-full md:w-[380px] glass-effect border-r border-primary/10 flex flex-col h-auto md:h-screen shrink-0 relative z-20 shadow-2xl">
            <!-- Header Navigation -->
            <header class="p-6 pb-4 border-b border-primary/10 flex items-center justify-between bg-background-dark/40">
                <div class="text-primary flex size-10 items-center justify-center rounded-full bg-primary/10">
                    <span class="material-symbols-outlined">menu</span>
                </div>
                <h2 class="text-slate-100 text-sm font-bold tracking-widest uppercase flex-1 text-center">ASK VISA Concierge AI</h2>
                <div class="flex w-10 items-center justify-end">
                    <div class="size-10 rounded-full bg-gradient-to-tr from-primary to-primary/60 flex items-center justify-center shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-white text-xl">person</span>
                    </div>
                </div>
            </header>
            
            <div class="flex-1 overflow-y-auto p-0 flex flex-col">
                <!-- Progress Tracker Section -->
                <div class="px-6 py-6 flex flex-col gap-3 bg-background-dark/20 border-b border-primary/5">
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-primary text-[10px] font-bold uppercase tracking-wider">Current Progress</p>
                            <p class="text-slate-100 text-base font-semibold" id="stepLabel">Country Selection</p>
                        </div>
                        <p class="text-slate-400 text-xs font-medium" id="progressPercent">0% Complete</p>
                    </div>
                    
                    <div class="h-1.5 w-full rounded-full bg-primary/10 overflow-hidden shadow-inner">
                        <div id="pBar" class="h-full bg-primary rounded-full transition-all duration-1000 relative overflow-hidden" style="width: 0%;">
                            <div class="absolute inset-0 bg-white/20 w-full animate-[shimmer_2s_infinite]"></div>
                        </div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-500 font-medium">
                        <span id="stepCount">Step 1/10</span>
                        <span>Ask Visa Portal</span>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="px-6 py-4">
                     <div class="flex items-center gap-3 glass-effect rounded-xl px-4 py-3 border border-primary/10 shadow-sm inline-flex">
                        <div class="bg-primary/20 p-2 rounded-lg">
                            <span class="material-symbols-outlined text-primary text-sm shrink-0">group</span>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold leading-none mb-1">Applicants</p>
                            <p class="text-sm font-bold text-slate-200" id="applicantCount">01 Person</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 space-y-3 mt-auto pb-8">
                     <button class="w-full flex justify-center items-center gap-2 px-4 py-3.5 bg-primary hover:bg-red-700 text-white rounded-xl text-sm font-bold transition-colors shadow-lg shadow-primary/20" onclick="toggleConfirm(true)">
                        <span class="material-symbols-outlined text-sm">add</span>
                        New Application
                    </button>
                    <button class="w-full flex justify-center items-center gap-2 px-4 py-3.5 glass-effect hover:bg-white/5 text-slate-200 rounded-xl text-sm font-bold transition-all border border-primary/20" onclick="window.location.href='edit.php'">
                        <span class="material-symbols-outlined text-sm">edit</span>
                        Edit Order
                    </button>
                    <button class="w-full flex justify-center items-center gap-2 px-4 py-3.5 glass-effect hover:bg-white/5 text-slate-400 hover:text-slate-200 rounded-xl text-sm font-bold transition-all border border-transparent hover:border-primary/10" onclick="window.location.href='privacy_policy.php'">
                        <span class="material-symbols-outlined text-sm">shield_person</span>
                        Privacy Policy
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="flex-1 flex flex-col relative h-[100dvh] md:h-screen bg-background-dark/40 backdrop-blur-sm">
            <div class="chat-container flex-1 overflow-y-auto p-4 md:p-8 relative z-10 flex flex-col gap-6" id="chat">
                <?php foreach ($_SESSION['messages'] as $m): ?>
                    <div class="message-row <?php echo $m['role']; ?>">
                        <?php if ($m['role'] === 'bot'): ?>
                            <div class="message-avatar">AV</div>
                        <?php endif; ?>
                        <div class="message-content">
                            <div class="message-text">
                                <?php
                                $displayText = $m['text'];
                                if (strpos($displayText, 'json_select:') === 0) {
                                    $parts = explode(':', $displayText, 3);
                                    $displayText = $parts[2] ?? $displayText;
                                }
                                echo formatBold($displayText);
                                ?>
                            </div>

                            <?php if (isset($m['img']) && $m['img']): ?>
                                <div class="message-attachment">
                                    <?php if (isset($m['is_pdf']) && $m['is_pdf']): 
                                        $pdf_name = $m['pdf_name'] ?? (isset($_SESSION['order_id']) ? "Invoice_Order_#" . $_SESSION['order_id'] . ".pdf" : "Document.pdf");
                                        $preview_url = $m['img'] . '&inline=1';
                                        ?>
                                        <div class="pdf-card" onclick="openLightbox('<?php echo $preview_url; ?>', true)">
                                            <i class="fas fa-file-pdf pdf-icon"></i>
                                            <div class="pdf-info">
                                                <h4><?php echo $pdf_name; ?></h4>
                                                <p>Click to view</p>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo $m['img']; ?>" class="msg-img" onclick="openLightbox(this.src)">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="message-time"><?php echo date('H:i'); ?></div>
                        </div>
                        <?php if ($m['role'] === 'user'): ?>
                            <div class="message-avatar">U</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div id="completionState" style="display: none;" class="flex justify-center my-8">
                    <div class="bg-surface/80 border border-primary/30 p-8 rounded-2xl text-center max-w-sm glass-effect">
                        <div class="size-16 bg-primary/20 text-primary rounded-full flex items-center justify-center mx-auto mb-4 border border-primary/30">
                            <span class="material-symbols-outlined text-3xl">task_alt</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Application Complete!</h3>
                        <p class="text-sm text-slate-300 mb-4">Your visa application has been successfully submitted.</p>
                        <div class="text-lg font-mono font-bold text-primary mb-4 bg-black/40 py-2 rounded-lg" id="finalOrderId">#0000</div>
                        <p class="text-xs text-slate-400">You will receive a confirmation email shortly.</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Area -->
            <div class="sticky bottom-0 w-full bg-background-dark/80 backdrop-blur-md border-t border-primary/10 p-4 md:p-6 z-20 shadow-[0_-10px_40px_rgba(0,0,0,0.3)]">
                
                <!-- Preview Tray -->
                <div id="previewTray" class="hidden mb-4 p-3 rounded-xl bg-background-dark/95 border border-primary/20 items-center justify-between shadow-2xl max-w-4xl mx-auto ring-1 ring-white/5">
                    <div class="flex items-center gap-4">
                        <div class="size-12 rounded-lg bg-black/60 overflow-hidden flex items-center justify-center shrink-0 border border-white/5">
                            <img id="previewImg" src="" class="h-full w-full object-cover hidden">
                            <span id="previewPdfIcon" class="material-symbols-outlined text-primary text-2xl hidden">picture_as_pdf</span>
                        </div>
                        <div>
                            <h4 id="previewFileName" class="text-sm font-semibold text-white truncate max-w-[200px] md:max-w-md">File</h4>
                            <p id="previewFileSize" class="text-xs text-slate-400 font-mono mt-0.5">Ready</p>
                        </div>
                    </div>
                    <button class="size-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-400 hover:text-white transition-colors" onclick="clearPreview()">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="flex items-center gap-3 max-w-4xl mx-auto relative">
                    <label id="attachBtn" class="size-12 rounded-full glass flex items-center justify-center text-slate-300 cursor-pointer hover:bg-primary/20 hover:text-white hover:border-primary/40 transition-all shrink-0 disabled:opacity-50 disabled:cursor-not-allowed border border-primary/10">
                        <span class="material-symbols-outlined text-[22px]">attach_file</span>
                        <input type="file" id="fileInput" hidden accept="image/*,application/pdf" disabled onchange="handlePreview(this)">
                    </label>
                    <div class="flex-1 relative group">
                        <input type="text" id="msgInput" class="w-full h-12 glass border-primary/10 rounded-full pl-6 pr-14 text-sm focus:ring-1 focus:ring-primary focus:border-primary placeholder-slate-500 text-slate-100 shadow-inner transition-all outline-none" placeholder="Type your response..." autocomplete="off">
                        <button id="sendBtn" class="absolute right-1 top-1/2 -translate-y-1/2 size-10 bg-primary rounded-full flex items-center justify-center text-white shadow-lg shadow-primary/30 disabled:opacity-50 hover:bg-red-600 transition-colors" onclick="sendMessage()">
                            <span class="material-symbols-outlined text-[20px] translate-x-px">send</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Typing indicator -->
            <div class="absolute bottom-28 left-8 text-xs text-primary flex items-center gap-2 glass-effect px-4 py-2 rounded-full font-medium shadow-lg" id="typingIndicator" style="display: none;">
                <span class="material-symbols-outlined animate-spin text-sm">hourglass_empty</span> Assistant is processing...
            </div>
        </main>
    </div>
    
<div id="lightbox" onclick="closeLightbox()">
        <div class="lightbox-close" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </div>
        <div id="lbContainer" onclick="event.stopPropagation()">
            <img id="lbImg">
            <iframe id="lbPdf"></iframe>
        </div>
    </div>

    <div id="confirmOverlay">
        <div class="confirm-card">
            <div class="confirm-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Reset Application?</h3>
            <p>This will clear all current progress and start a new application. This action cannot be undone.</p>
            <div class="confirm-actions">
                <button class="confirm-btn cancel" onclick="toggleConfirm(false)">Cancel</button>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="reset" class="confirm-btn danger">Reset Application</button>
                </form>
            </div>
        </div>
    </div>

    <div id="summaryPopup" class="summary-popup">
        <div class="summary-container">
            <div class="summary-header">
                <h3>Application Summary</h3>
                <div class="summary-close" onclick="closeSummaryPopup()">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="summary-content" id="summaryContent">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="summary-footer">
                <div class="summary-actions">
                    <button class="summary-btn close" onclick="closeSummaryPopup()">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button class="summary-btn download" id="downloadPdfBtn" onclick="downloadSummaryAsPDF()">
                        <i class="fas fa-download"></i> Download as PDF
                    </button>
                </div>
                <div class="summary-info">
                    <small>Generated on <span id="summaryDate">...</span></small>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chat = document.getElementById('chat');
        const msgInput = document.getElementById('msgInput');
        const fileInput = document.getElementById('fileInput');
        const attachBtn = document.getElementById('attachBtn');
        const sendBtn = document.getElementById('sendBtn');
        const pBar = document.getElementById('pBar');
        const stepLabel = document.getElementById('stepLabel');
        const stepCount = document.getElementById('stepCount');
        const applicantCount = document.getElementById('applicantCount');
        const progressPercent = document.getElementById('progressPercent');
        const themeToggle = document.getElementById('themeToggle');
        const completionState = document.getElementById('completionState');
        const finalOrderId = document.getElementById('finalOrderId');
        const typingIndicator = document.getElementById('typingIndicator');
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');

        let isProcessing = false;
        let currentOrderId = null;
        let currentSelectSelection = null;
        let currentSelectLabel = null;
        let currentQuestionId = null;
        let paymentCompleted = false;

        // Lightbox Logic
        function openLightbox(src, isPdf = false) {
            document.getElementById('lbImg').style.display = isPdf ? 'none' : 'block';
            document.getElementById('lbPdf').style.display = isPdf ? 'block' : 'none';
            if (isPdf) {
                document.getElementById('lbPdf').src = src + "#toolbar=0";
            } else {
                document.getElementById('lbImg').src = src;
            }
            document.getElementById('lightbox').style.display = 'flex';
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.getElementById('lbPdf').src = '';
        }

        function toggleConfirm(show) {
            document.getElementById('confirmOverlay').style.display = show ? 'flex' : 'none';
        }

        // File Preview
        function handlePreview(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                reader.onload = (e) => {
                    const isPdf = file.type === "application/pdf";
                    document.getElementById('previewImg').src = isPdf
                        ? "https://cdn-icons-png.flaticon.com/512/337/337946.png"
                        : e.target.result;

                    document.getElementById('previewFileName').textContent = file.name;
                    document.getElementById('previewFileSize').textContent =
                        `${(file.size / 1024).toFixed(2)} KB • ${isPdf ? 'PDF Document' : 'Image'}`;

                    document.getElementById('previewTray').style.display = 'flex';
                };
                reader.readAsDataURL(file);
            }
        }

        function clearPreview() {
            fileInput.value = "";
            document.getElementById('previewTray').style.display = 'none';
        }

        // Select field selection function
        function selectOption(questionId, value, label) {
            currentSelectSelection = value;
            currentQuestionId = questionId;

            // Highlight selected option
            document.querySelectorAll('.select-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.closest('.select-option').classList.add('selected');

            // Enable send button and auto-submit after a brief delay
            sendBtn.disabled = false;

            // Update input field
            msgInput.value = label || value;

            // Auto-submit after 500ms
            setTimeout(() => {
                if (!isProcessing) {
                    sendMessage();
                }
            }, 500);
        }

        /* Removed older generic function - Using the styled one below */

        // Create payment button
        function createPaymentButton(amount, currency) {
            const paymentContainer = document.createElement('div');
            paymentContainer.className = 'mt-3 mb-2';

            paymentContainer.innerHTML = `
            <div class="glass-effect rounded-2xl p-5 border border-primary/20 bg-background-dark/80 max-w-sm shadow-xl relative overflow-hidden group">
                <div class="absolute inset-0 bg-primary/5 group-hover:bg-primary/10 transition-colors"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 text-slate-300">
                            <span class="material-symbols-outlined text-primary text-xl">payments</span>
                            <span class="text-xs font-semibold uppercase tracking-wider">Total Due</span>
                        </div>
                        <div class="text-xl font-bold text-white tracking-tight">${currency} ${amount}</div>
                    </div>
                    
                    <button class="w-full bg-primary hover:bg-red-600 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-primary/25 transition-all flex items-center justify-center gap-2 group-hover:scale-[1.02]" onclick="initiatePayment(${amount}, '${currency}')">
                        <span class="material-symbols-outlined text-sm">lock</span>
                        Pay Securely Now
                    </button>
                    
                    <div class="mt-3 flex items-center justify-center gap-2 text-[10px] text-slate-500 font-medium">
                        <span class="material-symbols-outlined text-[12px] text-green-500/80">verified_user</span>
                        <span>256-bit Encrypted • All Cards Accepted</span>
                    </div>
                </div>
            </div>
        `;

            return paymentContainer;
        }

        // Initiate payment
        async function initiatePayment(amount, currency) {
            try {
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Create order in database first
                const createOrderResponse = await fetch('?ajax=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'create_order=1&csrf_token=' + encodeURIComponent(csrfToken)
                });

                const orderData = await createOrderResponse.json();

                if (orderData.success && orderData.redirect_url) {
                    // Redirect to payment page
                    window.location.href = orderData.redirect_url;
                } else {
                    throw new Error(orderData.message || 'Failed to create payment order');
                }
            } catch (error) {
                console.error('Payment initiation error:', error);
                alert('Error initiating payment: ' + error.message);
            }
        }

        // Calendar functionality
        let calendarPopup = null;
        let currentDateInput = null;
        let currentCalendar = {
            year: new Date().getFullYear(),
            month: new Date().getMonth(),
            day: new Date().getDate()
        };

        function createCalendarPopup() {
            const popup = document.createElement('div');
            popup.className = 'calendar-popup';
            popup.id = 'calendarPopup';

            popup.innerHTML = `
            <div class="calendar" onclick="event.stopPropagation()">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <select class="calendar-select" id="calendarMonthSelect" onchange="changeCalendarMonthFromSelect()">
                            <option value="0">January</option>
                            <option value="1">February</option>
                            <option value="2">March</option>
                            <option value="3">April</option>
                            <option value="4">May</option>
                            <option value="5">June</option>
                            <option value="6">July</option>
                            <option value="7">August</option>
                            <option value="8">September</option>
                            <option value="9">October</option>
                            <option value="10">November</option>
                            <option value="11">December</option>
                        </select>
                        <select class="calendar-select" id="calendarYearSelect" onchange="changeCalendarYearFromSelect()">
                            ${generateYearOptions()}
                        </select>
                    </div>
                </div>
                <div class="calendar-weekdays">
                    <div class="weekday">Sun</div>
                    <div class="weekday">Mon</div>
                    <div class="weekday">Tue</div>
                    <div class="weekday">Wed</div>
                    <div class="weekday">Thu</div>
                    <div class="weekday">Fri</div>
                    <div class="weekday">Sat</div>
                </div>
                <div class="calendar-days" id="calendarDays"></div>
                <div class="date-format-hint">Format: YYYY/MM/DD</div>
                <div class="calendar-actions">
                    <button class="calendar-btn today" onclick="selectToday()">Today</button>
                    <button class="calendar-btn close" onclick="closeCalendar()">Close</button>
                </div>
            </div>
        `;

            document.body.appendChild(popup);
            return popup;
        }

        function generateYearOptions() {
            const currentYear = new Date().getFullYear();
            let options = '';

            // Generate years from current year - 100 to current year + 100 (100 years before and after)
            for (let year = currentYear - 100; year <= currentYear + 100; year++) {
                options += `<option value="${year}">${year}</option>`;
            }

            return options;
        }

        function showCalendar(inputElement) {
            if (!calendarPopup) {
                calendarPopup = createCalendarPopup();
            }

            currentDateInput = inputElement;

            // Parse current date from input if available
            if (inputElement.value) {
                const parts = inputElement.value.split('/');
                if (parts.length === 3) {
                    currentCalendar.year = parseInt(parts[0]);
                    currentCalendar.month = parseInt(parts[1]) - 1;
                    currentCalendar.day = parseInt(parts[2]);
                }
            } else {
                // Set to today
                const today = new Date();
                currentCalendar.year = today.getFullYear();
                currentCalendar.month = today.getMonth();
                currentCalendar.day = today.getDate();
            }

            renderCalendar();
            calendarPopup.style.display = 'flex';
        }

        function closeCalendar() {
            if (calendarPopup) {
                calendarPopup.style.display = 'none';
            }
        }

        function renderCalendar() {
            if (!calendarPopup || !currentCalendar) return;

            const { year, month } = currentCalendar;
            const calendarDays = document.getElementById('calendarDays');
            const monthSelect = document.getElementById('calendarMonthSelect');
            const yearSelect = document.getElementById('calendarYearSelect');

            // Update select values
            if (monthSelect) monthSelect.value = month;
            if (yearSelect) yearSelect.value = year;

            const firstDay = new Date(year, month, 1);
            const startingDay = firstDay.getDay();

            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const today = new Date();
            const todayStr = `${today.getFullYear()}/${(today.getMonth() + 1).toString().padStart(2, '0')}/${today.getDate().toString().padStart(2, '0')}`;

            calendarDays.innerHTML = '';

            for (let i = 0; i < startingDay; i++) {
                const emptyDay = document.createElement('button');
                emptyDay.className = 'day empty';
                emptyDay.disabled = true;
                calendarDays.appendChild(emptyDay);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dayButton = document.createElement('button');
                dayButton.className = 'day';
                dayButton.textContent = day;

                const dateStr = `${year}/${(month + 1).toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;

                // Check if this is today
                if (dateStr === todayStr) {
                    dayButton.classList.add('today');
                }

                // Check if this is the selected day
                if (currentCalendar.day === day) {
                    dayButton.classList.add('selected');
                }

                dayButton.onclick = () => selectDate(day, month + 1, year);
                calendarDays.appendChild(dayButton);
            }
        }

        function changeCalendarMonthFromSelect() {
            const monthSelect = document.getElementById('calendarMonthSelect');
            if (monthSelect) {
                currentCalendar.month = parseInt(monthSelect.value);
                renderCalendar();
            }
        }

        function changeCalendarYearFromSelect() {
            const yearSelect = document.getElementById('calendarYearSelect');
            if (yearSelect) {
                currentCalendar.year = parseInt(yearSelect.value);
                renderCalendar();
            }
        }

        function selectDate(day, month, year) {
            const dateStr = `${year}/${month.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;

            if (currentDateInput) {
                currentDateInput.value = dateStr;

                // Update calendar state
                currentCalendar.day = day;
                currentCalendar.month = month - 1;
                currentCalendar.year = year;

                const event = new Event('input', { bubbles: true });
                currentDateInput.dispatchEvent(event);

                currentDateInput.focus();
            }

            closeCalendar();
        }

        function selectToday() {
            const today = new Date();
            selectDate(today.getDate(), today.getMonth() + 1, today.getFullYear());
        }

        document.addEventListener('click', (e) => {
            if (calendarPopup && !calendarPopup.contains(e.target) && e.target !== currentDateInput && !e.target.classList.contains('calendar-icon')) {
                closeCalendar();
            }
        });

        function selectOption(value, labelElement) {
            // Update UI
            document.querySelectorAll('.select-option').forEach(el => {
                el.classList.remove('selected');
                const radio = el.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            });

            labelElement.classList.add('selected');
            const radio = labelElement.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            // Set state variables
            currentSelectSelection = value;
            currentSelectLabel = labelElement.querySelector('.option-text').textContent;

            // Enable send button
            msgInput.value = currentSelectLabel;
            sendBtn.disabled = false;
        }

        // Typing indicator
        function showTypingIndicator() {
            typingIndicator.style.display = 'flex';
            chat.appendChild(typingIndicator);
            chat.scrollTop = chat.scrollHeight;
        }

        function hideTypingIndicator() {
            typingIndicator.style.display = 'none';
        }

        // Update progress display
        function updateProgressDisplay(data) {
            if (pBar && data.progress !== undefined) {
                pBar.style.width = data.progress + '%';
            }

            if (progressPercent && data.progress !== undefined) {
                progressPercent.textContent = data.progress + '%';
            }

            if (stepLabel && data.step_label) {
                stepLabel.textContent = data.step_label;
            }

            if (stepCount && data.step_count) {
                stepCount.textContent = data.step_count;
            }

            if (applicantCount && data.current_person && data.total_people) {
                applicantCount.textContent = `Applicant ${data.current_person}/${data.total_people}`;
            }
        }

        // Message Sending function
        async function sendMessage() {
            const file = fileInput.files[0];
            let text = msgInput.value.trim();

            // Check if we have a select selection
            if (currentSelectSelection && text === '') {
                text = currentSelectSelection;
            }

            // Check if we're on a date input
            const dateInput = document.getElementById('dateInput');
            if (dateInput && dateInput.value.trim() !== '') {
                text = dateInput.value.trim();
            }

            if (isProcessing || (!text && !file)) return;

            // Show typing indicator
            showTypingIndicator();

            isProcessing = true;
            msgInput.disabled = true;
            sendBtn.disabled = true;

            const formData = new FormData();
            formData.append('message', text);

            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            formData.append('csrf_token', csrfToken);

            if (file) formData.append('image', file);

            // Add select selection if available
            if (currentSelectSelection && currentQuestionId) {
                formData.append('select_value', currentSelectSelection);
            }

            // Add user message to UI immediately with file preview
            if (text || file) {
                const userRow = document.createElement('div');
                userRow.className = 'flex flex-row-reverse gap-3 max-w-[85%] ml-auto slide-in';

                let attachmentHtml = '';
                let messageText = text || '';

                // Format select message for display
                if (currentSelectSelection) {
                    if (currentSelectLabel) {
                        messageText = `Selected: ${currentSelectLabel}`;
                    } else if (text === currentSelectSelection) {
                        messageText = `Selected: ${text}`;
                    }
                }

                if (file) {
                    const isPdf = file.type === "application/pdf";
                    const fileName = file.name;
                    const fileSize = (file.size / 1024).toFixed(2) + ' KB';

                    // Create object URL for preview
                    const objectUrl = URL.createObjectURL(file);

                    if (isPdf) {
                        attachmentHtml = `
                        <div class="mt-3 text-left">
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-black/40 border border-white/5 cursor-pointer hover:bg-black/60 transition-colors" onclick="openLightbox('${objectUrl}', true)">
                                <span class="material-symbols-outlined text-primary text-2xl">picture_as_pdf</span>
                                <div>
                                    <h4 class="text-xs font-bold text-white w-48 truncate">${fileName}</h4>
                                    <p class="text-[10px] text-slate-400">${fileSize} • Click to view</p>
                                </div>
                            </div>
                        </div>
                    `;
                    } else {
                        attachmentHtml = `
                        <div class="mt-3 text-left">
                            <img src="${objectUrl}" class="rounded-lg max-w-[200px] h-auto border border-white/20 cursor-pointer shadow-sm hover:opacity-90 transition-opacity" onclick="openLightbox(this.src)">
                        </div>
                    `;
                    }

                    // If no text was entered, show "Uploaded file" as message
                    if (!text) {
                        messageText = isPdf ? "Uploaded PDF document" : "Uploaded image";
                    }
                }

                userRow.innerHTML = `
                <div class="size-8 rounded-full bg-slate-800 flex items-center justify-center shrink-0 mt-1 border border-white/10 shadow-md">
                    <span class="material-symbols-outlined text-white text-xs">person</span>
                </div>
                <div class="space-y-2 text-right">
                    <div class="bg-primary text-white p-4 rounded-2xl rounded-tr-none shadow-md shadow-primary/20 inline-block text-left">
                        <p class="text-sm">${escapeHtml(messageText)}</p>
                        ${attachmentHtml}
                    </div>
                    <span class="text-[10px] text-slate-400 px-1 block">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            `;
                chat.appendChild(userRow);
            }

            msgInput.value = '';
            clearPreview();
            currentSelectSelection = null;
            currentSelectLabel = null;
            currentQuestionId = null;

            // Deactivate all current selection options and skip buttons
            document.querySelectorAll('.select-option').forEach(option => {
                option.classList.add('disabled');
            });

            document.querySelectorAll('.skip-btn').forEach(btn => {
                btn.classList.add('disabled');
            });

            // Remove any date input
            const datePicker = document.querySelector('.date-picker-container');
            if (datePicker) {
                datePicker.remove();
            }

            // Remove any payment button
            const paymentContainer = document.querySelector('.payment-container');
            if (paymentContainer) {
                paymentContainer.remove();
            }

            chat.scrollTop = chat.scrollHeight;

            try {
                const response = await fetch('?ajax=1', { method: 'POST', body: formData });
                const data = await response.json();

                // Update CSRF token if provided
                if (data.csrf_token) {
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrf_token);
                }

                // Hide typing indicator
                hideTypingIndicator();

                // Update progress display
                updateProgressDisplay(data);

                // Store order ID if available
                if (data.order_id) {
                    currentOrderId = data.order_id;
                }

                // Check if we need to show payment button
                // ALSO show if step is 'payment' (when returning from payment page)
                if ((data.show_payment_button && data.payment_amount > 0) || data.step === 'payment') {
                    // Add payment button to the bot response
                    const botRow = document.createElement('div');
                    botRow.className = 'flex gap-3 max-w-[85%] slide-in';

                    const formattedText = data.text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');

                    botRow.innerHTML = `
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\ red.png'); background-color: #1a0f0f;">
                    </div>
                    <div class="space-y-2 w-full">
                        <div class="bg-white dark:bg-primary/5 p-4 rounded-2xl rounded-tl-none border border-slate-200 dark:border-primary/10 shadow-sm glass-effect text-slate-200">
                            <p class="text-sm leading-relaxed">${formattedText}</p>
                            <div class="bot-message-content"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 px-1 block">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                `;

                    chat.appendChild(botRow);

                    // Add payment button - use stored amount if returning
                    const paymentAmount = data.payment_amount || (data.step === 'payment' ? <?php echo $_SESSION['payment_amount'] ?? 0; ?> : 0);
                    const currency = data.currency || 'INR';

                    if (paymentAmount > 0) {
                        const paymentButton = createPaymentButton(paymentAmount, currency);
                        botRow.querySelector('.bot-message-content').appendChild(paymentButton);

                        // Update input placeholder
                        msgInput.placeholder = "Click the payment button above to proceed";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;
                        chat.scrollTop = chat.scrollHeight;

                        // Return early since we handled this specially
                        isProcessing = false;
                        msgInput.disabled = true;
                        sendBtn.disabled = true;
                        return;
                    }
                }
                // Check if we need to show select dropdown
                else if (data.text && data.text.startsWith('json_select:') && data.show_select_dropdown) {
                    // Extract question ID and message
                    const parts = data.text.split(':');
                    const questionId = parts[1];
                    const actualMessage = parts.slice(2).join(':');

                    // Add bot response to UI
                    const botRow = document.createElement('div');
                    botRow.className = 'flex gap-3 max-w-[85%] slide-in';

                    const formattedText = formatBold(actualMessage);

                    botRow.innerHTML = `
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\ red.png'); background-color: #1a0f0f;">
                    </div>
                    <div class="space-y-2 w-full">
                        <div class="bg-white dark:bg-primary/5 p-4 rounded-2xl rounded-tl-none border border-slate-200 dark:border-primary/10 shadow-sm glass-effect text-slate-200">
                            <p class="text-sm leading-relaxed">${formattedText}</p>
                            <div class="bot-message-content"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 px-1 block">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                `;

                    chat.appendChild(botRow);

                    // Add select dropdown with options
                    if (data.select_options && data.select_options.length > 0) {
                        const selectDropdown = createSelectDropdown(questionId, data.select_options);
                        // Append to chat container directly to separate from bubble
                        selectDropdown.classList.add('bot-options-container');
                        selectDropdown.style.marginLeft = "45px"; // Align with text (avatar width approx)
                        selectDropdown.style.marginBottom = "20px";
                        chat.appendChild(selectDropdown);

                        // Update input placeholder
                        msgInput.placeholder = "Select an option above";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;

                        chat.scrollTop = chat.scrollHeight;
                    }
                } else {
                    // Regular bot response
                    const botRow = document.createElement('div');
                    botRow.className = 'flex gap-3 max-w-[85%] slide-in';

                    const formattedText = formatBold(data.text);

                    botRow.innerHTML = `
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\ red.png'); background-color: #1a0f0f;">
                    </div>
                    <div class="space-y-2 w-full">
                        <div class="bg-white dark:bg-primary/5 p-4 rounded-2xl rounded-tl-none border border-slate-200 dark:border-primary/10 shadow-sm glass-effect text-slate-200">
                            <p class="text-sm leading-relaxed">${formattedText}</p>
                            <div class="bot-message-content"></div>
                        </div>
                        <span class="text-[10px] text-slate-400 px-1 block">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                `;

                    // Add calendar for date questions
                    if (data.show_date_calendar) {
                        // Remove old date inputs' IDs so that getElementById finds the new one
                        document.querySelectorAll('#dateInput').forEach(el => el.removeAttribute('id'));
                        document.querySelectorAll('.date-input-wrapper').forEach(el => el.style.opacity = '0.7');

                        const datePickerContainer = document.createElement('div');
                        datePickerContainer.className = 'date-picker-container mt-3';

                        datePickerContainer.innerHTML = `
                        <div class="relative glass border-primary/20 rounded-xl max-w-sm flex items-center overflow-hidden">
                            <input type="text" 
                                   class="flex-1 bg-transparent border-none text-white text-sm py-3 px-4 focus:ring-0 outline-none placeholder-slate-500" 
                                   placeholder="YYYY/MM/DD"
                                   id="dateInput"
                                   autocomplete="off">
                            <button class="bg-primary hover:bg-red-600 text-white px-4 py-3 flex items-center justify-center transition-colors shadow-lg" onclick="showCalendar(document.getElementById('dateInput'))">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 pl-1">Click the calendar icon to pick a date</div>
                    `;

                        botRow.querySelector('.bot-message-content').appendChild(datePickerContainer);

                        // Focus on date input
                        setTimeout(() => {
                            const dateInputEl = document.getElementById('dateInput');
                            if (dateInputEl) {
                                dateInputEl.focus();
                            }
                        }, 100);
                    }

                    // Add Skip Button if optional
                    if (data.show_skip_button) {
                        const skipBtnContainer = document.createElement('div');
                        skipBtnContainer.className = 'mt-3';
                        skipBtnContainer.innerHTML = `<button class="text-xs bg-white/5 hover:bg-white/10 text-slate-300 border border-white/10 px-4 py-2 rounded-lg transition-colors flex items-center gap-2" onclick="sendSkip()">Skip optional step <i class="fas fa-forward"></i></button>`;
                        botRow.querySelector('.bot-message-content').appendChild(skipBtnContainer);
                    }

                    chat.appendChild(botRow);

                    // Update file upload button
                    if (data.allow_upload) {
                        attachBtn.classList.remove('disabled');
                        attachBtn.classList.add('active');
                        fileInput.disabled = false;
                    } else {
                        attachBtn.classList.remove('active');
                        attachBtn.classList.add('disabled');
                        fileInput.disabled = true;
                        fileInput.value = "";
                    }
                }

            } catch (error) {
                console.error("Error sending message:", error);

                // Hide typing indicator on error
                hideTypingIndicator();

                // Show error message
                const errorRow = document.createElement('div');
                errorRow.className = 'flex gap-3 max-w-[85%] slide-in';
                errorRow.innerHTML = `
                <div class="size-8 rounded-full bg-primary flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-primary/20 bg-cover bg-center" style="background-image: url('assets/ask-visa-logo-final\ red.png'); background-color: #1a0f0f;">
                </div>
                <div class="space-y-2 w-full">
                    <div class="bg-red-500/10 p-4 rounded-2xl rounded-tl-none border border-red-500/20 shadow-sm glass-effect text-red-200">
                        <p class="text-sm leading-relaxed">Sorry, an error occurred. Please try again.</p>
                    </div>
                    <span class="text-[10px] text-slate-400 px-1 block">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
            `;
                chat.appendChild(errorRow);
            } finally {
                isProcessing = false;
                msgInput.disabled = false;
                sendBtn.disabled = false;
                msgInput.placeholder = "Type your response here...";
                msgInput.focus();
                chat.scrollTop = chat.scrollHeight;
            }
        }

        // Format bold text function for JS
        function formatBold(text) {
            return text.replace(/\\*\\*(.*?)\\*\\*/g, '<b>$1</b>');
        }

        function escapeHtml(text) {
            if (!text) return text;
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Theme Toggle Handler
        themeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        // Function to create payment button
        function createPaymentButton(amount, currency) {
            const button = document.createElement('button');
            button.className = 'glass-effect bg-primary/5 hover:bg-primary/10 text-primary border border-primary/10 px-4 py-2 rounded-lg transition-colors flex items-center gap-2 mt-3';
            button.innerHTML = `<i class="fas fa-money-bill-wave"></i> Pay ${currency} ${amount}`;
            button.onclick = () => {
                // Trigger payment process
                sendMessage('payment_request');
            };
            return button;
        }

        // Function to create select dropdown
        function createSelectDropdown(questionId, options) {
            const container = document.createElement('div');
            container.className = 'w-full max-w-sm pt-2 select-container flex flex-col gap-2';

            options.forEach(opt => {
                const optionDiv = document.createElement('div');
                // Refactored to use dark glass styling
                optionDiv.className = 'select-option group glass-effect bg-white/5 hover:bg-white/10 relative p-4 rounded-xl border border-white/10 cursor-pointer overflow-hidden transition-all hover:border-primary/50';

                // Create inner content structure for better styling
                // Check if label contains price/currency for formatting
                let labelText = opt.option_label || opt.label;
                let valueStr = opt.option_value || opt.value;

                // Try to split logic if it matches our standard format "Name - Currency Price"
                // Regex to match: Name - Currency Price
                // e.g. "Tourist Visa - INR 2500"
                const priceMatch = labelText.match(/^(.*?) - (.*?) (.*?)$/);

                if (priceMatch) {
                    optionDiv.innerHTML = `
                    <div class="flex items-center justify-between">
                    optionDiv.innerHTML = `<div class="font-semibold text-white group-hover:text-primary transition-colors text-sm">${labelText}</div>`;
                }

                optionDiv.dataset.value = valueStr;

                optionDiv.onclick = () => {
                    // Remove selected class from others

                    container.querySelectorAll('.select-option').forEach(el => el.classList.remove('selected'));
                    optionDiv.classList.add('selected');

                    // Store selection
                    currentSelectSelection = optionDiv.dataset.value;
                    currentSelectLabel = labelText; // Capture label for display
                    currentQuestionId = questionId;

                    // Auto-send
                    sendMessage();
                };

                container.appendChild(optionDiv);
            });

            return container;
        }

        // Summary functions
        async function showSummary() {
            try {
                const response = await fetch('?get_summary=1');
                const data = await response.json();

                if (data.success) {
                    const content = document.getElementById('summaryContent');
                    let html = '';

                    // Order Information
                    if (data.order_info) {
                        // Calculate total_people from applicants count
                        const totalPeople = data.data ? Object.keys(data.data).filter(key => key.startsWith('applicant_')).length : 0;

                        html += `
                        <div class="summary-section">
                            <h4><i class="fas fa-info-circle"></i> Order Information</h4>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">Order ID</span>
                                    <span class="summary-value">${data.order_info.id || 'N/A'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Country</span>
                                    <span class="summary-value">${data.country_name || 'N/A'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Primary Contact Email</span>
                                    <span class="summary-value">${data.order_info.email || 'N/A'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Primary Contact Phone</span>
                                    <span class="summary-value">${data.order_info.phone || 'N/A'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Payment Status</span>
                                    <span class="summary-value" style="color: ${data.order_info.payment_status === 'paid' ? '#28a745' : '#dc3545'}; font-weight: 600;">
                                        ${data.order_info.payment_status === 'paid' ? '✅ Paid' : '❌ Pending'}
                                    </span>
                                </div>
                                ${data.order_info.total_amount ? `
                                <div class="summary-item">
                                    <span class="summary-label">Amount Paid</span>
                                    <span class="summary-value">${data.order_info.currency} ${data.order_info.total_amount}</span>
                                </div>
                                ` : ''}
                                <div class="summary-item">
                                    <span class="summary-label">Visa Status</span>
                                    <span class="summary-value" style="color: #4361ee; font-weight: 600;">
                                        ${data.order_info.visa_status || 'initiated'}
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Total Applicants</span>
                                    <span class="summary-value">${totalPeople || 'N/A'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Order Date</span>
                                    <span class="summary-value">${data.order_info.created_at ? new Date(data.order_info.created_at).toLocaleString() : 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                    `;

                        // Payment Information
                        if (data.payment_info) {
                            html += `
                            <div class="summary-section">
                                <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="summary-label">Payment ID</span>
                                        <span class="summary-value">${data.payment_info.provider_payment_id || 'N/A'}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Payment Provider</span>
                                        <span class="summary-value">${data.payment_info.provider || 'Razorpay'}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Amount Paid</span>
                                        <span class="summary-value">${data.payment_info.currency} ${data.payment_info.amount}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Payment Status</span>
                                        <span class="summary-value" style="color: ${data.payment_info.status === 'success' ? '#28a745' : '#dc3545'};">
                                            ${data.payment_info.status ? data.payment_info.status.toUpperCase() : 'N/A'}
                                        </span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Payment Date</span>
                                        <span class="summary-value">${data.payment_info.created_at ? new Date(data.payment_info.created_at).toLocaleString() : 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        }
                    }

                    // Applicants Information
                    if (data.data) {
                        const applicantKeys = Object.keys(data.data).filter(key => key.startsWith('applicant_'));

                        applicantKeys.forEach((applicantKey, index) => {
                            const applicant = data.data[applicantKey];
                            const applicantNum = applicantKey.replace('applicant_', '');

                            html += `
                            <div class="summary-section">
                                <h4><i class="fas fa-user"></i> Applicant #${applicantNum}</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="summary-label">Email</span>
                                        <span class="summary-value">${applicant.email || 'N/A'}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Phone</span>
                                        <span class="summary-value">${applicant.phone || 'N/A'}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Visa Status</span>
                                        <span class="summary-value" style="color: #4361ee; font-weight: 600;">
                                            ${applicant.visa_status || 'submitted'}
                                        </span>
                                    </div>
                                </div>
                        `;

                            // Answers and Files
                            if (applicant.answers) {
                                Object.keys(applicant.answers).forEach(qId => {
                                    const answer = applicant.answers[qId];
                                    const label = data.question_labels ? (data.question_labels[qId] || `Question ${qId}`) : `Question ${qId}`;

                                    // Check if it's a file (contains fetch_file.php)
                                    if (answer.includes('fetch_file.php')) {
                                        const isPdf = answer.includes('.pdf') || answer.toLowerCase().includes('pdf');
                                        const fileName = getFileNameFromPath(answer);

                                        // Ensure the URL is properly formatted
                                        let fileUrl = answer;
                                        if (fileUrl.startsWith('/fetch_file.php')) {
                                            fileUrl = window.location.origin + fileUrl;
                                        } else if (fileUrl.startsWith('fetch_file.php')) {
                                            fileUrl = window.location.origin + '/' + fileUrl;
                                        }

                                        html += `
                                        <div class="summary-file">
                                            <i class="fas ${isPdf ? 'fa-file-pdf' : 'fa-file-image'}"></i>
                                            <div class="file-info">
                                                <h5>${label}</h5>
                                                <p>${fileName} • ${isPdf ? 'PDF Document' : 'Image File'}</p>
                                            </div>
                                            <div class="file-preview">
                                                ${!isPdf ? `
                                                    <img src="${fileUrl}" 
                                                         onclick="openLightbox('${fileUrl}')" 
                                                         alt="${label}"
                                                         style="max-width: 150px; max-height: 150px; object-fit: contain; cursor: pointer; border: 1px solid #ddd;"
                                                         onerror="handleImageError(this, '${fileUrl}')">
                                                    <p style="font-size: 11px; color: var(--gray); margin-top: 4px;">Click to preview</p>
                                                ` : `
                                                    <div onclick="openLightbox('${fileUrl}', true)" style="cursor: pointer; text-align: center;">
                                                        <i class="fas fa-file-pdf" style="font-size: 48px; color: #e53935; margin-bottom: 10px;"></i>
                                                        <p style="font-size: 12px; color: var(--dark); font-weight: 600;">PDF Document</p>
                                                        <p style="font-size: 10px; color: var(--gray);">Click to view</p>
                                                    </div>
                                                `}
                                            </div>
                                        </div>
                                    `;
                                    } else {
                                        html += `
                                        <div class="summary-item">
                                            <span class="summary-label">${label}</span>
                                            <span class="summary-value">${answer}</span>
                                        </div>
                                    `;
                                    }
                                });
                            }

                            html += `</div>`;
                        });
                    }

                    if (html === '') {
                        html = '<div class="empty-state"><i class="fas fa-file-alt"></i><h3>No Application Data</h3><p>Please complete a visa application first to generate a summary.</p></div>';
                    }

                    content.innerHTML = html;
                    document.getElementById('summaryDate').textContent = new Date().toLocaleString();
                    document.getElementById('summaryPopup').style.display = 'flex';
                    document.body.style.overflow = 'hidden';

                    // Store data for PDF download
                    window.summaryData = data;

                    // Show download PDF button if payment was successful
                    if (data.order_info && data.order_info.payment_status === 'paid') {
                        document.getElementById('downloadPdfBtn').classList.remove('hidden');
                    } else {
                        document.getElementById('downloadPdfBtn').classList.add('hidden');
                    }

                } else {
                    alert('No application data found. Please complete an application first.');
                }
            } catch (error) {
                console.error('Error fetching summary:', error);
                alert('Unable to fetch summary data. Please try again.');
            }
        }

        // Helper function to extract filename from path
        function getFileNameFromPath(path) {
            try {
                // If it's a fetch_file.php URL, extract the path parameter
                if (path.includes('fetch_file.php')) {
                    const url = new URL(path, window.location.origin);
                    const filePath = url.searchParams.get('path');
                    if (filePath) {
                        const parts = filePath.split('/');
                        return decodeURIComponent(parts[parts.length - 1]);
                    }
                }
                // Otherwise, try to extract from the string
                const parts = path.split('/');
                const filename = parts[parts.length - 1];
                return decodeURIComponent(filename.split('?')[0].split('#')[0]);
            } catch (e) {
                return 'file';
            }
        }

        // Function to close summary popup
        function closeSummaryPopup() {
            document.getElementById('summaryPopup').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Error handling for images
        function handleImageError(img, fileUrl) {
            console.error('Failed to load image:', fileUrl);
            img.onerror = null; // Prevent infinite loop
            img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9Ijc1IiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBub3QgYXZhaWxhYmxlPC90ZXh0Pjwvc3ZnPg==';
            img.style.cursor = 'default';
            img.onclick = null;
            img.title = 'Image could not be loaded';
        }

        // Function to download summary as PDF
        async function downloadSummaryAsPDF() {
            // Hide the download button
            if (downloadPdfBtn) {
                downloadPdfBtn.classList.add('hidden');
            }

            const data = window.summaryData || {};
            if (!data.success) {
                alert('No summary data available. Please generate a summary first.');
                if (downloadPdfBtn) {
                    downloadPdfBtn.classList.remove('hidden');
                }
                return;
            }

            const orderId = data.order_info ? data.order_info.id : '';
            if (!orderId) {
                alert('Order ID not found. Cannot generate PDF.');
                if (downloadPdfBtn) {
                    downloadPdfBtn.classList.remove('hidden');
                }
                return;
            }

            // Show loading message
            const originalContent = document.getElementById('summaryContent').innerHTML;
            document.getElementById('summaryContent').innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <div class="loading-dots" style="justify-content: center; margin-bottom: 20px;">
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                </div>
                <h3 style="color: #4361ee; margin-bottom: 10px;">Generating PDF...</h3>
                <p style="color: #666;">Please wait while we generate your PDF document.</p>
                <p style="color: #666; font-size: 12px; margin-top: 20px;">
                    Order #${orderId}
                </p>
            </div>
        `;

            try {
                // Generate PDF using server-side TCPDF
                window.open('generate_pdf.php?order_id=' + orderId, '_blank');

                // Restore original content after a short delay
                setTimeout(() => {
                    document.getElementById('summaryContent').innerHTML = originalContent;
                    if (downloadPdfBtn) {
                        downloadPdfBtn.classList.remove('hidden');
                    }
                }, 2000);

            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Error generating PDF. Please try again. ' + error.message);

                // Restore original content on error
                document.getElementById('summaryContent').innerHTML = originalContent;
                if (downloadPdfBtn) {
                    downloadPdfBtn.classList.remove('hidden');
                }
            }
        }

        // Enter key to send message
        msgInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Also handle enter key for date input
        document.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                const dateInput = document.getElementById('dateInput');
                if (dateInput && dateInput === document.activeElement) {
                    e.preventDefault();
                    sendMessage();
                }
            }
        });

        // Auto-format date input (YYYY/MM/DD)
        document.addEventListener('input', (e) => {
            if (e.target && e.target.id === 'dateInput') {
                let input = e.target.value;

                // Remove non-numeric characters (except slashes if already typed, but we'll re-add them)
                input = input.replace(/\D/g, '');

                // Add slashes for YYYY/MM/DD
                if (input.length > 4) {
                    input = input.substring(0, 4) + '/' + input.substring(4);
                }
                if (input.length > 7) {
                    input = input.substring(0, 7) + '/' + input.substring(7);
                }

                // Limit length
                if (input.length > 10) {
                    input = input.substring(0, 10);
                }

                e.target.value = input;
            }
        });



        // Function to create payment button (User Provided)
        function createPaymentButton(amount, currency, orderId) {
            const container = document.createElement('div');
            container.className = 'payment-container';

            const btn = document.createElement('button');
            btn.className = 'payment-button';
            btn.innerHTML = `Pay ${currency} ${amount} Now`;

            btn.onclick = () => {
                // Use the orderId passed to the function or handle it via initiatePayment
                initiatePayment(amount, currency);
            };

            container.appendChild(btn);
            return container;
        }

        // Function to initiate payment (User Provided)
        async function initiatePayment(amount, currency) {
            try {
                const createOrderResponse = await fetch('?ajax=1', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'create_order=1'
                });

                const orderData = await createOrderResponse.json();

                if (orderData.success && orderData.redirect_url) {
                    window.location.href = orderData.redirect_url;
                } else {
                    throw new Error(orderData.message || 'Failed to create payment order');
                }
            } catch (error) {
                console.error('Payment initiation error:', error);
                alert('Error initiating payment: ' + error.message);
            }
        }

        window.addEventListener('load', () => {
            // Restore Theme
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark');
                themeToggle.checked = true;
            }

            chat.scrollTop = chat.scrollHeight;

            // Initial Fetch for Progress and Upload status
            const initialFormData = new FormData();
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            initialFormData.append('csrf_token', csrfToken);

            fetch('?ajax=1', { method: 'POST', body: initialFormData })
                .then(r => r.json())
                .then(data => {
                    updateProgressDisplay(data);
                    if (data.allow_upload) {
                        attachBtn.classList.remove('disabled');
                        attachBtn.classList.add('active');
                        fileInput.disabled = false;
                    }

                    // Set current order ID if available
                    if (data.order_id) {
                        currentOrderId = data.order_id;
                    }

                    // Check for select dropdown on load
                    if (data.show_select_dropdown && data.select_options && data.select_options.length > 0) {
                        const selectDropdown = createSelectDropdown(data.current_question_id, data.select_options);
                        selectDropdown.classList.add('bot-options-container');
                        selectDropdown.style.marginLeft = "45px";
                        selectDropdown.style.marginBottom = "20px";
                        chat.appendChild(selectDropdown);

                        msgInput.placeholder = "Select an option above";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;
                        chat.scrollTop = chat.scrollHeight;
                    }

                    // ADD THIS: Check if we should show payment button when page loads
                    if (data.step === 'payment') {
                        // Get payment amount from AJAX response
                        const paymentAmount = data.payment_amount || 0;
                        const currency = data.currency || 'INR';

                        // Check if the chat already has a payment button at the end
                        const lastMessage = chat.lastElementChild;
                        const hasPaymentButton = lastMessage && lastMessage.querySelector('.payment-container');

                        if (!hasPaymentButton && paymentAmount > 0) {
                            // Create a fresh bot message with the button
                            const botRow = document.createElement('div');
                            botRow.className = 'message-row bot';
                            botRow.innerHTML = `
                        <div class="message-avatar">AI</div>
                        <div class="message-content">
                            <div class="message-text"><b>Action Required:</b> Please click the button below to complete your payment.</div>
                            <div class="message-time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                        </div>
                    `;

                            const paymentButton = createPaymentButton(paymentAmount, currency);
                            botRow.querySelector('.message-content').appendChild(paymentButton);
                            chat.appendChild(botRow);
                            chat.scrollTop = chat.scrollHeight;

                            // Update input placeholder
                            msgInput.placeholder = "Click the payment button above to proceed";
                            msgInput.disabled = true;
                            sendBtn.disabled = true;
                        }
                    }

                    // Show completion state if finished
                    if (data.is_finished) {
                        if (currentOrderId) {
                            finalOrderId.textContent = '#' + currentOrderId;
                        }
                        completionState.style.display = 'block';
                        chat.scrollTop = chat.scrollHeight;
                    }
                });

            msgInput.focus();
        });
        // Function to send skip message
        function sendSkip() {
            msgInput.value = "skip";
            sendMessage();
        }
    </script>
    <!-- <script src="automation.js"></script> -->
</body>

</html>