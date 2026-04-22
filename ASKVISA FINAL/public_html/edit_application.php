<?php
/* =========================
   DEBUG (DEV ONLY)
========================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

/* =========================
   SESSION GUARD
========================= */
if (empty($_SESSION['edit_verified'])) {
    die('Access denied');
}

$order_id     = $_SESSION['order_id'];
$applicant_id = $_SESSION['applicant_id'];

/* =========================
   LOAD ORDER
========================= */
$stmt = $pdo->prepare("SELECT * FROM visa_orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Invalid order');
}

if (in_array($order['visa_status'], ['approved','rejected'])) {
    die('Application locked. No edits allowed.');
}

/* =========================
   LOAD APPLICANT
========================= */
$stmt = $pdo->prepare(
    "SELECT * FROM applicants WHERE id = ? AND order_id = ?"
);
$stmt->execute([$applicant_id, $order_id]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    die('Invalid applicant');
}

/* =========================
   LOAD QUESTIONS
========================= */
$stmt = $pdo->prepare(
    "SELECT q.* FROM country_questions q
     LEFT JOIN applicant_answers aa ON q.id = aa.question_id AND aa.applicant_id = ?
     LEFT JOIN applicant_files af ON q.id = af.question_id AND af.applicant_id = ?
     WHERE q.country_id = ? AND (q.is_active = 1 OR aa.id IS NOT NULL OR af.id IS NOT NULL)
     GROUP BY q.id
     ORDER BY q.sort_order ASC, q.id ASC"
);
$stmt->execute([$applicant_id, $applicant_id, $order['country_id']]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LOAD ANSWERS
========================= */
$stmt = $pdo->prepare(
    "SELECT question_id, answer_text
     FROM applicant_answers
     WHERE order_id = ? AND applicant_id = ?"
);
$stmt->execute([$order_id, $applicant_id]);

$answers = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $answers[$row['question_id']] = $row['answer_text'];
}

/* =========================
   LOAD FILES
========================= */
$stmt = $pdo->prepare(
    "SELECT question_id, file_path
     FROM applicant_files
     WHERE order_id = ? AND applicant_id = ?"
);
$stmt->execute([$order_id, $applicant_id]);

$files = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $files[$row['question_id']] = $row['file_path'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Application</title>
</head>
<body>

<h2>Edit Visa Application</h2>

<p>
<b>Application #<?php echo htmlspecialchars($order_id); ?></b><br>
Applicant <?php echo htmlspecialchars($applicant['applicant_no']); ?>
</p>

<form method="POST" action="update_application.php" enctype="multipart/form-data">

<?php foreach ($questions as $q): 
    $qid   = $q['id'];
    $type  = $q['field_type'];
    $label = $q['label'];
    $value = $answers[$qid] ?? '';
?>

<div style="margin-bottom:15px;">
    <label><b><?php echo htmlspecialchars($label); ?></b></label><br>

<?php if ($type === 'text'): ?>
    <input type="text" name="answers[<?php echo $qid; ?>]"
           value="<?php echo htmlspecialchars($value); ?>">

<?php elseif ($type === 'date'): ?>
    <input type="date" name="answers[<?php echo $qid; ?>]"
           value="<?php echo htmlspecialchars($value); ?>">

<?php elseif ($type === 'number'): ?>
    <input type="number" name="answers[<?php echo $qid; ?>]"
           value="<?php echo htmlspecialchars($value); ?>">

<?php elseif ($type === 'select'): ?>
    <?php
        $optStmt = $pdo->prepare(
            "SELECT * FROM question_options
             WHERE question_id = ?
             ORDER BY sort_order"
        );
        $optStmt->execute([$qid]);
        $opts = $optStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <select name="answers[<?php echo $qid; ?>]">
        <option value="">-- Select --</option>
        <?php foreach ($opts as $opt): ?>
            <option value="<?php echo htmlspecialchars($opt['option_value']); ?>"
                <?php if ($opt['option_value'] == $value) echo 'selected'; ?>>
                <?php echo htmlspecialchars($opt['option_label']); ?>
            </option>
        <?php endforeach; ?>
    </select>

<?php elseif ($type === 'file'): ?>
    <?php if (isset($files[$qid])): ?>
        <small>File uploaded</small><br>
    <?php endif; ?>
    <input type="file" name="files[<?php echo $qid; ?>]">

<?php endif; ?>

</div>

<?php endforeach; ?>

<button type="submit">Save Changes</button>
</form>

</body>
</html>
