<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check for multiple applicants
if (
    !isset($_SESSION['edit_verified']) || $_SESSION['edit_verified'] !== true ||
    !isset($_SESSION['all_applicants']) || count($_SESSION['all_applicants']) <= 0
) {
    header("Location: edit_access.php?select_applicant_error");
    exit;
}

$order_id = $_SESSION['order_id'] ?? '';
$applicants = $_SESSION['all_applicants'] ?? [];
$total_applicants = count($applicants);

// Handle applicant selection
if (isset($_GET['applicant_id'])) {
    $selected_applicant_id = intval($_GET['applicant_id']);

    // Check if applicant is for the order_id
    $valid = false;
    foreach ($applicants as $applicant) {
        if ($applicant['applicant_id'] == $selected_applicant_id) {
            $valid = true;
            $_SESSION['applicant_id'] = $selected_applicant_id;
            $_SESSION['applicant_no'] = $applicant['applicant_no'];
            break;
        }
    }

    if ($valid) {
        header("Location: edit.php");
        exit;
    }
}

require 'db.php';

// Get order details 
$order = null;
try {
    $stmt = $pdo->prepare("
        SELECT vo.*, c.country_name 
        FROM visa_orders vo 
        LEFT JOIN countries c ON vo.country_id = c.id 
        WHERE vo.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error loading order: " . $e->getMessage());
}

// Function to get applicant full name
function getApplicantFullName($pdo, $applicant_id, $applicant_no)
{
    try {
        // Get first name (question_id = 1)
        $stmt = $pdo->prepare("
            SELECT answer_text 
            FROM applicant_answers 
            WHERE applicant_id = ? AND question_id = 1
            LIMIT 1
        ");
        $stmt->execute([$applicant_id]);
        $first_name_row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get last name (question_id = 2)
        $stmt = $pdo->prepare("
            SELECT answer_text 
            FROM applicant_answers 
            WHERE applicant_id = ? AND question_id = 2
            LIMIT 1
        ");
        $stmt->execute([$applicant_id]);
        $last_name_row = $stmt->fetch(PDO::FETCH_ASSOC);

        $first_name = $first_name_row['answer_text'] ?? '';
        $last_name = $last_name_row['answer_text'] ?? '';

        if (!empty($first_name) && !empty($last_name)) {
            return htmlspecialchars($first_name . ' ' . $last_name);
        } elseif (!empty($first_name)) {
            return htmlspecialchars($first_name);
        } else {
            return "Applicant #" . $applicant_no;
        }
    } catch (Exception $e) {
        return "Applicant #" . $applicant_no;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Applicant to Edit</title>
   <style>
    :root {
        /* Your provided palette */
        --alabaster-grey: #D9DADFff;
        --white: #FFFFFFff;
        --rosy-granite: #959599ff;
        --white-2: #FEFEFEff;
        --platinum: #F6F7F9ff;
        --dim-grey: #656464ff;
        
        /* Keep your red (similar to #b71c1c but using your style) */
        --primary: #E22427ff;
        --primary-light: #c41e21;
        --primary-dark: #8e1515;
        
        /* Updated variables using your palette */
        --dark: var(--dim-grey, #656464ff);
        --light: var(--platinum, #F6F7F9ff);
        --gray: var(--rosy-granite, #959599ff);
        --light-gray: var(--alabaster-grey, #D9DADFff);
        --border-radius: 16px;
        --shadow: 0 10px 30px rgba(101, 100, 100, 0.03);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
        background: var(--platinum, #F6F7F9ff);
        min-height: 100vh;
        padding: 20px;
        color: var(--dim-grey, #656464ff);
    }

    .select-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .header {
        background: var(--dim-grey, #656464ff);
        color: var(--white, #FFFFFFff);
        padding: 30px 40px;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        margin-bottom: 30px;
    }

    .header h1 {
        font-size: 32px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        color: var(--white, #FFFFFFff);
    }

    .header p {
        opacity: 0.8;
        font-size: 16px;
        color: var(--alabaster-grey, #D9DADFff);
    }

    .content {
        background: var(--white-2, #FEFEFEff);
        border-radius: var(--border-radius);
        padding: 40px;
        box-shadow: var(--shadow);
        border: 1px solid var(--alabaster-grey, #D9DADFff);
    }

    .order-info {
        background: var(--platinum, #F6F7F9ff);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 40px;
        border-left: 4px solid var(--primary, #E22427ff);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px dashed var(--alabaster-grey, #D9DADFff);
    }

    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .info-label {
        color: var(--rosy-granite, #959599ff);
        font-weight: 500;
        font-size: 14px;
    }

    .info-value {
        font-weight: 600;
        color: var(--dim-grey, #656464ff);
        font-size: 14px;
    }

    .applicants-section {
        margin-bottom: 40px;
    }

    .section-title {
        font-size: 22px;
        font-weight: 600;
        margin-bottom: 30px;
        color: var(--dim-grey, #656464ff);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .applicants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    @media (max-width: 768px) {
        .applicants-grid {
            grid-template-columns: 1fr;
        }
    }

    .applicant-card {
    background: var(--white-2, #FEFEFEff);
    border: 1px solid var(--alabaster-grey, #D9DADFff);
    border-radius: var(--border-radius);
    padding: 30px;
    display: block;
   
    
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(101, 100, 100, 0.02);
}

    .applicant-card:hover {
        border-color: var(--primary, #E22427ff);
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(226, 36, 39, 0.05);
    }

    .applicant-card.active {
        border-color: var(--primary, #E22427ff);
        background: linear-gradient(135deg, rgba(226, 36, 39, 0.02) 0%, rgba(226, 36, 39, 0.01) 100%);
    }

    .applicant-number {
        position: absolute;
        top: 0;
        left: 0;
        background: var(--primary, #E22427ff);
        color: var(--white, #FFFFFFff);
        padding: 8px 15px;
        border-radius: 0 0 var(--border-radius) 0;
        font-weight: 450;
        font-size: 12px;
    }

    .applicant-name {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 15px;
        color: var(--dim-grey, #656464ff);
        padding-top: 20px;
        padding-left:10px;
    }

    .applicant-details {
        margin-bottom: 20px;
        padding-left:10px;
        
    }

    .detail-row {
        display: flex;
        justify-content: left;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .detail-label {
        color: var(--rosy-granite, #959599ff);
    }

    .detail-value {
        color: var(--dim-grey, #656464ff);
        font-weight: 500;
        column-gap: 5px;
        text-align: center;
        max-width: 200px;
        word-break: break-word;
        padding-left: 10px;
    }

    .select-btn {
    width: 50%;
    padding: 14px;
    background: var(--primary, #E22427ff);
    color: var(--white, #FFFFFFff);
    border: none;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: block;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 20px auto 0;


    .select-btn:hover {
        background: var(--primary-dark, #8e1515);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(226, 36, 39, 0.1);
    }

    .back-link {
        text-align: center;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid var(--alabaster-grey, #D9DADFff);
    }

    .back-link a {
        color: var(--rosy-granite, #959599ff);
        text-decoration: none;
        font-weight: 500;
        font-size: 15px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s;
    }

    .back-link a:hover {
        color: var(--primary, #E22427ff);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--rosy-granite, #959599ff);
    }

    .empty-state i {
        font-size: 64px;
        color: var(--alabaster-grey, #D9DADFff);
        margin-bottom: 20px;
    }

    .visa-status {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-initiated {
        background: var(--platinum, #F6F7F9ff);
        color: var(--dim-grey, #656464ff);
    }

    .status-in_review {
        background: #fff3e0;
        color: var(--primary, #E22427ff);
    }

    .status-approved {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-rejected {
        background: #ffebee;
        color: var(--primary, #E22427ff);
    }

    .applicant-email {
        color: var(--primary, #E22427ff);
        font-weight: 500;
    }

    .applicant-phone {
        color: var(--dim-grey, #656464ff);
        font-weight: 500;
    }

    /* Add a subtle accent */
    .applicant-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, transparent 50%, rgba(226, 36, 39, 0.02) 50%);
        pointer-events: none;
    }
</style>
</head>

<body>
    <div class="select-container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Select Applicant</h1>
            <p>Choose which applicant's information you want to edit</p>
        </div>

        <div class="content">
            <div class="order-info">
                <h3 style="margin-bottom: 20px; color: var(--dark);">Order #<?php echo htmlspecialchars($order_id); ?></h3>
                <div class="info-row">
                    <div class="info-label">Application ID</div>
                    <div class="info-value">#<?php echo htmlspecialchars($order_id); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Country</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['country_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total Applicants</div>
                    <div class="info-value">
                        <span style="color: var(--primary); font-weight: 600;">
                            <?php echo $total_applicants; ?> person<?php echo $total_applicants > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Order Status</div>
                    <div class="info-value">
                        <span class="visa-status status-<?php echo str_replace(' ', '_', strtolower($order['visa_status'] ?? 'initiated')); ?>">
                            <?php echo htmlspecialchars($order['visa_status'] ?? 'Initiated'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="applicants-section">
                <h2 class="section-title">
                    <i class="fas fa-user-friends"></i>
                    Applicants in Order #<?php echo htmlspecialchars($order_id); ?>
                </h2>

                <?php if (count($applicants) > 0): ?>
                    <div class="applicants-grid">
                        <?php foreach ($applicants as $index => $applicant):
                            $applicant_id = $applicant['applicant_id'];
                            $applicant_no = $applicant['applicant_no'];
                            $applicant_email = $applicant['applicant_email'] ?? '';
                            $applicant_phone = $applicant['applicant_phone'] ?? '';

                            $applicant_name = getApplicantFullName($pdo, $applicant_id, $applicant_no);
                        ?>
                            <div class="applicant-card" onclick="window.location.href='?applicant_id=<?php echo $applicant_id; ?>'">
                                <div class="applicant-number">
                                    Applicant #<?php echo $applicant_no; ?>
                                </div>

                                <div class="applicant-name">
                                    <?php echo $applicant_name; ?>
                                </div>

                                <div class="applicant-details">
                                    <?php if (!empty($applicant_email)): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">
                                                <i class="fas fa-envelope" style="color: #6b7280;"></i>
                                            </span>
                                            <span class="detail-value applicant-email">
                                                <?php echo htmlspecialchars($applicant_email); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($applicant_phone)): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">
                                                <i class="fas fa-phone" style="color: #6b7280;"></i>
                                            </span>
                                            <span class="detail-value applicant-phone">
                                                <?php echo htmlspecialchars($applicant_phone); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="detail-row">
                                        <span class="detail-label">
                                            <i class="fas fa-id-card" style="color: #6b7280;"></i>
                                        </span>
                                        <span class="detail-value">
                                            ID: <?php echo $applicant_id; ?>
                                        </span>
                                    </div>
                                </div>

                                <button class="select-btn" onclick="event.stopPropagation(); window.location.href='?applicant_id=<?php echo $applicant_id; ?>'">
                                    <i class="fas fa-edit"></i> Edit This Applicant
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>No Applicants Found</h3>
                        <p>No applicant information was found for this order.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="note" style="background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 30px 0;">
                <h4 style="color: #0369a1; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle"></i> Editing Multiple Applicants
                </h4>
                <p style="color: #4b5563; margin: 0; line-height: 1.6;">
                    You can edit each applicant separately. After editing one applicant,
                    you can return to this page to select another applicant to edit.
                </p>
            </div>

            <div class="back-link">
                <a href="edit_access.php">
                    <i class="fas fa-arrow-left"></i> Back to Access Page
                </a>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        document.querySelectorAll('.applicant-card').forEach(card => {
            card.addEventListener('click', function() {
                const link = this.querySelector('.select-btn');
                if (link) {
                    window.location.href = link.onclick.toString().match(/href='([^']+)'/)[1];
                }
            });
        });


        document.querySelectorAll('.applicant-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.borderColor = '#4361ee';
                this.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.borderColor = '#e5e7eb';
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>

</html>