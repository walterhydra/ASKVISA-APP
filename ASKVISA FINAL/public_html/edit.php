<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$log_file = __DIR__ . '/palak_errors.log';
ini_set('error_log', $log_file);

error_log("========== EDIT PAGE ACCESSED ==========");
error_log("Session ID: " . session_id());



// Security check
if (!isset($_SESSION['edit_verified']) || $_SESSION['edit_verified'] !== true) {
    error_log("SECURITY FAIL: edit_verified not set or not true");
    session_destroy();
    header("Location: edit_access.php?error=access_denied");
    exit;
}

$order_id = $_SESSION['order_id'] ?? '';
$applicant_id = $_SESSION['applicant_id'] ?? '';

error_log("Using Order ID from session: " . $order_id);
error_log("Using Applicant ID from session: " . $applicant_id);

if (empty($order_id)) {
    error_log("ERROR: Order ID is empty in session");
    if (!empty($_SESSION['current_order_id'])) {
        $order_id = $_SESSION['current_order_id'];
        error_log("Using current_order_id as fallback: " . $order_id);
    } else {
        die("Error: Order ID not found in session. <a href='edit_access.php'>Go back</a>");
    }
}

if (empty($applicant_id)) {
    error_log("ERROR: Applicant ID is empty in session");
    die("Error: Applicant ID not found in session. <a href='select_applicant.php'>Select an applicant</a>");
}

// Connect to database
require 'db.php';

// ========== FETCH ORDER DETAILS ==========
$order = null;
try {
    $stmt = $pdo->prepare("
        SELECT vo.*, c.country_name, c.id as country_id, visa_type, processing_time
        FROM visa_orders vo 
        LEFT JOIN countries c ON vo.country_id = c.id 
        WHERE vo.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Error: Order #$order_id not found.");
    }
} catch (Exception $e) {
    die("Error loading order: " . $e->getMessage());
}

// ========== FETCH APPLICANT DETAILS ==========
$applicant = null;
try {
    $stmt = $pdo->prepare("
        SELECT * FROM applicants 
        WHERE id = ? AND order_id = ?
    ");
    $stmt->execute([$applicant_id, $order_id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        die("Error: Applicant not found.");
    }
} catch (Exception $e) {
    die("Error loading applicant: " . $e->getMessage());
}

// ========== FETCH ALL QUESTIONS FOR THIS COUNTRY ==========
$country_id = $order['country_id'];
$country_questions = [];

try {
    $stmt = $pdo->prepare("
        SELECT q.id, q.label, q.field_key, q.field_type, q.is_required, q.validation_rules
        FROM country_questions q
        LEFT JOIN applicant_answers aa ON q.id = aa.question_id AND aa.applicant_id = ?
        LEFT JOIN applicant_files af ON q.id = af.question_id AND af.applicant_id = ?
        WHERE q.country_id = ? AND (q.is_active = 1 OR aa.id IS NOT NULL OR af.id IS NOT NULL)
        GROUP BY q.id
        ORDER BY q.sort_order ASC, q.id ASC
    ");
    $stmt->execute([$applicant_id, $applicant_id, $country_id]);
    $country_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($country_questions) . " questions for country_id: " . $country_id);
} catch (Exception $e) {
    error_log("Error fetching country questions: " . $e->getMessage());
}

// ========== FETCH APPLICANT'S ANSWERS ==========
$answers_by_question = [];

try {
    $stmt = $pdo->prepare("
        SELECT question_id, answer_text, answer_type
        FROM applicant_answers 
        WHERE applicant_id = ? AND order_id = ?
    ");
    $stmt->execute([$applicant_id, $order_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $answers_by_question[$row['question_id']] = $row['answer_text'];
    }
} catch (Exception $e) {
    error_log("Error fetching answers: " . $e->getMessage());
}


// ========== FETCH APPLICANT FILES ==========
$applicant_files = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, file_path, file_type, question_id, uploaded_at
        FROM applicant_files 
        WHERE applicant_id = ?
        ORDER BY question_id, uploaded_at DESC
    ");
    $stmt->execute([$applicant_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Only keep the most recent file per question
        if (!isset($applicant_files[$row['question_id']])) {
            $applicant_files[$row['question_id']] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching applicant files: " . $e->getMessage());
}

// ========== GET ALL APPLICANTS FOR SWITCHER ==========
$all_applicants = [];
if (isset($_SESSION['all_applicants'])) {
    $all_applicants = $_SESSION['all_applicants'];
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT id AS applicant_id, applicant_no, applicant_email 
            FROM applicants 
            WHERE order_id = ? 
            ORDER BY applicant_no
        ");
        $stmt->execute([$order_id]);
        $all_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all applicants: " . $e->getMessage());
    }
}

// ========== FUNCTION TO GET APPLICANT FULL NAME ==========
function getApplicantFullName($pdo, $applicant_id, $applicant_no)
{
    try {
        $stmt = $pdo->prepare("
            SELECT answer_text 
            FROM applicant_answers 
            WHERE applicant_id = ? AND question_id = 1
            LIMIT 1
        ");
        $stmt->execute([$applicant_id]);
        $first = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT answer_text 
            FROM applicant_answers 
            WHERE applicant_id = ? AND question_id = 2
            LIMIT 1
        ");
        $stmt->execute([$applicant_id]);
        $last = $stmt->fetchColumn();

        if ($first && $last) return htmlspecialchars($first . ' ' . $last);
        if ($first) return htmlspecialchars($first);
        return "Applicant #" . $applicant_no;
    } catch (Exception $e) {
        return "Applicant #" . $applicant_no;
    }
}

// ========== HELPER FUNCTION TO RENDER INPUT ==========
function renderQuestionInput($question, $answers_by_question)
{
    $q_id = $question['id'];
    $field_type = $question['field_type'];
    $label = $question['label'];
    $field_key = $question['field_key'];
    $is_required = $question['is_required'] ?? 1;
    $value = $answers_by_question[$q_id] ?? '';

    $html = '<div class="form-group">';
    $html .= '<label for="q_' . $q_id . '"' . ($is_required ? ' class="required"' : '') . '>' . htmlspecialchars($label) . '</label>';

    switch ($field_type) {
        case 'select':
            $html .= '<select id="q_' . $q_id . '" name="q_' . $q_id . '" ' . ($is_required ? 'required' : '') . '>';
            $html .= '<option value="">Select ' . htmlspecialchars($label) . '</option>';

            // Handle gender specially (you can move this to question_options table later)
            if ($field_key === 'gender') {
                $options = [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                    ['value' => 'other', 'label' => 'Other']
                ];
                foreach ($options as $opt) {
                    $selected = ($value == $opt['value']) ? 'selected' : '';
                    $html .= '<option value="' . $opt['value'] . '" ' . $selected . '>' . $opt['label'] . '</option>';
                }
            }
            $html .= '</select>';
            break;

        case 'date':
            // Convert DD-MM-YYYY to YYYY-MM-DD for input
            if ($value && preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $matches)) {
                $value = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            $html .= '<input type="date" id="q_' . $q_id . '" name="q_' . $q_id . '" value="' . htmlspecialchars($value) . '" ' . ($is_required ? 'required' : '') . '>';
            break;

        case 'file':
            return ''; // Skip files in regular tabs

        default:
            $input_type = 'text';
            if ($field_key === 'email') $input_type = 'email';
            if ($field_key === 'phone') $input_type = 'tel';
            if ($field_key === 'passport_number') $input_type = 'text';

            $html .= '<input type="' . $input_type . '" id="q_' . $q_id . '" name="q_' . $q_id . '" value="' . htmlspecialchars($value) . '" ' . ($is_required ? 'required' : '') . ' placeholder="Enter ' . htmlspecialchars($label) . '">';
    }

    $html .= '</div>';
    return $html;
}

// ========== GROUP QUESTIONS BY CATEGORY ==========
$personal_q = [];
$passport_q = [];
$travel_q = [];
$document_q = [];

foreach ($country_questions as $q) {
    $field_key = $q['field_key'];
    $field_type = $q['field_type'];

    if ($field_type === 'file') {
        $document_q[] = $q;
    } elseif (
        strpos($field_key, 'first_name') !== false ||
        strpos($field_key, 'last_name') !== false ||
        strpos($field_key, 'gender') !== false ||
        strpos($field_key, 'date_of_birth') !== false ||
        strpos($field_key, 'place_of_birth') !== false
    ) {
        $personal_q[] = $q;
    } elseif (strpos($field_key, 'passport') !== false) {
        $passport_q[] = $q;
    } elseif (
        strpos($field_key, 'arrival') !== false ||
        strpos($field_key, 'flight') !== false ||
        strpos($field_key, 'hotel') !== false
    ) {
        $travel_q[] = $q;
    } else {
        $personal_q[] = $q; // Default to personal
    }
}

// ========== HANDLE FORM SUBMISSION ==========
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['new_document'])) {
    try {
        $pdo->beginTransaction();
        $updates_made = false;

        // Update all questions
        foreach ($country_questions as $question) {
            if ($question['field_type'] === 'file') continue;

            $q_id = $question['id'];
            $post_key = 'q_' . $q_id;

            if (isset($_POST[$post_key])) {
                $value = trim($_POST[$post_key]);

                // Check required
                if ($question['is_required'] && empty($value)) {
                    throw new Exception($question['label'] . " is required");
                }

                if (empty($value)) continue; // Skip empty non-required

                // Check if answer exists
                $check = $pdo->prepare("SELECT id FROM applicant_answers WHERE applicant_id = ? AND question_id = ?");
                $check->execute([$applicant_id, $q_id]);

                if ($check->fetch()) {
                    $stmt = $pdo->prepare("UPDATE applicant_answers SET answer_text = ? WHERE applicant_id = ? AND question_id = ?");
                    $stmt->execute([$value, $applicant_id, $q_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO applicant_answers (order_id, applicant_id, question_id, answer_type, answer_text) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$order_id, $applicant_id, $q_id, $question['field_type'], $value]);
                }
                $updates_made = true;
            }
        }

        // Update contact info for primary applicant
        if ($applicant['applicant_no'] == 1 && isset($_POST['applicant_email'])) {
            $stmt = $pdo->prepare("UPDATE applicants SET applicant_email = ?, applicant_phone = ? WHERE id = ?");
            $stmt->execute([$_POST['applicant_email'], $_POST['applicant_phone'], $applicant_id]);
            $updates_made = true;
        }

        if ($updates_made) {
            $pdo->commit();
            $success_msg = "Application updated successfully!";

            // Refresh answers
            $stmt = $pdo->prepare("SELECT question_id, answer_text FROM applicant_answers WHERE applicant_id = ?");
            $stmt->execute([$applicant_id]);
            $answers_by_question = [];
            while ($row = $stmt->fetch()) {
                $answers_by_question[$row['question_id']] = $row['answer_text'];
            }
        } else {
            $pdo->rollBack();
            $error_msg = "No changes were made.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Update failed: " . $e->getMessage();
    }
}

// ========== HANDLE DOCUMENT UPLOADS (who knew a nested form would make you kill yourself so only use the main form to handle uploads) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['has_document_uploads'])) {

    // Loop through all document questions
    foreach ($document_q as $question) {
        $q_id = $question['id'];
        $file_input_name = 'document_' . $q_id;


        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$file_input_name];

            // Validate file
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
           

            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {

                // Use order creation date for folder
                $order_date = strtotime($order['created_at']);
                $year = date('Y', $order_date);
                $month = date('m', $order_date);
                $day = date('d', $order_date);

                $order_folder = "Order_{$order_id}";
                $applicant_folder = "applicant_{$applicant['applicant_no']}";

                $relative_path = "{$year}/{$month}/{$day}/{$order_folder}/{$applicant_folder}/";
                $upload_dir = dirname($_SERVER['DOCUMENT_ROOT']) . "/gov_id/" . $relative_path;

                // Create directory if needed
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $first_name = '';
                    foreach ($country_questions as $q) {
                        if ($q['field_key'] === 'first_name') {
                            $first_name_qid = $q['id'];
                            if (isset($answers_by_question[$first_name_qid])) {
                                $first_name = $answers_by_question[$first_name_qid];
                                $first_name = preg_replace('/[^a-zA-Z0-9]/', '', $first_name);
                            }
                            break;
                        }
                    }
                    
                    // If no first name found, use applicant number
                    if (empty($first_name)) {
                        $first_name = 'applicant_' . $applicant['applicant_no'];
                    }
                $document_type = ($question['field_key'] == 'passport_front') ? 'front' : 'back';

                $filename = $first_name . '_passport' .$document_type .'.'. $extension;
                $full_path = $upload_dir . $filename;

                // Save file
                if (move_uploaded_file($file['tmp_name'], $full_path)) {

                    // Delete old file if exists
                    if (isset($applicant_files[$q_id])) {
                        $old_path = $applicant_files[$q_id]['file_path'];
                        $old_full = dirname($_SERVER['DOCUMENT_ROOT']) . "/gov_id/" . $old_path;
                        if (file_exists($old_full)) {
                            unlink($old_full);
                        }

                        // Delete old record
                        $del = $pdo->prepare("DELETE FROM applicant_files WHERE id = ?");
                        $del->execute([$applicant_files[$q_id]['id']]);
                    }

                    // Insert new record
                    $db_path = $relative_path . $filename;
                    $stmt = $pdo->prepare("INSERT INTO applicant_files (order_id, applicant_id, question_id, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$order_id, $applicant_id, $q_id, $db_path, $file['type']]);
                    
                    // ALSO UPDATE PATH IN applicant_answers for questions 4(front) and 5 (back)
                    
                    $target_qid = null;
                    foreach ($country_questions as $q) {
                        if ($q['field_key'] === $question['field_key']) {
                            $target_qid = $q['id'];
                            break;
                        }
                    }
                    
                    if ($target_qid) {
                        $check = $pdo->prepare("SELECT id FROM applicant_answers WHERE applicant_id = ? AND question_id = ?");
                        $check->execute([$applicant_id, $target_qid]);
                        
                        if ($check->fetch()) {
                            // UPDATE existing record
                            $stmt = $pdo->prepare("
                                UPDATE applicant_answers 
                                SET answer_text = ?, answer_type = 'file', created_at = NOW()
                                WHERE applicant_id = ? AND question_id = ?
                            ");
                            $stmt->execute([$db_path, $applicant_id, $target_qid]);
                        } else {
                            // INSERT new record
                            $stmt = $pdo->prepare("
                                INSERT INTO applicant_answers 
                                (order_id, applicant_id, question_id, answer_type, answer_text, created_at) 
                                VALUES (?, ?, ?, 'file', ?, NOW())
                            ");
                            $stmt->execute([$order_id, $applicant_id, $target_qid, $db_path]);
                        }
                    }

                    $upload_success = true;
                }
            }
        }
    }

    // Redirect to show success
    if (isset($upload_success)) {
        header("Location: edit.php?upload_success=1&t=" . time());
        exit;
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Application #<?php echo htmlspecialchars($order_id); ?></title>
  <style>
        :root {
            /* Your exact grayscale palette */
            --alabaster-grey: #D9DADF;
            --white: #FFFFFF;
            --white-2: #FEFEFE;
            --platinum: #F6F7F9;
            --rosy-granite: #959599;
            --dim-grey: #656464;
            
            /* Soft red accent */
            --soft-red: #C42E2E;
            --soft-red-light: #D65A5A;
            --soft-red-dark: #9E2222;
            
            /* Semantic mappings */
            --primary: var(--soft-red);
            --primary-light: var(--soft-red-light);
            --primary-dark: var(--soft-red-dark);
            --secondary: var(--rosy-granite);
            --danger: var(--soft-red);
            --dark: var(--dim-grey);
            --light: var(--platinum);
            --gray: var(--rosy-granite);
            --light-gray: var(--alabaster-grey);
            
            /* Structural variables */
            --border-radius: 16px;
            --shadow: 0 10px 30px rgba(196, 46, 46, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: var(--platinum);
            min-height: 100vh;
            padding: 20px;
            color: var(--dim-grey);
        }

        .edit-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: var(--white);
            color: var(--dim-grey);
            padding: 30px 40px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--alabaster-grey);
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--dim-grey);
        }

        .header p {
            opacity: 0.8;
            font-size: 16px;
            color: var(--rosy-granite);
        }

        .content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        @media (max-width: 992px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        .sidebar {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 30px;
            border: 1px solid var(--alabaster-grey);
        }

        .main-content {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--alabaster-grey);
        }

        .alert {
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .alert-success {
            background: #F0F7F0;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }

        .alert-error {
            background: #FFF0F0;
            color: var(--soft-red-dark);
            border: 1px solid var(--soft-red-light);
            border-left: 4px solid var(--soft-red);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--alabaster-grey);
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dim-grey);
        }

        .info-card {
            background: var(--platinum);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--alabaster-grey);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--alabaster-grey);
        }

        .info-label {
            color: var(--rosy-granite);
            font-weight: 500;
            font-size: 14px;
        }

        .info-value {
            font-weight: 600;
            color: var(--dim-grey);
            text-align: right;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 15px;
            color: var(--dim-grey);
        }

        label.required:after {
            content: ' *';
            color: var(--soft-red);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--alabaster-grey);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: var(--white);
            color: var(--dim-grey);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--soft-red);
            box-shadow: 0 0 0 3px rgba(196, 46, 46, 0.08);
            background: var(--white);
        }

        .field-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--soft-red);
            color: white;
        }

        .btn-primary:hover {
            background: var(--soft-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196, 46, 46, 0.15);
        }

        .btn-secondary {
            background: var(--platinum);
            color: var(--dim-grey);
            border: 1px solid var(--alabaster-grey);
        }

        .btn-secondary:hover {
            background: var(--alabaster-grey);
        }

        .btn-outline {
            background: white;
            color: var(--soft-red);
            border: 1px solid var(--soft-red);
        }

        .btn-outline:hover {
            background: #FFF0F0;
        }

        .tab-navigation {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--alabaster-grey);
            padding-bottom: 10px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 20px;
            background: var(--platinum);
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--rosy-granite);
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn:hover {
            background: var(--alabaster-grey);
            color: var(--dim-grey);
        }

        .tab-btn.active {
            background: var(--soft-red);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Applicant switcher styles */
        .applicant-switcher a {
            transition: all 0.3s;
        }

        .applicant-switcher a:hover {
            background: #FFF0F0 !important;
            border-color: var(--soft-red) !important;
        }

        /* File input styles */
        input[type="file"] {
            padding: 10px;
            border: 1px dashed var(--alabaster-grey);
            background: var(--platinum);
            border-radius: 8px;
            width: 100%;
            color: var(--dim-grey);
        }

        input[type="file"]:hover {
            border-color: var(--soft-red);
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-paid {
            background: #F0F7F0;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }

        .status-pending {
            background: #FFF0F0;
            color: var(--soft-red-dark);
            border: 1px solid var(--soft-red-light);
        }

        /* Icons */
        .fas, .far {
             color: var(--dim-grey);
        }
        
        .tab-btn.active{
            color : var(--white);
        }
        
        .tab-btn.active i,
        .tab-btn.active .fas,
        .tab-btn.active .far {
             color: white;
        }

        /* Headers */
        h1, h2, h3, h4, h5, h6 {
            color: var(--dim-grey);
        }

        /* Links */
        a {
            color: var(--soft-red);
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: var(--soft-red-dark);
        }

        /* Cards */
        .applicant-card {
            background: var(--white);
            border: 1px solid var(--alabaster-grey);
            border-radius: var(--border-radius);
            transition: all 0.3s;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .applicant-card:hover {
            border-color: var(--soft-red);
            box-shadow: 0 10px 25px rgba(196, 46, 46, 0.05);
        }

        /* Applicant number badge */
        .applicant-number {
            position: absolute;
            top: 0;
            left: 0;
            background: var(--soft-red);
            color: white;
            padding: 8px 15px;
            border-radius: 0 0 var(--border-radius) 0;
            font-weight: 600;
            font-size: 14px;
        }

        .applicant-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dim-grey);
            padding-top: 10px;
        }

        /* Select dropdown */
        select {
            background: var(--white);
            color: var(--dim-grey);
            appearance: none;
            padding-right: 30px;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23C42E2E' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }

        /* Placeholder text */
        ::placeholder {
            color: var(--alabaster-grey);
            opacity: 1;
        }

        /* Focus states */
        *:focus {
            outline: none;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--rosy-granite);
        }

        .empty-state i {
            font-size: 64px;
            color: var(--alabaster-grey);
            margin-bottom: 20px;
        }

        /* Back link */
        .back-link {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--alabaster-grey);
        }

        .back-link a {
            color: var(--rosy-granite);
            font-weight: 500;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: var(--soft-red);
        }

        /* Applicant details */
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .detail-label {
            color: var(--rosy-granite);
        }

        .detail-value {
            color: var(--dim-grey);
            font-weight: 500;
            text-align: right;
        }

        .applicant-email {
            color: var(--soft-red);
            font-weight: 500;
        }

        .applicant-phone {
            color: var(--dim-grey);
            font-weight: 500;
        }

        /* Select button */
        .select-btn {
            width: 100%;
            padding: 14px;
            background: var(--soft-red);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .select-btn:hover {
            background: var(--soft-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(196, 46, 46, 0.1);
        }
    </style>
</head>

<body>
    <div class="edit-container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Application</h1>
            <p>Update your application information</p>
        </div>

        <div class="content">
            <!-- Sidebar -->
            <div class="sidebar">
                <h2 class="section-title"><i class="fas fa-file-alt"></i> Application Summary</h2>
                <div class="info-card">
                    <div class="info-row"><span class="info-label">Order ID</span><span class="info-value">#<?php echo $order_id; ?></span></div>
                    <div class="info-row"><span class="info-label">Applicant</span><span class="info-value">#<?php echo $applicant['applicant_no']; ?></span></div>
                    <div class="info-row"><span class="info-label">Country</span><span class="info-value"><?php echo htmlspecialchars($order['country_name'] ?? 'N/A'); ?></span></div>
                    <div class="info-row"><span class="info-label">Visa Type</span><span class="info-value"><?php echo $order['visa_type']; ?></span></div>
                    <div class="info-row"><span class="info-label">Processing Time</span><span class="info-value"><?php echo $order['processing_time']; ?></span></div>
                    <div class="info-row"><span class="info-label">Visa Status</span><span class="info-value"><?php echo $order['visa_status']; ?></span></div>
                    <div class="info-row"><span class="info-label">Payment Status</span><span class="info-value"><?php echo $order['payment_status']; ?></span></div>
                </div>

                <!-- Applicant Switcher -->
                <?php if (count($all_applicants) > 1): ?>
                    <div style="margin-top: 30px;">
                        <h3 style="font-size: 16px; margin-bottom: 15px;"><i class="fas fa-exchange-alt"></i> Switch Applicant</h3>
                        <div style="background: #f8fafc; border-radius: 8px; padding: 10px;">
                            <?php foreach ($all_applicants as $app):
                                $is_current = $app['applicant_id'] == $applicant_id;
                                $app_name = getApplicantFullName($pdo, $app['applicant_id'], $app['applicant_no']);
                            ?>
                                <a href="select_applicant.php?applicant_id=<?php echo $app['applicant_id']; ?>"
                                    style="display: block; padding: 12px; margin-bottom: 8px; background: <?php echo $is_current ? '#4361ee' : 'white'; ?>; 
                                      color: <?php echo $is_current ? 'white' : '#374151'; ?>; border-radius: 6px; text-decoration: none;
                                      border: 1px solid <?php echo $is_current ? '#4361ee' : '#e5e7eb'; ?>;">
                                    <i class="fas fa-user"></i> <?php echo $app_name; ?>
                                    <?php if ($is_current): ?><span style="float: right;">#<?php echo $app['applicant_no']; ?></span><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button type="button" class="tab-btn active" onclick="switchTab('personal')"><i class="fas fa-user"></i> Personal</button>
                    <button type="button" class="tab-btn" onclick="switchTab('passport')"><i class="fas fa-passport"></i> Passport</button>
                    <button type="button" class="tab-btn" onclick="switchTab('travel')"><i class="fas fa-plane"></i> Travel</button>
                    <button type="button" class="tab-btn" onclick="switchTab('documents')"><i class="fas fa-file-upload"></i> Documents</button>
                    <button type="button" class="tab-btn" onclick="switchTab('contact')"><i class="fas fa-phone"></i> Contact</button>
                </div>

                <form method="POST" id="editForm" enctype="multipart/form-data">
                    <!-- Personal Tab -->
                    <div id="personalTab" class="tab-content active">
                        <h2 class="section-title"><i class="fas fa-user"></i> Personal Information</h2>
                        <div class="field-group">
                            <?php foreach ($personal_q as $q): ?>
                                <?php echo renderQuestionInput($q, $answers_by_question); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Passport Tab -->
                    <div id="passportTab" class="tab-content">
                        <h2 class="section-title"><i class="fas fa-passport"></i> Passport Details</h2>
                        <div class="field-group">
                            <?php foreach ($passport_q as $q): ?>
                                <?php echo renderQuestionInput($q, $answers_by_question); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Travel Tab -->
                    <div id="travelTab" class="tab-content">
                        <h2 class="section-title"><i class="fas fa-plane"></i> Travel Details</h2>
                        <div class="field-group">
                            <?php foreach ($travel_q as $q): ?>
                                <?php echo renderQuestionInput($q, $answers_by_question); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Contact Tab -->
                    <div id="contactTab" class="tab-content">
                        <h2 class="section-title"><i class="fas fa-phone"></i> Contact Information</h2>
                        <div class="field-group">
                            <div class="form-group">
                                <label for="applicant_email" class="required">Email Address</label>
                                <input type="email" id="applicant_email" name="applicant_email" value="<?php echo htmlspecialchars($applicant['applicant_email'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="applicant_phone" class="required">Phone Number</label>
                                <input type="tel" id="applicant_phone" name="applicant_phone" value="<?php echo htmlspecialchars($applicant['applicant_phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Tab -->
                    <div id="documentsTab" class="tab-content">
                        <h2 class="section-title"><i class="fas fa-file-upload"></i> Uploaded Documents</h2>

                        <?php if (isset($_GET['upload_success'])): ?>
                            <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                                <i class="fas fa-check-circle"></i> Document uploaded successfully!
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['upload_error'])): ?>
                            <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['upload_error']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($document_q)): ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                                <?php foreach ($document_q as $question):
                                    $q_id = $question['id'];
                                    $has_file = isset($applicant_files[$q_id]);
                                    $field_key = $question['field_key'];
                                ?>
                                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                            <h4 style="font-size: 16px; font-weight: 600; margin: 0;">
                                                <i class="fas fa-file-upload" style="color: black; margin-right: 8px;"></i>
                                                <?php echo htmlspecialchars($question['label']); ?>
                                            </h4>
                                            <?php if ($has_file): ?>
                                                <span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500;">
                                                    ✓ Uploaded
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($has_file):
                                            $file = $applicant_files[$q_id];
                                            $file_url = 'fetch_edit.php?path=' . urlencode($file['file_path']);
                                            $filename = basename($file['file_path']);
                                        ?>
                                            <div style="background: #f8fafc; border-radius: 6px; padding: 10px; margin-bottom: 15px; border: 1px solid #e5e7eb;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="fas fa-file-image" style="color: #10b981;"></i>
                                                    <span style="font-size: 13px; color: #1f2937; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                        <?php echo htmlspecialchars($filename); ?>
                                                    </span>
                                                    <a href="<?php echo $file_url; ?>" target="_blank" style="color: blue; text-decoration: none; font-size: 12px; padding: 4px 8px; background: #e0e7ff; border-radius: 4px;">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- File input -->
                                        <div>
                                            <label for="doc_<?php echo $q_id; ?>" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: #4b5563;">
                                                <?php echo $has_file ? 'Update document:' : 'Upload document:'; ?>
                                            </label>
                                            <input type="file"
                                                id="doc_<?php echo $q_id; ?>"
                                                name="document_<?php echo $q_id; ?>"
                                                accept=".jpg,.jpeg,.png,.pdf"
                                                data-question-id="<?php echo $q_id; ?>"
                                                style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: white;">
                                            <div id="filename_<?php echo $q_id; ?>" style="font-size: 11px; color: #6b7280; margin-top: 5px;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Hidden field to indicate document uploads -->
                            <input type="hidden" name="has_document_uploads" value="1">

                        <?php else: ?>
                            <p style="color: #6b7280; text-align: center; padding: 40px;">No document uploads required for this country.</p>
                        <?php endif; ?>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Changes</button>
                        <a href="edit_access.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        <a href= "index.php" class="btn btn-outline"><i class="fas fa-comments"></i> Back to Chat</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabName + 'Tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Show selected filename for file inputs
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || '';
                const fileNameDiv = document.getElementById('filename_' + this.dataset.questionId);
                if (fileNameDiv) {
                    if (fileName) {
                        fileNameDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Selected: ' + fileName;
                        fileNameDiv.style.color = '#065f46';
                    } else {
                        fileNameDiv.innerHTML = '';
                    }
                }
            });
        });

        // Form validation
        // TEMPORARY FIX - Let the form submit normally
        document.getElementById('editForm').addEventListener('submit', function(e) {
            // Just let it submit - server will handle validation
            console.log('Form submitting...');
            return true;
        });

        // Keep the beforeunload warning
        let formChanged = false;
        document.querySelectorAll('#editForm input, #editForm select').forEach(i => {
            i.addEventListener('input', () => formChanged = true);
            i.addEventListener('change', () => formChanged = true);
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Clear flag on submit
        document.getElementById('editForm').addEventListener('submit', () => {
            formChanged = false;
        });

        // Clear flag on cancel/back buttons
        document.querySelectorAll('.btn-secondary, .btn-outline').forEach(btn => {
            btn.addEventListener('click', () => {
                formChanged = false;
            });
        });
    </script>
</body>

</html>