<?php
session_start();
require 'db.php';

/* ======================
   AUTH GUARD
====================== */
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit;
}

/* ======================
   VALIDATE ORDER ID
====================== */
$order_id = $_GET['order_id'] ?? null;
if (!$order_id || !ctype_digit($order_id)) {
    die("Invalid order ID");
}

/* ======================
   FETCH ORDER INFO (PAID ONLY)
====================== */
$orderStmt = $pdo->prepare("
    SELECT vo.id, c.country_name, vo.email, vo.phone, vo.visa_status
    FROM visa_orders vo
    JOIN countries c ON c.id = vo.country_id
    WHERE vo.id = ? AND vo.payment_status = 'paid'
");
$orderStmt->execute([$order_id]);
$order = $orderStmt->fetch();

if (!$order) {
    die("Order not found or not paid");
}

/* ======================
   FETCH APPLICANTS
====================== */
$appStmt = $pdo->prepare("
    SELECT 
        id,
        applicant_no,
        applicant_email,
        applicant_phone,
        visa_status,
        created_at
    FROM applicants
    WHERE order_id = ?
    ORDER BY applicant_no ASC
");
$appStmt->execute([$order_id]);
$applicants = $appStmt->fetchAll();

$applicantCount = count($applicants);
?>
<!DOCTYPE html>
<html>
<head>
<title>Applicants – Order <?= $order_id ?></title>

<style>
body {
    background:#0e0e0e;
    color:#fff;
    font-family: Arial, sans-serif;
}
.container {
    width:95%;
    margin:20px auto;
}
table {
    width:100%;
    border-collapse: collapse;
}
th, td {
    border:1px solid #333;
    padding:10px;
    text-align:center;
}
th {
    background:#1f1f1f;
}
tr:nth-child(even) {
    background:#151515;
}
a {
    color:#ff3b3b;
    text-decoration:none;
}
.header {
    display:flex;
    justify-content:space-between;
    margin-bottom:20px;
}
.badge {
    background:#ff3b3b;
    color:#000;
    padding:4px 8px;
    font-weight:bold;
    border-radius:4px;
    margin-left:6px;
}
</style>
</head>

<body>

<div class="container">

<div class="header">
    <div>
        <h2>
            Order #<?= $order_id ?>
            <span class="badge"><?= $applicantCount ?> Applicant<?= $applicantCount > 1 ? 's' : '' ?></span>
        </h2>
        <p>
            <strong>Country:</strong> <?= htmlspecialchars($order['country_name']) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?><br>
            <strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?><br>
            <strong>Visa Status:</strong> <?= strtoupper($order['visa_status']) ?>
        </p>
    </div>
    <div>
        <a href="application.php">← Back to Orders</a>
    </div>
</div>

<table>
<tr>
    <th>Applicant #</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Status</th>
    <th>Submitted</th>
    <th>Action</th>
</tr>

<?php if ($applicantCount === 0): ?>
<tr>
    <td colspan="6">No applicants found (this should not fucking happen)</td>
</tr>
<?php endif; ?>

<?php foreach ($applicants as $a): ?>
<tr>
    <td><?= (int)$a['applicant_no'] ?></td>
    <td><?= htmlspecialchars($a['applicant_email'] ?? '-') ?></td>
    <td><?= htmlspecialchars($a['applicant_phone'] ?? '-') ?></td>
    <td><?= strtoupper($a['visa_status']) ?></td>
    <td><?= $a['created_at'] ?></td>
    <td>
        <a href="view_applicant_detail.php?applicant_id=<?= (int)$a['id'] ?>">
            View Details
        </a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</div>

</body>
</html>
