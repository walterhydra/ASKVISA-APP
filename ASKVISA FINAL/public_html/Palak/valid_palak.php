<?php

session_start();
require 'db.php';

// Check for successful payment
if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
    $order_id = $_GET['order_id'] ?? '';

    // Update session status
    $_SESSION['payment_status'] = 'paid';
    $_SESSION['step'] = 'payment_complete';

    // Update order Id
    if ($order_id && !isset($_SESSION['current_order_id'])) {
        $_SESSION['current_order_id'] = $order_id;
    }

    // REPLACE the last payment message with success message
    if (!isset($_SESSION['payment_success_shown'])) {

        $lastIndex = count($_SESSION['messages']) - 1;
        for ($i = $lastIndex; $i >= 0; $i--) {
            if (
                isset($_SESSION['messages'][$i]['text'])

            ) {

                $_SESSION['messages'][$i] = [
                    'role' => 'bot',
                    'text' => "json_button:status:✅ **Payment Successful!** Your order #$order_id has been confirmed. Click 'Check Status' below to view your application status."
                ];
                break;
            }
        }

        $_SESSION['payment_success_shown'] = true;
    }
}


// check database for payment status on page load
if (isset($_SESSION['current_order_id']) && is_numeric($_SESSION['current_order_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT payment_status FROM visa_orders WHERE id = ?");
        $stmt->execute([$_SESSION['current_order_id']]);
        $order = $stmt->fetch();

        if ($order && $order['payment_status'] === 'paid') {
            $_SESSION['payment_status'] = 'paid';
            $_SESSION['step'] = 'payment_complete';
        }
    } catch (Exception $e) {
        // Ignore errors
    }
}

// Handle summary data request
if (isset($_GET['get_summary'])) {
    $data = [];

    // Get order ID from session
    $order_id = $_SESSION['current_order_id'] ?? 0;

    if ($order_id) {
        // Fetch from database instead of session
        try {
            // Get order info
            $stmt = $pdo->prepare("SELECT 
                vo.id, 
                vo.email, 
                vo.phone,
                vo.payment_status,
                vo.total_amount,
                vo.currency,
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
                    'currency' => $order_info['currency']
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

                // Get all applicants for this order
                $stmt = $pdo->prepare("SELECT id, applicant_no, applicant_email, applicant_phone FROM applicants WHERE order_id = ? ORDER BY applicant_no ASC");
                $stmt->execute([$order_id]);
                $applicants = $stmt->fetchAll();
                $applicant_no = $applicant['applicant_no'];

                foreach ($applicants as $applicant) {
                    $applicant_key = "applicant_" . $applicant['applicant_no'];

                    // Get applicant's answers (text fields)
                    $stmt = $pdo->prepare("SELECT 
                        q.id as question_id,
                        q.label,
                        q.field_key,
                        q.field_type,
                        aa.answer_text
                        FROM applicant_answers aa
                        JOIN country_questions q ON aa.question_id = q.id
                        WHERE aa.order_id = ? AND aa.applicant_id = ?
                        ORDER BY q.sort_order ASC");
                    $stmt->execute([$order_id, $applicant['id']]);
                    $answers = $stmt->fetchAll();

                    // Get applicant's files - IMPORTANT: file_path already contains /fetch_file.php?path=encoded_path
                    $stmt = $pdo->prepare("SELECT 
                        q.id as question_id,
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

                    $data[$applicant_key] = [
                        'email' => $applicant['applicant_email'],
                        'phone' => $applicant['applicant_phone'],
                        'answers' => []
                    ];

                    // Combine answers and files
                    foreach ($answers as $answer) {
                        $data[$applicant_key]['answers'][$answer['question_id']] = $answer['answer_text'];
                        $data['question_labels'][$answer['question_id']] = $answer['label'];
                    }

                    foreach ($files as $file) {
                        // IMPORTANT: file_path already contains /fetch_file.php?path=encoded_path
                        $data[$applicant_key]['answers'][$file['question_id']] = $file['file_path'];
                        $data['question_labels'][$file['question_id']] = $file['label'];
                    }
                }

                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'country_name' => $data['country_name'] ?? '',
                    'order_info' => $data['order_info'] ?? [],
                    'payment_info' => $data['payment_info'] ?? null,
                    'question_labels' => $data['question_labels'] ?? [],
                    'visa_status' => $order['visa_status'] ?? 'Processing'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching summary: ' . $e->getMessage()]);
        }
    } else {
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
                    'id' => $_SESSION['current_order_id'] ?? ''
                ],
                'question_labels' => $question_labels
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No data available']);
        }
    }
    exit;
}

// Initialize session variables if they don't exist
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [['role' => 'bot', 'text' => 'Hello! 👋 Which country are you applying for?']];
    $_SESSION['step'] = 'country';
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

// Helper function to format bold text for initial page load
function formatBold($text)
{
    return preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $text);
}

// Function to parse and apply validation rules from database
function applyValidationRules($value, $validation_rules, $field_key = '', $context_data = [], $country_name = '')
{
    $errors = [];

    // If no validation rules or empty JSON, return empty errors
    if (empty($validation_rules) || $validation_rules === '[]' || $validation_rules === '{}') {
        return $errors;
    }

    // Decode JSON validation rules
    $rules = json_decode($validation_rules, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $errors;
    }

    // Check required field
    if (isset($rules['required']) && $rules['required'] === true) {
        if (empty(trim($value))) {
            $errors[] = "This field is required.";
        }
    }

    // Check min_length
    if (isset($rules['min_length']) && strlen(trim($value)) < $rules['min_length']) {
        $errors[] = sprintf("Minimum %d characters required.", $rules['min_length']);
    }

    // Check max_length
    if (isset($rules['max_length']) && strlen(trim($value)) > $rules['max_length']) {
        $errors[] = sprintf("Maximum %d characters allowed.", $rules['max_length']);
    }

    // Check regex pattern
    if (isset($rules['regex']) && !empty($value)) {
        // Clean the regex pattern (remove escaping)
        $regex_pattern = str_replace('\\\\', '\\', $rules['regex']);
        if (!preg_match('/' . $regex_pattern . '/', $value)) {
            // Provide specific hints based on field type
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

    // Check date format
    if (isset($rules['date_format'])) {
        $date_error = validateDateWithRules($value, $rules, $context_data, $field_key);
        if ($date_error) {
            $errors[] = $date_error;
        }
    }

    return $errors;
}

// Function to validate dates based on rules
function validateDateWithRules($date, $rules, $context_data = [], $field_key = '')
{
    if (empty($date)) {
        return null;
    }

    // Check date format - always DD-MM-YYYY for your system
    $d = DateTime::createFromFormat('d-m-Y', $date);
    if (!$d || $d->format('d-m-Y') !== $date) {
        return "Date must be in DD-MM-YYYY format (e.g., 31-12-2024).";
    }

    $date_obj = DateTime::createFromFormat('d-m-Y', $date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    // Check min_date
    if (isset($rules['min_date'])) {
        $min_date_str = $rules['min_date'];
        if ($min_date_str === 'TODAY') {
            $min_date = clone $today;
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $min_date_str)) {
            $min_date = DateTime::createFromFormat('d-m-Y', $min_date_str);
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

    // Check max_date
    if (isset($rules['max_date'])) {
        $max_date_str = $rules['max_date'];
        if ($max_date_str === 'TODAY') {
            $max_date = clone $today;
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $max_date_str)) {
            $max_date = DateTime::createFromFormat('d-m-Y', $max_date_str);
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

    // Check min_validity_days (for expiry dates)
    if (isset($rules['min_validity_days'])) {
        $days_required = (int)$rules['min_validity_days'];
        $future_date = clone $today;
        $future_date->modify("+$days_required days");

        if ($date_obj < $future_date) {
            return sprintf("Passport must be valid for at least %d more days.", $days_required);
        }
    }

    // Validate passport expiry > issue date
    if ($field_key === 'passport_expiry_date') {
        // Look for issue date in context
        foreach ($context_data as $key => $val) {
            if (strpos(strtolower($key), 'issue') !== false && preg_match('/^\d{2}-\d{2}-\d{4}$/', $val)) {
                $issue_date = DateTime::createFromFormat('d-m-Y', $val);
                if ($issue_date && $date_obj <= $issue_date) {
                    return "Expiry date must be after issue date.";
                }
            }
        }
    }

    // Validate issue date < expiry date
    if ($field_key === 'passport_issue_date') {
        // Look for expiry date in context
        foreach ($context_data as $key => $val) {
            if ((strpos(strtolower($key), 'expiry') !== false || strpos(strtolower($key), 'expiration') !== false) &&
                preg_match('/^\d{2}-\d{2}-\d{4}$/', $val)
            ) {
                $expiry_date = DateTime::createFromFormat('d-m-Y', $val);
                if ($expiry_date && $date_obj >= $expiry_date) {
                    return "Issue date must be before expiry date.";
                }
            }
        }
    }

    return null;
}

// Function to validate file uploads based on rules
function validateFileUpload($file, $validation_rules,)
{
    $errors = [];

    if (empty($validation_rules)) {
        return $errors;
    }

    $rules = json_decode($validation_rules, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $errors;
    }

    // Check required
    if (isset($rules['required']) && $rules['required'] === true && empty($file['name'])) {
        $errors[] = "This file is required.";
        return $errors;
    }

    if (empty($file['name'])) {
        return $errors;
    }

    // Check file types
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
                if ($type === 'image/jpeg') $allowed_types[] = 'JPG';
                if ($type === 'image/png') $allowed_types[] = 'PNG';
                if ($type === 'application/pdf') $allowed_types[] = 'PDF';
            }
            $errors[] = sprintf("Invalid file type. Allowed formats: %s.", implode(', ', array_unique($allowed_types)));
        }
    }

    // Check file size
    if (isset($rules['max_size'])) {
        $max_size = (int)$rules['max_size']; // in bytes
        if ($file['size'] > $max_size) {
            $mb_size = round($max_size / 1024 / 1024, 1);
            $errors[] = sprintf("File too large. Maximum size: %dMB.", $mb_size);
        }
    }

    return $errors;
}

// Function to get select field options from database
function getSelectOptions($question_id, $pdo)
{
    $options = [];
    $stmt = $pdo->prepare("SELECT option_value, option_label FROM question_options WHERE question_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$question_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $options;
}

// Function to validate select field answer
function validateSelectAnswer($value, $question_id, $pdo)
{
    if (empty($value)) {
        return "Please select an option.";
    }

    // Get valid options from database
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

// Function to detect if question is asking for a date
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

// Function to get context data for dependent validations
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

// Phone number validation by country
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
    } elseif (strpos($country_lower, 'china') !== false) {
        if (!preg_match('/^1[0-9]{10}$/', $cleaned_phone)) {
            return "Chinese phone number must be 11 digits starting with 1.";
        }
    } elseif (strpos($country_lower, 'germany') !== false) {
        if (!preg_match('/^[0-9]{10,13}$/', $cleaned_phone)) {
            return "German phone number must be 10-13 digits.";
        }
    } elseif (strpos($country_lower, 'france') !== false) {
        if (!preg_match('/^[0-9]{9}$/', $cleaned_phone)) {
            return "French phone number must be 9 digits.";
        }
    } elseif (strpos($country_lower, 'thailand') !== false) {
        if (!preg_match('/^[0-9]{9,10}$/', $cleaned_phone)) {
            return "Thai phone number must be 9-10 digits.";
        }
    } else {
        if (!preg_match('/^\+?[0-9]{8,15}$/', $cleaned_phone)) {
            return "Phone number must be 8-15 digits, may start with +.";
        }
    }

    return null;
}
// Please remove this after payment gateway integration
// Simulate payment processing
function processPayment()
{
    // For demo purposes, simulate successful payment
    sleep(2); // Simulate processing time

    // Generate a fake transaction ID
    $transaction_id = 'TXN_' . strtoupper(uniqid()) . '_' . date('YmdHis');

    // For demo, all payments succeed
    return [
        'success' => true,
        'transaction_id' => $transaction_id,
        'provider_payment_id' => 'PAY_' . strtoupper(uniqid()),
        'message' => 'Payment processed successfully'
    ];
}

if (isset($_GET['ajax'])) {
    $msg = htmlspecialchars(trim($_POST['message'] ?? ''));
    $response = "";
    $img_path = "";
    $progress = 0;

    if ($msg === 'get_step' || $msg === 'debug_info') {
        // Return session information
        echo json_encode([
            'step' => $_SESSION['step'] ?? 'NOT SET',
            'current_person_num' => $_SESSION['current_person_num'] ?? 'NOT SET',
            'total_people' => $_SESSION['total_people'] ?? 'NOT SET',
            'q_idx' => $_SESSION['q_idx'] ?? 'NOT SET',
            'country_name' => $_SESSION['country_name'] ?? 'NOT SET',
            'country_id' => $_SESSION['country_id'] ?? 'NOT SET',
            'order_contact_email' => $_SESSION['order_contact_email'] ?? 'NOT SET',
            'order_contact_phone' => $_SESSION['order_contact_phone'] ?? 'NOT SET',
            'order_amount' => $_SESSION['order_amount'] ?? 'NOT SET',
            'order_currency' => $_SESSION['order_currency'] ?? 'NOT SET',
            'current_order_id' => $_SESSION['current_order_id'] ?? 'NOT SET',
            'payment_pending' => $_SESSION['payment_pending'] ?? 'NOT SET',
            'has_collected_info' => !empty($_SESSION['collected_info']) ? 'YES' : 'NO',
            'has_db_questions' => isset($_SESSION['db_questions']) ? 'YES' : 'NO',
            'session_keys' => array_keys($_SESSION),
            'show_paynow_button' => ($_SESSION['step'] ?? '') === 'payment_now',
            'step_label' => $step_label ?? '',
            'step_count' => $step_count ?? '',
            'progress' => $progress ?? 0,
            'order_id' => $_SESSION['current_order_id'] ?? null,
            'visa_status' => $order['visa_status'] ?? 'Processing'
        ]);
        exit;
    }

    // Check if this is a select field selection
    $is_select_selection = isset($_POST['select_value']) && $_POST['select_value'] !== '';
    if ($is_select_selection) {
        $msg = $_POST['select_value'];
    }

    // 1. Handle File Uploads with validation
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $home_dir = dirname($_SERVER['DOCUMENT_ROOT']);
        $base_gov_id = $home_dir . '/gov_id/';

        if (!isset($_SESSION['order_folder_name'])) {
            $_SESSION['order_folder_name'] = 'order_'. $order_id;
        }

        $p_num = $_SESSION['current_person_num'] ?? 1;
        $sub_path = date('Y/m/d') . '/' . $_SESSION['order_folder_name'] . '/applicant_' . $p_num;
        $full_dir = $base_gov_id . $sub_path . '/';

        if (!is_dir($full_dir)) mkdir($full_dir, 0775, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $applicant_no = $applicant['applicant_no'];
        $filename = 'order_' . $order_id . '_applicant_' . $applicant_no . '.' . $extension;
        $target = $full_dir . $filename;

        // Get validation rules for current question if it's a file field
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
                // Store as /fetch_file.php?path=encoded_path (for compatibility with other sites)
                $relative_file_path = $sub_path . '/' . $filename;
                $img_path = '/fetch_file.php?path=' . urlencode($relative_file_path);
            }
        } else {
            $response = implode("\n", $upload_errors);
        }
    }

    // 2. Main Logic
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
                $stmt = $pdo->prepare("SELECT id, country_name FROM countries WHERE country_name LIKE ? AND is_active = 1 LIMIT 1");
                $stmt->execute(["%$msg%"]);
                $country = $stmt->fetch();
                if ($country) {
                    $_SESSION['country_id'] = $country['id'];
                    $_SESSION['country_name'] = $country['country_name'];

                    // Fetch questions with validation rules
                    $q_stmt = $pdo->prepare("SELECT id, label, field_type, validation_rules, field_key FROM country_questions WHERE country_id = ? ORDER BY sort_order ASC");
                    $q_stmt->execute([$country['id']]);
                    $_SESSION['db_questions'] = $q_stmt->fetchAll();

                    // Store question data for validation lookups
                    $_SESSION['question_data'] = [];
                    foreach ($_SESSION['db_questions'] as $q) {
                        $_SESSION['question_data'][$q['id']] = [
                            'label' => $q['label'],
                            'field_key' => $q['field_key'],
                            'field_type' => $q['field_type'],
                            'validation_rules' => $q['validation_rules']
                        ];

                        // If it's a select field, fetch options now
                        if ($q['field_type'] === 'select') {
                            $_SESSION['question_data'][$q['id']]['options'] = getSelectOptions($q['id'], $pdo);
                        }
                    }

                    $_SESSION['step'] = 'how_many';
                    $response = "Selected: **" . trim($country['country_name']) . "**. How many applicants?";
                } else {
                    $response = "Sorry we currently don't support " . $msg . ". Please try another Country.";
                }
                break;

            case 'how_many':
                if (is_numeric($msg) && (int)$msg > 0 && (int)$msg <= 20) {
                    $_SESSION['total_people'] = (int)$msg;
                    $_SESSION['current_person_num'] = 1;
                    $_SESSION['q_idx'] = 0;
                    $_SESSION['step'] = 'details';

                    // Get first question
                    $first_q = $_SESSION['db_questions'][0];
                    $first_q_id = $first_q['id'];
                    $first_q_label = $first_q['label'];
                    $first_field_type = $first_q['field_type'];

                    // Check if first question is select field
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

                if ($current_field_type === 'file' && !$img_path) {
                    $response = "I need a file for: **" . trim($current_q_label) . "**. Please use the 📎 icon.";
                } else {
                    $validation_errors = [];

                    if ($img_path === '') {
                        // If it's a select field, validate against options
                        if ($current_field_type === 'select') {
                            $select_error = validateSelectAnswer($msg, $current_q_id, $pdo);
                            if ($select_error) {
                                $validation_errors[] = $select_error;
                            }
                        }

                        // Apply database validation rules (for text, date, etc.)
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
                        // Store the answer
                        $_SESSION['collected_info']["applicant_$p_num"]['answers'][$current_q_id] = $img_path ?: $msg;
                        $_SESSION['q_idx']++;

                        if ($_SESSION['q_idx'] < count($questions)) {
                            $next_q = $questions[$_SESSION['q_idx']];
                            $next_q_id = $next_q['id'];
                            $next_q_label = $next_q['label'];
                            $next_field_type = $next_q['field_type'];

                            // Check if next question is select field
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
                // Email validation
                if (!filter_var($msg, FILTER_VALIDATE_EMAIL)) {
                    $response = "Please enter a valid email address for Applicant #$p_num.";
                } else {
                    $_SESSION['collected_info']["applicant_$p_num"]['email'] = $msg;
                    $_SESSION['step'] = 'applicant_phone';
                    $response = "What is the **phone number** for Applicant #$p_num?";
                }
                break;

            case 'applicant_phone':
                // Phone validation based on country
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

                        // Check if first question is select field
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
                // Phone validation for order contact based on country
                if (isset($_SESSION['country_name'])) {
                    $validation_error = validatePhoneNumber($msg, $_SESSION['country_name']);
                } else {
                    $validation_error = "Please enter a valid phone number for the primary contact.";
                }

                if ($validation_error) {
                    $response = $validation_error;
                } else {
                    $_SESSION['order_contact_phone'] = $msg;

                    // Calculate total amount
                    $amount_per_applicant = 100; // Example: $100 per applicant
                    $total_amount = $_SESSION['total_people'] * $amount_per_applicant;
                    $_SESSION['order_amount'] = $total_amount;
                    $_SESSION['order_currency'] = 'USD';

                    try {
                        // to avoid duplicate pending inputs for same order
                        if (isset($_SESSION['current_order_id']) && strpos($_SESSION['current_order_id'], 'TEMP_') !== 0) {
                            // updating with order ID (application ID) 
                            $stmt = $pdo->prepare("UPDATE visa_orders SET 
                    country_id = ?, 
                    email = ?, 
                    phone = ?, 
                    total_amount = ?, 
                    currency = ?,
                    payment_status = 'pending'
                    WHERE id = ?");
                            $stmt->execute([
                                $_SESSION['country_id'] ?? 0,
                                $_SESSION['order_contact_email'] ?? '',
                                $_SESSION['order_contact_phone'] ?? '',
                                $total_amount,
                                $_SESSION['order_currency'] ?? 'USD',
                                $_SESSION['current_order_id']
                            ]);

                            $order_id = $_SESSION['current_order_id'];
                            error_log("✅ Updated existing pending order: $order_id");
                        } else {
                            // Create new order with 'pending' status
                            $stmt = $pdo->prepare("INSERT INTO visa_orders 
                    (country_id, email, phone, payment_status, total_amount, currency, created_at) 
                    VALUES (?, ?, ?, 'pending', ?, ?, NOW())");

                            $stmt->execute([
                                $_SESSION['country_id'] ?? 0,
                                $_SESSION['order_contact_email'] ?? '',
                                $_SESSION['order_contact_phone'] ?? '',
                                $total_amount,
                                $_SESSION['order_currency'] ?? 'USD'
                            ]);

                            $order_id = $pdo->lastInsertId();
                            $_SESSION['current_order_id'] = $order_id;

                            error_log("✅ Created new pending order: $order_id");

                            // Save applicant data to database
                            for ($i = 1; $i <= $_SESSION['total_people']; $i++) {
                                if (isset($_SESSION['collected_info']["applicant_$i"])) {
                                    $app_data = $_SESSION['collected_info']["applicant_$i"];

                                    // Insert applicant
                                    $stmt = $pdo->prepare("INSERT INTO applicants 
                            (order_id, applicant_no, applicant_email, applicant_phone, visa_status) 
                            VALUES (?, ?, ?, ?, 'pending')");
                                    $stmt->execute([
                                        $order_id,
                                        $i,
                                        $app_data['email'] ?? '',
                                        $app_data['phone'] ?? ''
                                    ]);

                                    $app_id = $pdo->lastInsertId();

                                    // Save answers
                                    if (isset($app_data['answers']) && is_array($app_data['answers'])) {
                                        foreach ($app_data['answers'] as $q_id => $val) {
                                            $field_type = strpos($val, 'fetch_file.php') !== false ? 'file' : 'text';

                                            $stmt = $pdo->prepare("INSERT INTO applicant_answers 
                                    (order_id, applicant_id, question_id, answer_type, answer_text) 
                                    VALUES (?, ?, ?, ?, ?)");
                                            $stmt->execute([$order_id, $app_id, $q_id, $field_type, $val]);
                                        }
                                    }
                                }
                            }
                        }

                        // Store order ID in session for payment page
                        $_SESSION['payment_order_id'] = $order_id;
                    } catch (Exception $e) {
                        error_log("Error saving order: " . $e->getMessage());
                        // TEMP ID FOR DEMO ONLY
                        $_SESSION['current_order_id'] = 'TEMP_' . time() . '_' . rand(1000, 9999);
                        $_SESSION['payment_order_id'] = $_SESSION['current_order_id'];
                    }

                    $_SESSION['step'] = 'payment_now';
                    $response = "json_button:payment:Great! Total amount due: **$$total_amount**. Click the button below to proceed with payment.";

                    break;
                }
            case 'payment_now':
                // This is triggered when user clicks "Pay Now" button
                $payment_result = processPayment(null, $_SESSION['order_amount']);

                if ($payment_result['success']) {
                    $_SESSION['transaction_id'] = $payment_result['transaction_id'];
                    $_SESSION['provider_payment_id'] = $payment_result['provider_payment_id'];
                } else {
                    $response = "Payment failed. Please try again or contact support.";
                    $_SESSION['step'] = 'payment_error';
                }
                break;

            case 'payment_error':
                $response = "There was an error with your payment. Please contact support.";
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
    $show_paynow_button = false;
    $select_options = [];

    switch ($_SESSION['step']) {
        case 'country':
            $progress = 0;
            $step_label = "Country Selection";
            break;

        case 'how_many':
            $progress = 10;
            $step_label = "Applicant Count";
            break;

        case 'details':
            if (isset($_SESSION['db_questions'][$_SESSION['q_idx']])) {
                $current_q = $_SESSION['db_questions'][$_SESSION['q_idx']];
                $allow_upload = ($current_q['field_type'] === 'file');
                $total_questions = count($_SESSION['db_questions']);
                $progress = round((($_SESSION['q_idx']) / $total_questions) * 70 + 10);
                $step_label = "Document " . ($_SESSION['q_idx'] + 1) . " of " . $total_questions;

                // Check if current question is a date question
                $validation_rules = $current_q['validation_rules'] ?? '';
                if (isDateQuestion($validation_rules)) {
                    $show_date_calendar = true;
                }

                // Check if current question is a select field
                if ($current_q['field_type'] === 'select') {
                    $show_select_dropdown = true;
                    // Get options for select field
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

        case 'payment_now':
            $progress = 98;
            $step_label = "Payment";
            $show_paynow_button = true;
            break;

        case 'finish':
            $progress = 100;
            $step_label = "Complete";
            break;
    }

    $step_count = "Step ";
    switch ($_SESSION['step']) {
        case 'country':
            $step_count .= "1/8";
            break;
        case 'how_many':
            $step_count .= "2/8";
            break;
        case 'details':
            $step_count .= "3/8";
            break;
        case 'applicant_email':
            $step_count .= "4/8";
            break;
        case 'applicant_phone':
            $step_count .= "5/8";
            break;
        case 'order_email':
            $step_count .= "6/8";
            break;
        case 'order_phone':
            $step_count .= "7/8";
            break;
        case 'payment_now':
            $step_count .= "8/8";
            break;
        case 'finish':
            $step_count .= "8/8";
            break;
        default:
            $step_count .= "1/8";
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
        'show_paynow_button' => $show_paynow_button,
        'select_options' => $select_options,
        'current_question_id' => isset($_SESSION['db_questions'][$_SESSION['q_idx']]['id']) ? $_SESSION['db_questions'][$_SESSION['q_idx']]['id'] : null,
        'order_id' => $_SESSION['current_order_id'] ?? null,
        'visa_status' => $order['visa_status'] ?? 'Processing',
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1a1b26;
            --dark-light: #24283b;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 16px;
            --border-radius-sm: 8px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            --box-shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.dark {
            --light: #1a1b26;
            --dark: #f8f9fa;
            --gray-light: #24283b;
            --gray: #a9b1d6;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            --box-shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            height: 100vh;
            overflow: hidden;
            transition: var(--transition);
        }

        .app-container {
            display: flex;
            height: 100vh;
            max-width: 1600px;
            margin: 0 auto;
            overflow: hidden;
        }

        .sidebar {
            width: 320px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            color: white;
            box-shadow: var(--box-shadow-lg);
            z-index: 10;
            position: relative;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>'),
                radial-gradient(circle at 20% 80%, rgba(76, 201, 240, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(67, 97, 238, 0.1) 0%, transparent 50%);
            background-size: cover, cover, cover;
            pointer-events: none;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
            animation: logoFloat 4s ease-in-out infinite;
        }

        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .logo-text p {
            font-size: 13px;
            opacity: 0.9;
        }

        .progress-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .progress-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shimmerBorder 3s linear infinite;
        }

        .step-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        .step-label {
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .step-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            animation: countPulse 2s infinite;
        }

        .progress-container {
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4cc9f0, #4895ef, #4cc9f0);
            background-size: 200% 100%;
            border-radius: 4px;
            width: 0%;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            animation: progressPulse 2s infinite, progressShimmer 3s linear infinite;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 2s infinite;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .sidebar-actions {
            margin-top: auto;
            position: relative;
            z-index: 1;
        }

        .action-btn {
            width: 100%;
            padding: 14px;
            border-radius: var(--border-radius-sm);
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 16px;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s ease;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .action-btn.danger {
            background: rgba(247, 37, 133, 0.2);
        }

        .action-btn.danger:hover {
            background: rgba(247, 37, 133, 0.3);
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 18px;
            border-radius: var(--border-radius-sm);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .theme-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .switch {
            position: relative;
            width: 52px;
            height: 28px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.3);
            transition: var(--transition);
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        input:checked+.slider {
            background-color: var(--success);
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }

        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        .chat-header {
            padding: 20px 30px;
            background: var(--light);
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            z-index: 5;
            position: relative;
        }

        .chat-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--success), var(--primary));
            background-size: 200% 100%;
            animation: headerShimmer 3s linear infinite;
        }

        .chat-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .chat-title p {
            font-size: 14px;
            color: var(--gray);
            margin-top: 4px;
        }

        .chat-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--success);
            font-weight: 500;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 10px var(--success);
        }

        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            scroll-behavior: smooth;
            position: relative;
        }

        .chat-container::-webkit-scrollbar {
            width: 6px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: var(--gray-light);
            border-radius: 3px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: var(--gray);
        }

        .message-row {
            display: flex;
            margin-bottom: 24px;
            animation: fadeIn 0.4s ease-out;
            position: relative;
        }

        .message-row.bot {
            justify-content: flex-start;
            animation: slideInLeft 0.5s ease-out;
        }

        .message-row.user {
            justify-content: flex-end;
            animation: slideInRight 0.5s ease-out;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 8px 12px 0 0;
            flex-shrink: 0;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 14px;
            position: relative;
        }

        .message-avatar::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: var(--primary-light);
            animation: avatarSpin 2s linear infinite;
        }

        .message-row.user .message-avatar {
            margin: 8px 0 0 12px;
            background: var(--primary-light);
        }

        .message-content {
            max-width: 70%;
            padding: 10px 16px;
            border-radius: var(--border-radius);
            position: relative;
            box-shadow: var(--box-shadow);
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-row.bot .message-content {
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-top-left-radius: 4px;
            color: var(--dark);
        }

        .message-row.user .message-content {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-text {
            font-size: 15px;
        }

        .message-text b {
            font-weight: 700;
            color: inherit;
        }

        .message-row.bot .message-text b {
            color: var(--primary);
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 8px;
            text-align: right;
        }

        .message-attachment {
            margin-top: 12px;
        }

        .msg-img {
            max-width: 240px;
            border-radius: var(--border-radius-sm);
            margin-top: 10px;
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }

        .msg-img:hover {
            transform: scale(1.03);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .pdf-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 14px 18px;
            border-radius: var(--border-radius-sm);
            margin-top: 12px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .pdf-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .pdf-card:hover::before {
            left: 100%;
        }

        .message-row.bot .pdf-card {
            background: var(--gray-light);
            border: 1px solid var(--gray-light);
        }

        .pdf-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(4px);
        }

        .pdf-icon {
            font-size: 24px;
            color: var(--danger);
            animation: pdfPulse 2s infinite;
        }

        .pdf-info h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .pdf-info p {
            font-size: 12px;
            opacity: 0.8;
        }

        .gender-dropdown {
            margin-top: 12px;
            max-width: 300px;
        }

        .gender-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .gender-option {
            padding: 12px 16px;
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .gender-option:hover {
            background: var(--primary-light);
            color: white;
            border-color: var(--primary-light);
            transform: translateX(5px);
        }

        .gender-option.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .gender-icon {
            font-size: 16px;
        }

        .date-picker-container {
            margin-top: 12px;
            max-width: 300px;
        }

        .date-input-wrapper {
            position: relative;
        }

        .date-input {
            width: 100%;
            padding: 12px 16px;
            padding-right: 40px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            background: var(--light);
            color: var(--dark);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: var(--transition);
        }

        .date-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .calendar-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            cursor: pointer;
            font-size: 18px;
            z-index: 10;
        }

        /* Improved Calendar Popup */
        .calendar-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            backdrop-filter: blur(5px);
        }

        .calendar {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 20px;
            width: 90%;
            max-width: 320px;
            box-shadow: var(--box-shadow-lg);
            border: 1px solid var(--gray-light);
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 10px;
        }

        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .calendar-select {
            padding: 8px 12px;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            background: var(--light);
            color: var(--dark);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            min-width: 120px;
        }

        .calendar-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            color: var(--dark);
        }

        .nav-btn:hover {
            background: var(--primary);
            color: white;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 10px;
        }

        .weekday {
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: var(--gray);
            padding: 5px;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }

        .day {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            background: transparent;
            border: none;
            color: var(--dark);
        }

        .day:hover:not(.empty):not(.selected) {
            background: var(--gray-light);
        }

        .day.selected {
            background: var(--primary);
            color: white;
        }

        .day.today {
            background: var(--success);
            color: white;
        }

        .day.empty {
            cursor: default;
            background: transparent;
        }

        .calendar-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 10px;
        }

        .calendar-btn {
            flex: 1;
            padding: 10px;
            border-radius: var(--border-radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }

        .calendar-btn.today {
            background: var(--gray-light);
            color: var(--dark);
        }

        .calendar-btn.today:hover {
            background: var(--gray);
            color: white;
        }

        .calendar-btn.close {
            background: var(--light);
            border: 1px solid var(--gray-light);
            color: var(--dark);
        }

        .calendar-btn.close:hover {
            background: var(--gray-light);
        }

        .date-format-hint {
            font-size: 12px;
            color: var(--gray);
            margin-top: 8px;
            text-align: center;
        }


        .paynow-button-container {
            margin-top: 12px;
            max-width: 300px;
        }

        .paynow-button {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        .paynow-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .paynow-button:hover::before {
            left: 100%;
        }

        .paynow-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .paynow-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .input-section {
            padding: 20px 30px;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
            z-index: 5;
            position: relative;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 8px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .file-upload-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        .file-upload-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: var(--primary);
            animation: buttonSpin 1.5s linear infinite;
        }

        .file-upload-btn.active {
            background: var(--primary-light);
            color: white;
            cursor: pointer;
        }

        .file-upload-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .file-upload-btn:hover:not(.disabled) {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .input-field {
            flex: 1;
            border: none;
            background: transparent;
            padding: 14px 0;
            font-size: 16px;
            color: var(--dark);
            font-family: 'Inter', sans-serif;
            outline: none;
        }

        .input-field::placeholder {
            color: var(--gray);
            animation: placeholderPulse 2s infinite;
        }

        .send-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            position: relative;
            overflow: hidden;
        }

        .send-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .send-btn:hover::before {
            left: 100%;
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #previewTray {
            position: absolute;
            bottom: 100px;
            left: 30px;
            right: 30px;
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 16px 20px;
            display: none;
            align-items: center;
            gap: 16px;
            box-shadow: var(--box-shadow-lg);
            border: 1px solid var(--gray-light);
            z-index: 100;
            animation: slideUp 0.3s ease-out;
        }

        #previewImg {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius-sm);
            border: 2px solid var(--primary-light);
            animation: previewPulse 2s infinite;
        }

        .preview-info {
            flex: 1;
        }

        .preview-info h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .preview-info p {
            font-size: 12px;
            color: var(--gray);
        }

        .preview-close {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray);
        }

        .preview-close:hover {
            background: var(--danger);
            color: white;
            transform: rotate(90deg);
        }

        #lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(10px);
        }

        #lbContainer {
            width: 90%;
            height: 90%;
            position: relative;
        }

        #lbImg,
        #lbPdf {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: var(--border-radius);
            display: none;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 24px;
            color: white;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 2001;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        #confirmOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .confirm-card {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 32px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: var(--box-shadow-lg);
            border: 1px solid var(--gray-light);
            animation: modalSlide 0.3s ease-out;
        }

        .confirm-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(247, 37, 133, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--danger);
            font-size: 32px;
            animation: dangerPulse 1.5s infinite;
        }

        .confirm-card h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--dark);
        }

        .confirm-card p {
            color: var(--gray);
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .confirm-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .confirm-btn {
            padding: 12px 28px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }

        .confirm-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .confirm-btn:hover::before {
            left: 100%;
        }

        .confirm-btn.cancel {
            background: var(--gray-light);
            color: var(--dark);
        }

        .confirm-btn.cancel:hover {
            background: var(--gray);
            color: white;
        }

        .confirm-btn.danger {
            background: linear-gradient(135deg, #f72585, #ff4d9e);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
        }

        .confirm-btn.danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 37, 133, 0.4);
        }

        .completion-state {
            text-align: center;
            padding: 40px 20px;
            background: var(--light);
            border-radius: var(--border-radius);
            margin: 20px auto;
            max-width: 500px;
            box-shadow: var(--box-shadow);
            border: 2px solid var(--success);
            position: relative;
            overflow: hidden;
        }

        .completion-state::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--success), var(--primary), var(--success));
            background-size: 200% 100%;
            animation: successShimmer 2s linear infinite;
        }

        .completion-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(76, 201, 240, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--success);
            font-size: 36px;
            animation: bounce 1s infinite alternate, iconGlow 2s infinite;
        }

        .completion-state h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--success);
            background: linear-gradient(90deg, var(--success), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .completion-state p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        .order-id {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            background: var(--gray-light);
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            display: inline-block;
            margin: 10px 0;
            letter-spacing: 2px;
            animation: orderIdGlow 2s infinite;
            box-shadow: 0 0 20px rgba(67, 97, 238, 0.3);
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: var(--light);
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            max-width: 200px;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--gray-light);
            animation: fadeIn 0.3s ease-out;
        }

        .loading-dots {
            display: flex;
            gap: 4px;
        }

        .loading-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            animation: dotPulse 1.4s ease-in-out infinite;
        }

        .loading-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .loading-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        .typing-indicator span {
            font-size: 14px;
            color: var(--gray);
            font-weight: 500;
        }


        .summary-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 4000;
            backdrop-filter: blur(10px);
        }

        .summary-container {
            background: var(--light);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            box-shadow: var(--box-shadow-lg);
            border: 1px solid var(--gray-light);
            overflow: hidden;
            animation: modalSlide 0.3s ease-out;
        }

        .summary-header {
            padding: 24px 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .summary-header h3 {
            font-size: 24px;
            font-weight: 700;
        }

        .summary-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .summary-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .summary-content {
            padding: 30px;
            overflow-y: auto;
            max-height: 70vh;
        }

        .summary-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-light);
        }

        .summary-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .summary-section h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-section h4 i {
            font-size: 16px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .summary-label {
            font-size: 13px;
            color: var(--gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 16px;
            color: var(--dark);
            font-weight: 500;
            word-break: break-word;
            padding: 8px 12px;
            background: var(--gray-light);
            border-radius: var(--border-radius-sm);
            border-left: 3px solid var(--primary);
        }

        .summary-file {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--gray-light);
            border-radius: var(--border-radius-sm);
            margin-bottom: 15px;
            border-left: 3px solid var(--success);
        }

        .summary-file i {
            font-size: 24px;
            color: var(--danger);
        }

        .file-info {
            flex: 1;
        }

        .file-info h5 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--dark);
        }

        .file-info p {
            font-size: 12px;
            color: var(--gray);
        }

        .file-preview {
            flex-shrink: 0;
            text-align: center;
        }

        .file-preview img {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-preview img:hover {
            transform: scale(1.05);
            border-color: var(--primary);
        }

        .summary-footer {
            padding: 20px 30px;
            background: var(--gray-light);
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary-actions {
            display: flex;
            gap: 12px;
        }

        .summary-btn {
            padding: 12px 24px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-btn.close {
            background: var(--light);
            border: 1px solid var(--gray-light);
            color: var(--dark);
        }

        .summary-btn.close:hover {
            background: var(--gray-light);
        }

        .summary-btn.download {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .summary-btn.download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .summary-btn.download.hidden {
            display: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--gray-light);
        }

        .select-dropdown {
            margin-top: 12px;
            max-width: 300px;
        }

        .select-options {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .select-option {
            padding: 12px 16px;
            background: var(--light);
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .select-option:hover {
            background: var(--primary-light);
            color: white;
            border-color: var(--primary-light);
            transform: translateX(5px);
        }

        .select-option.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .select-icon {
            font-size: 16px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.1);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        @keyframes bounce {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-10px);
            }
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-5px) rotate(2deg);
            }
        }

        @keyframes progressShimmer {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes headerShimmer {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes avatarSpin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes buttonSpin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes countPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        @keyframes progressPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 201, 240, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(76, 201, 240, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(76, 201, 240, 0);
            }
        }

        @keyframes pdfPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        @keyframes previewPulse {

            0%,
            100% {
                border-color: var(--primary-light);
            }

            50% {
                border-color: var(--success);
            }
        }

        @keyframes placeholderPulse {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }
        }

        @keyframes dangerPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(247, 37, 133, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(247, 37, 133, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(247, 37, 133, 0);
            }
        }

        @keyframes successShimmer {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes iconGlow {

            0%,
            100% {
                filter: drop-shadow(0 0 5px var(--success));
            }

            50% {
                filter: drop-shadow(0 0 15px var(--success));
            }
        }

        @keyframes orderIdGlow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(67, 97, 238, 0.3);
            }

            50% {
                box-shadow: 0 0 30px rgba(67, 97, 238, 0.6);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes shimmerBorder {
            0% {
                transform: rotate(45deg) translateX(-100%);
            }

            100% {
                transform: rotate(45deg) translateX(100%);
            }
        }

        @keyframes dotPulse {

            0%,
            60%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            30% {
                transform: scale(1.2);
                opacity: 0.7;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 280px;
            }

            .message-content {
                max-width: 85%;
            }
        }

        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .logo {
                margin-bottom: 20px;
            }

            .progress-section {
                margin-bottom: 20px;
            }

            .chat-header {
                padding: 15px 20px;
            }

            .chat-container {
                padding: 20px;
            }

            .input-section {
                padding: 15px 20px;
            }

            #previewTray {
                left: 20px;
                right: 20px;
                bottom: 90px;
            }
        }
    </style>
</head>

<body id="body">
    <div class="app-container">

        <div class="sidebar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-passport"></i>
                </div>
                <div class="logo-text">
                    <h1>Ask Visa</h1>
                    <p>Intelligent Application Assistant</p>
                </div>
            </div>



            <div class="progress-section">
                <div class="step-info">
                    <span class="step-label" id="stepLabel">Country Selection</span>
                    <span class="step-count" id="stepCount">Step 1/8</span>
                </div>
                <div class="progress-container">
                    <div id="pBar" class="progress-bar"></div>
                </div>
                <div class="stats">
                    <span id="applicantCount">Applicants: 0</span>
                    <span id="progressPercent">0%</span>
                </div>
            </div>

            <button onclick="jumpToPaymentNow()" style="
    position: fixed;
    bottom: 10px;
    right: 10px;
    z-index: 9999;
    background: #f72585;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
"> Jump to Payment</button>

            <script>
                function jumpToPaymentNow() {
                    // Set session to payment step directly via AJAX
                    fetch('?ajax=1&debug_jump_to_payment=1')
                        .then(() => {
                            // Add payment message to chat
                            const botRow = document.createElement('div');
                            botRow.className = 'message-row bot';
                            botRow.innerHTML = `
                <div class="message-avatar">AI</div>
                <div class="message-content">
                    <div class="message-text"><b>Great! Total amount due: $100.</b> Click the button below to proceed with payment.</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                </div>
            `;

                            const payNowContainer = document.createElement('div');
                            payNowContainer.className = 'paynow-button-container';
                            payNowContainer.innerHTML = `
                <button class="paynow-button" onclick="processPaymentNow()">
                    <i class="fas fa-lock"></i>
                    Pay Now
                </button>
                <div style="text-align: center; margin-top: 8px; font-size: 12px; color: var(--gray);">
                    Secure payment • Total will be charged
                </div>
            `;

                            botRow.querySelector('.message-content').appendChild(payNowContainer);
                            document.getElementById('chat').appendChild(botRow);

                            // Update progress
                            document.getElementById('pBar').style.width = '98%';
                            document.getElementById('progressPercent').textContent = '98%';
                            document.getElementById('stepLabel').textContent = 'Payment';
                            document.getElementById('stepCount').textContent = 'Step 8/8';

                            console.log('✅ Jumped to payment step!');
                        });
                }
            </script>

            <div class="sidebar-actions">
                <button class="action-btn" onclick="toggleConfirm(true)">
                    <i class="fas fa-plus-circle"></i>
                    New Application
                </button>
                <button class="action-btn" onclick="window.location.href='edit.php'">
                    <i class="fas fa-edit"></i>
                    Edit Existing Order
                </button>
                <button class="action-btn danger" onclick="showSummary()">
                    <i class="fas fa-download"></i>
                    Download Summary
                </button>

                <div class="theme-toggle">
                    <div class="theme-label">
                        <i class="fas fa-moon"></i>
                        Dark Mode
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="themeToggle">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="chat-section">
            <div class="chat-header">
                <div class="chat-title">
                    <h2>Visa Application Assistant</h2>
                    <p>Your personal guide through the visa application process</p>
                </div>
                <div class="chat-status">
                    <div class="status-dot"></div>
                    <span>Assistant is online</span>
                </div>
            </div>

            <div class="chat-container" id="chat">
                <?php foreach ($_SESSION['messages'] as $m): ?>
                    <div class="message-row <?php echo $m['role']; ?>">
                        <?php if ($m['role'] === 'bot'): ?>
                            <div class="message-avatar">AI</div>
                        <?php endif; ?>
                        <div class="message-content">

                            <?php
                            // Check for button messages
                            $show_payment_button = false;
                            $show_status_button = false;
                            $message_text = $m['text'];

                            if (isset($m['text']) && strpos($m['text'], 'json_button:') === 0) {
                                $parts = explode(':', $m['text'], 3);
                                $buttonType = $parts[1] ?? '';
                                $message_text = $parts[2] ?? $m['text'];

                                if ($buttonType === 'payment') {
                                    $show_payment_button = true;
                                } elseif ($buttonType === 'status') {
                                    $show_status_button = true;
                                }
                            }
                            ?>

                            <div class="message-text"><?php echo formatBold($message_text); ?></div>

                            <?php if ($show_payment_button): ?>

                                <div class="paynow-button-container">
                                    <button class="paynow-button" onclick="processPaymentNow()">
                                        <i class="fas fa-lock"></i>
                                        Pay Now
                                    </button>
                                </div>
                            <?php elseif ($show_status_button): ?>
                                <div class="status-button-container" style="margin-top: 15px;">
                                    <button class="status-button" onclick="checkApplicationStatus()" style="
                        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                        color: white;
                        border: none;
                        padding: 15px 30px;
                        border-radius: 8px;
                        font-size: 16px;
                        font-weight: 600;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        width: 100%;
                        justify-content: center;
                        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
                    ">
                                        <i class="fas fa-check-circle"></i>
                                        Check Application Status
                                    </button>
                                    <div style="text-align: center; margin-top: 10px; color: #28a745; font-size: 14px;">
                                        <i class="fas fa-check"></i> Payment Confirmed
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($m['img']) && $m['img']): ?>
                                <div class="message-attachment">
                                    <?php if (isset($m['is_pdf']) && $m['is_pdf']): ?>
                                        <div class="pdf-card" onclick="openLightbox('<?php echo $m['img']; ?>', true)">
                                            <i class="fas fa-file-pdf pdf-icon"></i>
                                            <div class="pdf-info">
                                                <h4>Document.pdf</h4>
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
                <div id="completionState" style="display: none;">
                    <div class="completion-state">
                        <div class="completion-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Application Complete!</h3>
                        <p>Your visa application has been successfully submitted.</p>
                        <div class="order-id" id="finalOrderId">#0000</div>
                        <p>You will receive a confirmation email shortly.</p>
                    </div>
                </div>
            </div>

            <div id="previewTray">
                <img id="previewImg" src="">
                <div class="preview-info">
                    <h4 id="previewFileName">File Preview</h4>
                    <p id="previewFileSize">Ready to upload</p>
                </div>
                <div class="preview-close" onclick="clearPreview()">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <div class="input-section">
                <div class="input-wrapper">
                    <label id="attachBtn" class="file-upload-btn disabled">
                        <i class="fas fa-paperclip"></i>
                        <input type="file" id="fileInput" hidden accept="image/*,application/pdf" disabled onchange="handlePreview(this)">
                    </label>
                    <input type="text" id="msgInput" class="input-field" placeholder="Type your response here..." autocomplete="off">
                    <button id="sendBtn" class="send-btn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="typing-indicator" id="typingIndicator" style="display: none;">
        <div class="loading-dots">
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
            <div class="loading-dot"></div>
        </div>
        <span>Assistant is typing...</span>
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
        let currentQuestionId = null;

        // Check application status
        function checkApplicationStatus() {
            const orderId = "<?php echo $_SESSION['current_order_id'] ?? ''; ?>";

            if (!orderId) {
                alert('No order found. Please complete a payment first.');
                return;
            }

            // Show loading
            const botRow = document.createElement('div');
            botRow.className = 'message-row bot';
            botRow.innerHTML = `
        <div class="message-avatar">AI</div>
        <div class="message-content">
            <div class="message-text">Checking status for Order #${orderId}...</div>
            <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;
            chat.appendChild(botRow);

            // Fetch status from server
            fetch('check_status.php?order_id=' + orderId)
                .then(r => r.json())
                .then(data => {
                    const statusRow = document.createElement('div');
                    const visaStatus = data.visa_status || 'Processing';
                    statusRow.className = 'message-row bot';

                    let statusHtml = '';
                    if (data.success) {
                        statusHtml = `
                    <div class="message-text">
                        <h3>Application Status</h3>
                        <p><strong>Order ID:</strong> ${data.order_id}</p>
                        <p><strong>Payment Status:</strong> <span style="color: #28a745;">${data.payment_status}</span></p>
                        <p><strong>Visa Status:</strong> <span style="color: #28a745;">${data.visa_status}</span></p>
                        <p><strong>Applicants:</strong> ${data.applicants_count}</p>
                        <p><strong>Payment:</strong> Paid </p>
                        <p><strong>Submitted:</strong> ${data.submitted_date}</p>
                    </div>
                `;
                    } else {
                        statusHtml = `<div class="message-text">Error: ${data.message}</div>`;
                    }

                    statusRow.innerHTML = `
                <div class="message-avatar">AI</div>
                <div class="message-content">
                    ${statusHtml}
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                </div>
            `;

                    chat.appendChild(statusRow);
                    chat.scrollTop = chat.scrollHeight;
                })
                .catch(error => {
                    console.error('Status check error:', error);
                });

            chat.scrollTop = chat.scrollHeight;
        }



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
                    document.getElementById('previewImg').src = isPdf ?
                        "https://cdn-icons-png.flaticon.com/512/337/337946.png" :
                        e.target.result;

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

        // Create select dropdown
        function createSelectDropdown(questionId, options) {
            const selectDropdown = document.createElement('div');
            selectDropdown.className = 'select-dropdown';

            let optionsHtml = '<div class="select-options">';
            options.forEach(option => {
                const value = option.option_value;
                const label = option.option_label || option.option_value;
                optionsHtml += `
                <div class="select-option" onclick="selectOption('${questionId}', '${value}', '${label}')">
                    <span>${label}</span>
                    <i class="fas fa-check select-icon"></i>
                </div>
            `;
            });
            optionsHtml += '</div>';

            selectDropdown.innerHTML = optionsHtml;

            return selectDropdown;
        }

        // Create Pay Now button
        function createPayNowButton() {
            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'paynow-button-container';

            buttonContainer.innerHTML = `
            <button class="paynow-button" onclick="processPaymentNow()">
                <i class="fas fa-lock"></i>
                Pay Now
            </button>

            <div style="text-align: center; margin-top: 8px; font-size: 12px; color: var(--gray);">
                Secure payment • Total will be charged
            </div>
        `;

            return buttonContainer;
        }

        // Function to process payment
        function processPaymentNow() {
            const payNowBtn = document.querySelector('.paynow-button');
            if (!payNowBtn || payNowBtn.disabled)


                payNowBtn.disabled = true;
            payNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting...';

            // Get data
            const applicants = <?php echo isset($_SESSION['total_people']) ? $_SESSION['total_people'] : 1; ?>;
            const amount = applicants * 100;
            const orderId = "<?php echo isset($_SESSION['current_order_id']) ? $_SESSION['current_order_id'] : ('DEMO_' . time() . '_' . rand(1000, 9999)); ?>";
            const country = "<?php echo isset($_SESSION['country_name']) ? urlencode($_SESSION['country_name']) : ''; ?>";
            const email = "<?php echo isset($_SESSION['order_contact_email']) ? urlencode($_SESSION['order_contact_email']) : ''; ?>";
            const phone = "<?php echo isset($_SESSION['order_contact_phone']) ? urlencode($_SESSION['order_contact_phone']) : ''; ?>";

            // Show brief message in chat
            const botRow = document.createElement('div');
            botRow.className = 'message-row bot';
            botRow.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
            <div class="message-text">Redirecting to secure payment page...</div>
            <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;
            chat.appendChild(botRow);
            chat.scrollTop = chat.scrollHeight;

            // Build payment URL with all data
            let paymentUrl = `payment_palak.php?applicants=${applicants}&amount=${amount}`;
            if (orderId) paymentUrl += `&order_id=${orderId}`;
            if (country) paymentUrl += `&country=${country}`;
            if (email) paymentUrl += `&email=${email}`;
            if (phone) paymentUrl += `&phone=${phone}`;

            console.log('Redirecting to:', paymentUrl);

            // Redirect to payment
            setTimeout(() => {
                window.location.href = paymentUrl;
            }, 800);
        }


        // For debugging only
        async function getSessionStep() {
            try {
                const response = await fetch('?ajax=1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=get_step'
                });

                if (!response.ok) {
                    throw new Error('Network error');
                }

                return await response.json();
            } catch (error) {
                console.error('Error getting session step:', error);
                return {
                    error: error.message
                };
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
                <div class="date-format-hint">Format: DD-MM-YYYY</div>
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
                const parts = inputElement.value.split('-');
                if (parts.length === 3) {
                    currentCalendar.day = parseInt(parts[0]);
                    currentCalendar.month = parseInt(parts[1]) - 1;
                    currentCalendar.year = parseInt(parts[2]);
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

            const {
                year,
                month
            } = currentCalendar;
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
            const todayStr = `${today.getDate().toString().padStart(2, '0')}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getFullYear()}`;

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

                const dateStr = `${day.toString().padStart(2, '0')}-${(month + 1).toString().padStart(2, '0')}-${year}`;

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
            const dateStr = `${day.toString().padStart(2, '0')}-${month.toString().padStart(2, '0')}-${year}`;

            if (currentDateInput) {
                currentDateInput.value = dateStr;

                // Update calendar state
                currentCalendar.day = day;
                currentCalendar.month = month - 1;
                currentCalendar.year = year;

                const event = new Event('input', {
                    bubbles: true
                });
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
            if (file) formData.append('image', file);

            // Add select selection if available
            if (currentSelectSelection && currentQuestionId) {
                formData.append('select_value', currentSelectSelection);
            }

            // Add user message to UI immediately with file preview
            if (text || file) {
                const userRow = document.createElement('div');
                userRow.className = 'message-row user';

                let attachmentHtml = '';
                let messageText = text || '';

                // Format select message for display
                if (currentSelectSelection && text === currentSelectSelection) {
                    messageText = `Selected: ${text}`;
                }

                if (file) {
                    const isPdf = file.type === "application/pdf";
                    const fileName = file.name;
                    const fileSize = (file.size / 1024).toFixed(2) + ' KB';

                    // Create object URL for preview
                    const objectUrl = URL.createObjectURL(file);

                    if (isPdf) {
                        attachmentHtml = `
                        <div class="message-attachment">
                            <div class="pdf-card" onclick="openLightbox('${objectUrl}', true)">
                                <i class="fas fa-file-pdf pdf-icon"></i>
                                <div class="pdf-info">
                                    <h4>${fileName}</h4>
                                    <p>${fileSize} • PDF Document</p>
                                </div>
                            </div>
                        </div>
                    `;
                    } else {
                        attachmentHtml = `
                        <div class="message-attachment">
                            <img src="${objectUrl}" class="msg-img" onclick="openLightbox(this.src)">
                        </div>
                    `;
                    }

                    // If no text was entered, show "Uploaded file" as message
                    if (!text) {
                        messageText = isPdf ? "Uploaded PDF document" : "Uploaded image";
                    }
                }

                userRow.innerHTML = `
                <div class="message-content">
                    <div class="message-text">${messageText}</div>
                    ${attachmentHtml}
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                </div>
                <div class="message-avatar">
                    <i class="fas fa-user"></i>
                </div>
            `;
                chat.appendChild(userRow);
            }

            msgInput.value = '';
            clearPreview();
            currentSelectSelection = null;
            currentQuestionId = null;

            // Remove any existing select dropdown highlights
            document.querySelectorAll('.select-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Remove any date input
            const datePicker = document.querySelector('.date-picker-container');
            if (datePicker) {
                datePicker.remove();
            }

            chat.scrollTop = chat.scrollHeight;

            try {
                const response = await fetch('?ajax=1', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                // Hide typing indicator
                hideTypingIndicator();

                // Update progress display
                updateProgressDisplay(data);

                // Store order ID if available
                if (data.order_id) {
                    currentOrderId = data.order_id;
                }
                if (data.show_paynow_button || (data.text && data.text.startsWith('json_button:'))) {
                    console.log("🔄 DEBUG: Creating Pay Now button from init...");
                    console.log("📋 data.show_paynow_button:", data.show_paynow_button);
                    console.log("📋 data.text:", data.text);
                    console.log("📋 data.text starts with json_button?:", data.text && data.text.startsWith('json_button:'));

                    // Check for json_button 
                    if (data.text && data.text.startsWith('json_button:')) {
                        console.log("📋 Parsing json_button format...");
                        processJsonButtonResponse(data.text, data);
                        isProcessing = false;
                        msgInput.disabled = false;
                        sendBtn.disabled = false;
                        chat.scrollTop = chat.scrollHeight;
                        return;

                    }
                }



                // Check if we need to show select dropdown
                if (data.text && data.text.startsWith('json_select:') && data.show_select_dropdown) {
                    // Extract question ID and message
                    const parts = data.text.split(':');
                    const questionId = parts[1];
                    const actualMessage = parts.slice(2).join(':');

                    // Add bot response to UI
                    const botRow = document.createElement('div');
                    botRow.className = 'message-row bot';

                    const formattedText = formatBold(actualMessage);

                    botRow.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-text">${formattedText}</div>
                        <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                `


                    chat.appendChild(botRow);

                    // Add select dropdown with options
                    if (data.select_options && data.select_options.length > 0) {
                        const selectDropdown = createSelectDropdown(questionId, data.select_options);
                        botRow.querySelector('.message-content').appendChild(selectDropdown);

                        // Update input placeholder
                        msgInput.placeholder = "Select an option above";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;
                    }
                    return;
                }
                // Check to show Pay button
                else if (data.text && data.text.startsWith('json_button:') && data.show_paynow_button) {
                    // Extract payment type and message

                    const parts = data.text.split(':');
                    const paymentType = parts[1];
                    const actualMessage = parts.slice(2).join(':');


                    const botRow = document.createElement('div');
                    botRow.className = 'message-row bot';

                    const formattedText = formatBold(actualMessage);

                    botRow.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-text">${formattedText}</div>
                        <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                `;

                    chat.appendChild(botRow);
                    chat.scrollTop = chat.scrollHeight;
                    isProcessing = false;
                    msgInput.disabled = true;
                    sendBtn.disabled = true;
                    return;
                } else {
                    // for Regular bot response
                    const botRow = document.createElement('div');
                    botRow.className = 'message-row bot';

                    const formattedText = data.text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');

                    botRow.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-text">${formattedText}</div>
                        <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                `;

                    // Add calendar for date questions
                    if (data.show_date_calendar) {
                        const datePickerContainer = document.createElement('div');
                        datePickerContainer.className = 'date-picker-container';

                        datePickerContainer.innerHTML = `
                        <div class="date-input-wrapper">
                            <input type="text" 
                                   class="date-input" 
                                   placeholder="DD-MM-YYYY"
                                   id="dateInput"
                                   autocomplete="off">
                            <i class="fas fa-calendar-alt calendar-icon" onclick="showCalendar(document.getElementById('dateInput'))"></i>
                        </div>
                        <div class="date-format-hint">Click the calendar icon to pick a date</div>
                    `;

                        botRow.querySelector('.message-content').appendChild(datePickerContainer);

                        // Focus on date input
                        setTimeout(() => {
                            const dateInputEl = document.getElementById('dateInput');
                            if (dateInputEl) {
                                dateInputEl.focus();
                            }
                        }, 100);
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

                    // Handle finish state
                    if (data.is_finished) {
                        msgInput.placeholder = "Application Complete";
                        msgInput.disabled = true;
                        sendBtn.disabled = true;

                        // Extract order ID from response
                        const orderMatch = data.text.match(/Order ID:\s*\*\*(\d+)\*\*/);
                        if (orderMatch) {
                            currentOrderId = orderMatch[1];
                            finalOrderId.textContent = `#${currentOrderId}`;

                            // Show completion state
                            setTimeout(() => {
                                completionState.style.display = 'block';
                                chat.scrollTop = chat.scrollHeight;
                            }, 500);
                        }
                    }
                }

            } catch (error) {
                console.error("Error sending message:", error);

                // Hide typing indicator on error
                hideTypingIndicator();

                // Show error message
                const errorRow = document.createElement('div');
                errorRow.className = 'message-row bot';
                errorRow.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">Sorry, an error occurred. Please try again.</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
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

        // Process json_button response
        function processJsonButtonResponse(text, data) {
            console.log("🚀 PROCESSING JSON BUTTON:", text);

            const parts = text.split(':');
            if (parts.length < 3) {
                console.error("❌ Invalid json_button format");
                return;
            }

            const buttonType = parts[1]; // "payment" or "status"
            const actualMessage = parts.slice(2).join(':');

            console.log(`🎯 Type: ${buttonType}, Msg: ${actualMessage}`);

            // Create the message
            const botRow = document.createElement('div');
            botRow.className = 'message-row bot';

            const formattedText = actualMessage.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');

            botRow.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
            <div class="message-text">${formattedText}</div>
            <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        </div>
    `;

            // Add button based on type
            if (buttonType === 'payment') {
                const payNowContainer = createPayNowButton();
                botRow.querySelector('.message-content').appendChild(payNowContainer);
                msgInput.placeholder = "Click the Pay Now button above";
            } else if (buttonType === 'status') {
                const statusContainer = createStatusButton();
                botRow.querySelector('.message-content').appendChild(statusContainer);
                msgInput.placeholder = "Payment completed - check application status";
            }

            // Add to chat
            document.getElementById('chat').appendChild(botRow);

            // Update UI
            msgInput.disabled = true;
            sendBtn.disabled = true;

            console.log(`✅ ${buttonType} button created successfully`);

            // Scroll to bottom
            document.getElementById('chat').scrollTop = document.getElementById('chat').scrollHeight;
        }

        // function for status button
        function createStatusButton() {
            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'status-button-container';
            buttonContainer.style.marginTop = '15px';
            buttonContainer.style.maxWidth = '300px';



            // Add hover effect
            const statusButton = buttonContainer.querySelector('.status-button');
            statusButton.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 6px 20px rgba(40, 167, 69, 0.4)';
            });

            statusButton.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 15px rgba(40, 167, 69, 0.3)';
            });

            return buttonContainer;


            // Add to chat
            chat.appendChild(botRow);

        }


        // Format bold text function for JS
        function formatBold(text) {
            return text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
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
                                    <span class="summary-label">Total Amount</span>
                                    <span class="summary-value">$${data.order_info.total_amount || '0'} ${data.order_info.currency || 'USD'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Payment Status</span>
                                    <span class="summary-value" style="color: ${data.order_info.payment_status === 'paid' ? 'var(--success)' : 'var(--danger)'};">
                                        ${data.order_info.payment_status ? data.order_info.payment_status.toUpperCase() : 'N/A'}
                                    </span>
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
                                        <span class="summary-value">${data.payment_info.provider || 'Online'}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Amount Paid</span>
                                        <span class="summary-value">$${data.payment_info.amount || '0'} ${data.payment_info.currency || 'USD'}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Payment Status</span>
                                        <span class="summary-value" style="color: ${data.payment_info.status === 'success' ? 'var(--success)' : 'var(--danger)'};">
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
                // Show the download button again
                if (downloadPdfBtn) {
                    downloadPdfBtn.classList.remove('hidden');
                }
                return;
            }

            const country = data.country_name || 'Visa Application';
            const orderId = data.order_info ? data.order_info.id : '';
            const timestamp = new Date().toLocaleString();

            // Create a temporary div for PDF generation
            const pdfContent = document.createElement('div');
            pdfContent.id = 'pdfContent';
            pdfContent.style.padding = '20px';
            pdfContent.style.fontFamily = "'Inter', Arial, sans-serif";
            pdfContent.style.lineHeight = '1.5';
            pdfContent.style.color = '#333';
            pdfContent.style.width = '100%';
            pdfContent.style.maxWidth = '800px';
            pdfContent.style.margin = '0 auto';
            pdfContent.style.boxSizing = 'border-box';
            pdfContent.style.background = 'white';

            // Build HTML content
            let html = `
            <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4361ee; padding-bottom: 20px; page-break-after: avoid;">
                <h1 style="color: #4361ee; margin-bottom: 10px; font-size: 28px;">
                    <i class="fas fa-passport" style="margin-right: 10px;"></i>
                    ${country} Visa Application Summary
                </h1>
                ${orderId ? `<p style="font-size: 16px; color: #666; margin-bottom: 5px;"><strong>Order ID:</strong> ${orderId}</p>` : ''}
                <p style="font-size: 14px; color: #666;"><strong>Generated:</strong> ${timestamp}</p>
            </div>
        `;

            // Order Information
            if (data.order_info) {
                html += `
                <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; page-break-after: avoid;">
                    <h2 style="color: #4361ee; margin-bottom: 15px; font-size: 20px; border-left: 4px solid #4361ee; padding-left: 10px;">
                        Order Information
                    </h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div style="margin-bottom: 10px;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                Order ID
                            </div>
                            <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                ${data.order_info.id || 'N/A'}
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                Country
                            </div>
                            <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                ${data.country_name || 'N/A'}
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                Contact Email
                            </div>
                            <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                ${data.order_info.email || 'N/A'}
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                Contact Phone
                            </div>
                            <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                ${data.order_info.phone || 'N/A'}
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                Total Amount
                            </div>
                            <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                $${data.order_info.total_amount || '0'} ${data.order_info.currency || 'USD'}
                            </div>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                Payment Status
                            </div>
                            <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid ${data.order_info.payment_status === 'paid' ? '#4caf50' : '#f44336'}; font-weight: 500; color: ${data.order_info.payment_status === 'paid' ? '#4caf50' : '#f44336'};">
                                ${data.order_info.payment_status ? data.order_info.payment_status.toUpperCase() : 'N/A'}
                            </div>
                        </div>
                    </div>
                </div>
            `;

                // Payment Information
                if (data.payment_info) {
                    html += `
                    <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; page-break-after: avoid;">
                        <h2 style="color: #4361ee; margin-bottom: 15px; font-size: 20px; border-left: 4px solid #4361ee; padding-left: 10px;">
                            Payment Information
                        </h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Payment ID
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    ${data.payment_info.provider_payment_id || 'N/A'}
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Payment Provider
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    ${data.payment_info.provider || 'Online'}
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Amount Paid
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    $${data.payment_info.amount || '0'} ${data.payment_info.currency || 'USD'}
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Payment Status
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid ${data.payment_info.status === 'success' ? '#4caf50' : '#f44336'}; font-weight: 500; color: ${data.payment_info.status === 'success' ? '#4caf50' : '#f44336'};">
                                    ${data.payment_info.status ? data.payment_info.status.toUpperCase() : 'N/A'}
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Payment Date
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    ${data.payment_info.created_at ? new Date(data.payment_info.created_at).toLocaleString() : 'N/A'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                }
            }

            // Store file promises for later embedding
            const filePromises = [];
            const fileElements = [];

            // Applicants Information
            if (data.data) {
                const applicantKeys = Object.keys(data.data).filter(key => key.startsWith('applicant_'));

                applicantKeys.forEach((applicantKey, index) => {
                    const applicant = data.data[applicantKey];
                    const applicantNum = applicantKey.replace('applicant_', '');

                    // Separate text answers and file answers
                    const textAnswers = [];
                    const fileAnswers = [];

                    if (applicant.answers) {
                        Object.keys(applicant.answers).forEach(qId => {
                            const answer = applicant.answers[qId];
                            const label = data.question_labels ? (data.question_labels[qId] || `Question ${qId}`) : `Question ${qId}`;

                            if (answer.includes('fetch_file.php')) {
                                fileAnswers.push({
                                    qId,
                                    answer,
                                    label,
                                    isPdf: answer.includes('.pdf') || answer.toLowerCase().includes('pdf'),
                                    fileName: getFileNameFromPath(answer)
                                });
                            } else {
                                textAnswers.push({
                                    qId,
                                    answer,
                                    label
                                });
                            }
                        });
                    }

                    html += `
                    <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; page-break-before: always;">
                        <h2 style="color: #4361ee; margin-bottom: 20px; font-size: 22px; border-left: 4px solid #4361ee; padding-left: 10px;">
                            <i class="fas fa-user" style="margin-right: 10px;"></i>
                            Applicant #${applicantNum}
                        </h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Email
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    ${applicant.email || 'N/A'}
                                </div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    Phone
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    ${applicant.phone || 'N/A'}
                                </div>
                            </div>
                        </div>
                `;

                    // Display all text answers first
                    if (textAnswers.length > 0) {
                        html += `<h3 style="color: #666; margin: 20px 0 15px 0; font-size: 18px;">Application Information</h3>`;

                        textAnswers.forEach(item => {
                            html += `
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 13px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                                    ${item.label}
                                </div>
                                <div style="background: #f8f9fa; padding: 10px 12px; border-radius: 6px; border-left: 3px solid #4361ee; font-weight: 500;">
                                    ${item.answer}
                                </div>
                            </div>
                        `;
                        });
                    }

                    // Display all attached files
                    if (fileAnswers.length > 0) {
                        html += `<h3 style="color: #666; margin: 30px 0 15px 0; font-size: 18px;">Attached Files</h3>`;

                        fileAnswers.forEach((file, fileIndex) => {
                            // Extract the file URL
                            let fileUrl = file.answer;
                            if (fileUrl.startsWith('/fetch_file.php')) {
                                fileUrl = window.location.origin + fileUrl;
                            } else if (fileUrl.startsWith('fetch_file.php')) {
                                fileUrl = window.location.origin + '/' + fileUrl;
                            }

                            // Create a unique ID for this file
                            const fileId = `file_${applicantNum}_${file.qId}_${Date.now()}_${fileIndex}`;

                            html += `
                            <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 3px solid ${file.isPdf ? '#e53935' : '#4caf50'};">
                                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                    <div style="background: ${file.isPdf ? '#ffebee' : '#e8f5e8'}; padding: 8px; border-radius: 4px; margin-right: 10px;">
                                        <i class="fas ${file.isPdf ? 'fa-file-pdf' : 'fa-file-image'}" style="color: ${file.isPdf ? '#e53935' : '#4caf50'}; font-size: 20px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #333; margin-bottom: 2px;">${file.label}</div>
                                        <div style="font-size: 12px; color: #666;">${file.fileName} • ${file.isPdf ? 'PDF Document' : 'Image File'}</div>
                                    </div>
                                </div>
                        `;

                            if (!file.isPdf) {
                                // For images, add placeholder that will be replaced with actual image
                                html += `
                                <div id="${fileId}_placeholder" style="text-align: center; padding: 20px; background: white; border-radius: 4px; border: 1px solid #ddd; margin-top: 10px;">
                                    <i class="fas fa-image" style="font-size: 36px; color: #4caf50; margin-bottom: 10px;"></i>
                                    <p style="color: #666; margin: 0;">Loading image...</p>
                                </div>
                            `;

                                // Store promise to load the image
                                filePromises.push(
                                    fetch(fileUrl)
                                    .then(response => {
                                        if (!response.ok) throw new Error('Failed to fetch image');
                                        return response.blob();
                                    })
                                    .then(blob => {
                                        return new Promise((resolve) => {
                                            const reader = new FileReader();
                                            reader.onloadend = function() {
                                                fileElements.push({
                                                    id: fileId,
                                                    type: 'image',
                                                    data: reader.result,
                                                    fileName: file.fileName,
                                                    label: file.label
                                                });
                                                resolve();
                                            };
                                            reader.readAsDataURL(blob);
                                        });
                                    })
                                    .catch(error => {
                                        console.error('Error loading image:', error);
                                        fileElements.push({
                                            id: fileId,
                                            type: 'image',
                                            data: null,
                                            fileName: file.fileName,
                                            label: file.label,
                                            error: true
                                        });
                                    })
                                );
                            } else {
                                // For PDFs, create a visual representation
                                html += `
                                <div style="text-align: center; padding: 20px; background: white; border-radius: 4px; border: 1px solid #ddd; margin-top: 10px;">
                                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #e53935; margin-bottom: 15px;"></i>
                                    <p style="color: #333; font-weight: 600; margin-bottom: 5px;">${file.fileName}</p>
                                    <p style="color: #666; font-size: 14px; margin: 0;">PDF Document - ${file.label}</p>
                                    <div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                                        <p style="color: #666; font-size: 12px; margin: 0;">
                                            <i class="fas fa-info-circle" style="margin-right: 5px;"></i>
                                            This PDF document is stored in the application system.
                                        </p>
                                    </div>
                                </div>
                            `;
                            }

                            html += `</div>`;
                        });
                    }

                    html += `</div>`;
                });
            }

            // Final footer
            html += `
            <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center; color: #666; font-size: 12px; page-break-before: avoid;">
                <p>This document was generated by Ask Visa Portal on ${timestamp}</p>
                <p>All application information is included in this document. Images are embedded, PDF documents are referenced.</p>
                <p>© ${new Date().getFullYear()} Ask Visa. All rights reserved.</p>
            </div>
        `;

            // Show loading message
            const originalContent = document.getElementById('summaryContent').innerHTML;
            document.getElementById('summaryContent').innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <div class="loading-dots" style="justify-content: center; margin-bottom: 20px;">
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                </div>
                <h3 style="color: #4361ee; margin-bottom: 10px;">Preparing PDF with all files...</h3>
                <p style="color: #666;">This may take a moment. We're loading all uploaded images and documents.</p>
                <p style="color: #666; font-size: 12px; margin-top: 20px;">
                    Including ${filePromises.length} uploaded file(s)
                </p>
            </div>
        `;

            try {
                // Wait for all image files to load
                await Promise.all(filePromises);

                // Now set the HTML content
                pdfContent.innerHTML = html;
                document.body.appendChild(pdfContent);

                // Replace placeholders with actual image data
                fileElements.forEach(file => {
                    if (file.type === 'image' && file.data && !file.error) {
                        const placeholder = document.getElementById(`${file.id}_placeholder`);
                        if (placeholder) {
                            const imgElement = document.createElement('img');
                            imgElement.src = file.data;
                            imgElement.alt = file.label;
                            imgElement.style.maxWidth = '100%';
                            imgElement.style.height = 'auto';
                            imgElement.style.display = 'block';
                            imgElement.style.margin = '10px auto 0 auto';
                            imgElement.style.border = '1px solid #ddd';
                            imgElement.style.borderRadius = '4px';
                            imgElement.style.maxHeight = '400px';
                            imgElement.style.objectFit = 'contain';

                            placeholder.parentNode.replaceChild(imgElement, placeholder);
                        }
                    }
                });

                // Generate PDF
                const options = {
                    margin: [15, 15, 15, 15],
                    filename: `Visa_Application_${orderId || 'Summary'}_${new Date().getTime()}.pdf`,
                    image: {
                        type: 'jpeg',
                        quality: 0.90
                    },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        letterRendering: true,
                        logging: false,
                        allowTaint: false,
                        onclone: function(clonedDoc) {
                            // Ensure images are loaded in the cloned document
                            fileElements.forEach(file => {
                                if (file.type === 'image' && file.data && !file.error) {
                                    const img = clonedDoc.getElementById(`${file.id}_placeholder`);
                                    if (img) {
                                        const newImg = clonedDoc.createElement('img');
                                        newImg.src = file.data;
                                        newImg.alt = file.label;
                                        newImg.style.maxWidth = '100%';
                                        newImg.style.height = 'auto';
                                        newImg.style.display = 'block';
                                        newImg.style.margin = '10px auto 0 auto';
                                        newImg.style.border = '1px solid #ddd';
                                        newImg.style.borderRadius = '4px';
                                        newImg.style.maxHeight = '400px';
                                        newImg.style.objectFit = 'contain';
                                        img.parentNode.replaceChild(newImg, img);
                                    }
                                }
                            });
                        }
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait'
                    },
                    pagebreak: {
                        mode: ['avoid-all', 'css', 'legacy'],
                        before: '.page-break-before',
                        after: '.page-break-after',
                        avoid: '.page-break-avoid'
                    }
                };

                await html2pdf().set(options).from(pdfContent).save();

            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Error generating PDF. Please try again. ' + error.message);
            } finally {
                // Restore original content
                document.getElementById('summaryContent').innerHTML = originalContent;

                // Show the download button again
                if (downloadPdfBtn) {
                    downloadPdfBtn.classList.remove('hidden');
                }

                // Clean up
                if (pdfContent.parentNode) {
                    pdfContent.parentNode.removeChild(pdfContent);
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

        // Theme Toggle Handler
        themeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        // Initialize
        window.addEventListener('load', () => {
            // Restore Theme
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark');
                themeToggle.checked = true;
            }

            chat.scrollTop = chat.scrollHeight;

            // Initial Fetch for Progress and Upload status
            fetch('?ajax=1', {
                    method: 'POST',
                    body: new FormData()
                })
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
                });

            msgInput.focus();
        });
    </script>
</body>

</html>