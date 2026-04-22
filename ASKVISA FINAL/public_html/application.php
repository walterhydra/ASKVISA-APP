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
   FETCH ORDERS (MASTER)
====================== */
$stmt = $pdo->query("
    SELECT
        vo.id AS order_id,
        c.country_name,
        vo.email,
        vo.phone,
        vo.payment_status,
        vo.visa_status,
        vo.created_at,
        COUNT(a.id) AS applicant_count
    FROM visa_orders vo
    JOIN countries c ON c.id = vo.country_id
    LEFT JOIN applicants a ON a.order_id = vo.id
    WHERE vo.payment_status = 'paid'
    GROUP BY vo.id
    ORDER BY vo.created_at DESC
");

$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>Visa Orders</title>

<style>
body {
    background:#0d0d0d;
    color:#fff;
    font-family: Arial, sans-serif;
}

table {
    width:95%;
    margin:30px auto;
    border-collapse: collapse;
}

th, td {
    padding:10px;
    border:1px solid #333;
    text-align:center;
}

th {
    background:#1f1f1f;
}

tr:nth-child(even) {
    background:#141414;
}

.badge {
    padding:4px 8px;
    border-radius:4px;
    font-size: 12px;
}

.paid { background:green; }
.pending { background:orange; }
.failed { background:red; }

a {
    color:#ff3b3b;
    text-decoration:none;
}
.top {
    width:95%;
    margin:20px auto;
    display:flex;
    justify-content:space-between;
}
</style>
</head>

<body>

<div class="top">
    <h2>Visa Orders (Master)</h2>
    <a href="logout.php">Logout</a>
</div>

<table>
<tr>
    <th>Order ID</th>
    <th>Country</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Applicants</th>
    <th>Payment</th>
    <th>Visa Status</th>
    <th>Created</th>
    <th>Action</th>
</tr>

<?php if (count($orders) === 0): ?>
<tr>
    <td colspan="8">No orders yet. Nothing to process.</td>
</tr>
<?php endif; ?>

<?php foreach ($orders as $o): ?>
<tr>
    <td><?= $o['order_id'] ?></td>
    <td><?= htmlspecialchars($o['country_name']) ?></td>
    <td><?= htmlspecialchars($o['email']) ?></td>
    <td><?= htmlspecialchars($o['phone']) ?></td>
    <td><strong><?= $o['applicant_count'] ?></strong></td>
    <td>
        <span class="badge <?= $o['payment_status'] ?>">
            <?= strtoupper($o['payment_status']) ?>
        </span>
    </td>
    <td><?= strtoupper($o['visa_status']) ?></td>
    <td><?= $o['created_at'] ?></td>
    <td>
    <a href="view_applicants.php?order_id=<?= $o['order_id'] ?>">
        View Applicants
    </a>
</td>

</tr>
<?php endforeach; ?>

</table>

</body>
</html>
