<?php
require '../db.php';
header("Content-Type: application/json");

/* ======================
   SECURITY
====================== */
if (!isset($_GET['key']) || $_GET['key'] !== 'SUPER_SECRET_123') {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

/* ======================
   VALIDATE ORDER ID
====================== */
$order_id = $_GET['order_id'] ?? null;

if (!$order_id || !ctype_digit($order_id)) {
    echo json_encode(["error" => "Invalid order ID"]);
    exit;
}

/* ======================
   FETCH ORDER INFO
====================== */
$orderStmt = $pdo->prepare("
    SELECT 
        vo.*,
        c.country_name
    FROM visa_orders vo
    JOIN countries c ON c.id = vo.country_id
    WHERE vo.id = ?
");
$orderStmt->execute([$order_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(["error" => "Order not found"]);
    exit;
}

/* ======================
   FETCH ALL APPLICANTS FOR ORDER
====================== */
$appStmt = $pdo->prepare("
    SELECT *
    FROM applicants
    WHERE order_id = ?
    ORDER BY applicant_no ASC
");
$appStmt->execute([$order_id]);
$applicants = $appStmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================
   FETCH ANSWERS & FILES FOR EACH APPLICANT
====================== */
foreach ($applicants as &$app) {

    // Answers
    $ansStmt = $pdo->prepare("
        SELECT cq.label, aa.answer_text
        FROM applicant_answers aa
        JOIN country_questions cq ON cq.id = aa.question_id
        WHERE aa.applicant_id = ?
        ORDER BY cq.sort_order ASC
    ");
    $ansStmt->execute([$app['id']]);
    $app['answers'] = $ansStmt->fetchAll(PDO::FETCH_ASSOC);

    // Files
    $fileStmt = $pdo->prepare("
        SELECT cq.label, af.file_path, af.file_type, af.uploaded_at
        FROM applicant_files af
        JOIN country_questions cq ON cq.id = af.question_id
        WHERE af.applicant_id = ?
    ");
    $fileStmt->execute([$app['id']]);
    $app['files'] = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ======================
   OUTPUT JSON
====================== */
echo json_encode([
    "order" => $order,
    "applicants" => $applicants
], JSON_PRETTY_PRINT);