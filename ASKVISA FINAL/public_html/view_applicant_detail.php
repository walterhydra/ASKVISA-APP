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
       VALIDATE INPUT
    ====================== */
    $applicant_id = $_GET['applicant_id'] ?? null;
    if (!$applicant_id || !ctype_digit($applicant_id)) {
        die("Invalid applicant ID");
    }
    
    /* ======================
       FETCH APPLICANT META
    ====================== */
    $appStmt = $pdo->prepare("
        SELECT 
            a.id,
            a.applicant_no,
            a.applicant_email,
            a.applicant_phone,
            a.visa_status,
            a.created_at,
            a.order_id,
            c.country_name
        FROM applicants a
        JOIN visa_orders vo ON vo.id = a.order_id
        JOIN countries c ON c.id = vo.country_id
        WHERE a.id = ?
    ");
    $appStmt->execute([$applicant_id]);
    $applicant = $appStmt->fetch();
    
    if (!$applicant) {
        die("Applicant not found");
    }
    
    /* ======================
       FETCH ANSWERS
    ====================== */
    $ansStmt = $pdo->prepare("
        SELECT 
            cq.label,
            aa.answer_text
        FROM applicant_answers aa
        JOIN country_questions cq ON cq.id = aa.question_id
        WHERE aa.applicant_id = ?
        ORDER BY cq.sort_order ASC
    ");
    $ansStmt->execute([$applicant_id]);
    $answers = $ansStmt->fetchAll();
    
    /* ======================
       FETCH FILES
    ====================== */
    $fileStmt = $pdo->prepare("
        SELECT 
            cq.label,
            af.file_path,
            af.file_type,
            af.uploaded_at
        FROM applicant_files af
        JOIN country_questions cq ON cq.id = af.question_id
        WHERE af.applicant_id = ?
    ");
    $fileStmt->execute([$applicant_id]);
    $files = $fileStmt->fetchAll();
    
    /* ======================
       HOSTINGER STORAGE BASE
    ====================== */
    $storageBase = '/files/gov_id';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <title>Applicant #<?= htmlspecialchars($applicant['applicant_no']) ?></title>
    
    <style>
    body {
        background:#0b0b0b;
        color:#fff;
        font-family: Arial, sans-serif;
    }
    .container {
        width:90%;
        margin:20px auto;
    }
    .section {
        background:#151515;
        padding:15px;
        margin-bottom:20px;
        border:1px solid #333;
    }
    table {
        width:100%;
        border-collapse: collapse;
    }
    th, td {
        border:1px solid #333;
        padding:8px;
        vertical-align: top;
    }
    th {
        background:#1f1f1f;
        width:30%;
    }
    a {
        color:#ff3b3b;
        text-decoration:none;
        cursor:pointer;
    }
    .badge {
        padding:4px 8px;
        background:#444;
        border-radius:4px;
    }
    </style>
    </head>
    
    <body>
    
    <div class="container">
    
    <a href="view_applicants.php?order_id=<?= (int)$applicant['order_id'] ?>">
    ← Back to Applicants
    </a>
    
    <div class="section">
        <h2>Applicant Metadata</h2>
        <p>
            <strong>Applicant #:</strong> <?= htmlspecialchars($applicant['applicant_no']) ?><br>
            <strong>Country:</strong> <?= htmlspecialchars($applicant['country_name']) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($applicant['applicant_email']) ?><br>
            <strong>Phone:</strong> <?= htmlspecialchars($applicant['applicant_phone']) ?><br>
            <strong>Status:</strong> <span class="badge"><?= strtoupper($applicant['visa_status']) ?></span><br>
            <strong>Submitted At:</strong> <?= htmlspecialchars($applicant['created_at']) ?>
        </p>
    </div>
    
    <div class="section">
        <h3>Answers</h3>
    
        <?php if (!$answers): ?>
            <p>No answers recorded.</p>
        <?php else: ?>
        <table>
            <?php foreach ($answers as $ans): ?>
            <tr>
                <th><?= htmlspecialchars($ans['label']) ?></th>
                <td><?= nl2br(htmlspecialchars($ans['answer_text'] ?? '—')) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h3>Uploaded Files</h3>
    
        <?php if (!$files): ?>
            <p>No files uploaded.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Field</th>
                <th>File</th>
                <th>Uploaded</th>
            </tr>
    
<?php foreach ($files as $f): ?>
    <?php
    $rawPath = $f['file_path'];

    // DB stores: /fetch_file.php?path=xxxx
    if (strpos($rawPath, 'path=') !== false) {
        parse_str(parse_url($rawPath, PHP_URL_QUERY), $q);
        $relativePath = urldecode($q['path'] ?? '');
    } else {
        $relativePath = ltrim($rawPath, '/');
    }

    // Normalize
    $relativePath = ltrim($relativePath, '/');

    // FINAL STREAM URL (important)
    $fileUrl = "fetch_docs.php?path=" . urlencode($relativePath);
    ?>

    <tr>
        <td><?= htmlspecialchars($f['label']) ?></td>
<td>
    <a href="<?= htmlspecialchars($fileUrl) ?>"
       class="view-file"
       data-type="<?= htmlspecialchars($f['file_type']) ?>">
        View
    </a>
    |
    <a href="<?= htmlspecialchars($fileUrl) ?>"
       class="download-file"
       download>
        Download
    </a>
</td>

        <td><?= htmlspecialchars($f['uploaded_at']) ?></td>
    </tr>
<?php endforeach; ?>

        </table>
        <?php endif; ?>
    </div>
    
    </div>
    
    <!-- PREVIEW MODAL -->
    <div id="previewModal" style="
        display:none;
        position:fixed;
        top:0;left:0;
        width:100%;height:100%;
        background:rgba(0,0,0,.9);
        z-index:9999;
    ">
        <span onclick="closePreview()" style="
            position:absolute;
            top:15px;
            right:30px;
            font-size:30px;
            cursor:pointer;
            color:#fff;
        ">✖</span>
    
        <div id="previewContent" style="
            width:90%;
            height:90%;
            margin:5% auto;
            text-align:center;
        "></div>
    </div>
    
    <script>
    document.querySelectorAll('.view-file').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
    
            const url = this.href;
            const type = this.dataset.type;
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');
    
            content.innerHTML = '';
    
            if (type && type.startsWith('image')) {
                content.innerHTML = `<img src="${url}" style="max-width:100%; max-height:100%;">`;
            } else if (type === 'application/pdf') {
                content.innerHTML = `<iframe src="${url}" style="width:100%; height:100%; border:none;"></iframe>`;
            } else {
                window.open(url, '_blank');
                return;
            }
    
            modal.style.display = 'block';
        });
    });
    
    function closePreview() {
        document.getElementById('previewModal').style.display = 'none';
    }
    </script>
    
    </body>
    </html>
