<?php
require 'db.php';

header("Content-Type: application/json");

/* ==========================
   SECRET KEY SECURITY
========================== */
if (!isset($_GET['key']) || $_GET['key'] !== 'SUPER_SECRET_123') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

/* ==========================
   START TRANSACTION
========================== */
$pdo->beginTransaction();

/* ==========================
   FETCH NEXT APPLICANT
========================== */
$stmt = $pdo->prepare("
    SELECT 
        a.id AS applicant_id,
        a.applicant_no,
        a.applicant_email,
        a.applicant_phone,
        a.order_id,
        vo.email AS order_email,
        vo.phone AS order_phone,
        c.country_name
    FROM applicants a
    JOIN visa_orders vo ON vo.id = a.order_id
    JOIN countries c ON c.id = vo.country_id
    WHERE 
        vo.payment_status = 'paid'
        AND a.visa_status = 'pending'
    ORDER BY a.created_at ASC
    LIMIT 1
    FOR UPDATE
");

$stmt->execute();
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    $pdo->commit();
    echo json_encode(["status" => "EMPTY"]);
    exit;
}

/* ==========================
   LOCK IT (MARK PROCESSING)
========================== */
$update = $pdo->prepare("
    UPDATE applicants
    SET visa_status = 'processing'
    WHERE id = ?
");
$update->execute([$applicant['applicant_id']]);

/* ==========================
   FETCH ANSWERS
========================== */
$ansStmt = $pdo->prepare("
    SELECT 
        cq.label,
        aa.answer_text
    FROM applicant_answers aa
    JOIN country_questions cq ON cq.id = aa.question_id
    WHERE aa.applicant_id = ?
    ORDER BY cq.sort_order ASC
");
$ansStmt->execute([$applicant['applicant_id']]);
$answers = $ansStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
   FETCH FILES (optional)
========================== */
$fileStmt = $pdo->prepare("
    SELECT 
        cq.label,
        af.file_path,
        af.file_type
    FROM applicant_files af
    JOIN country_questions cq ON cq.id = af.question_id
    WHERE af.applicant_id = ?
");
$fileStmt->execute([$applicant['applicant_id']]);
$files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

$pdo->commit();

/* ==========================
   RETURN CLEAN JSON
========================== */
echo json_encode([
    "status" => "OK",
    "applicant" => $applicant,
    "answers" => $answers,
    "files" => $files
]);