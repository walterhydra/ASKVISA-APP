<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = trim($_POST['order_id'] ?? '');
    $order_email = trim($_POST['order_email'] ?? '');

    if (empty($order_id) || empty($order_email)) {
        $error = 'Please fill in all fields';
    } else {
        // Email validation
        if (!filter_var($order_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // FIX 1 & 2: Removed extra comma and added country_name
            $stmt = $pdo->prepare("
                SELECT vo.id, vo.email, vo.phone, c.country_name 
                FROM visa_orders vo 
                LEFT JOIN countries c ON vo.country_id = c.id 
                WHERE vo.id = ? 
                AND LOWER(vo.email) = LOWER(?)
                LIMIT 1
            ");
            $stmt->execute([$order_id, $order_email]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                sleep(1);
                $error = 'No order found with these details. Please check your Order ID and Primary Email.';
            } else {
                // Get ALL applicants for this order_id
                $stmt = $pdo->prepare("
                    SELECT a.id AS applicant_id, a.order_id, a.applicant_no, 
                           a.applicant_email, a.applicant_phone
                    FROM applicants a
                    WHERE a.order_id = ?
                    ORDER BY a.applicant_no
                ");
                $stmt->execute([$order_id]);
                $all_applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($all_applicants) === 0) {
                    $error = 'No applicants found for this order.';
                } else {
                    // Clear any old edit session data
                    unset($_SESSION['edit_verified']);
                    unset($_SESSION['order_id']);
                    unset($_SESSION['applicant_id']);
                    unset($_SESSION['all_applicants']);

                    // Set session for ALL cases (both single and multiple)
                    $_SESSION['edit_verified'] = true;
                    $_SESSION['order_id'] = $order_id;
                    $_SESSION['order_email'] = $order['email'];
                    $_SESSION['order_phone'] = $order['phone'];
                    $_SESSION['order_country'] = $order['country_name'] ?? '';
                    $_SESSION['all_applicants'] = $all_applicants;
                    $_SESSION['total_applicants'] = count($all_applicants);

                    // FIX 4: Always set the first applicant as default
                    $_SESSION['applicant_id'] = $all_applicants[0]['applicant_id'];
                    $_SESSION['applicant_no'] = $all_applicants[0]['applicant_no'];

                    // Force session write
                    session_write_close();

                    // FIX 3: Always go to select_applicant.php for both single and multiple
                    header("Location: select_applicant.php");
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Edit Application Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    body {
        background: var(--platinum, #F6F7F9ff);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .access-container {
        background: var(--white-2, #FEFEFEff);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(101, 100, 100, 0.08);
        width: 100%;
        max-width: 500px;
        overflow: hidden;
        border: 1px solid var(--alabaster-grey, #D9DADFff);
    }

    .header {
        background: var(--white-2, #FEFEFEff);
        color: #E22427ff;
        padding: 30px;
        text-align: center;
        border-bottom: 2px solid #E22427ff;
    }

    .header h1 {
        font-size: 28px;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--dim-grey, #656464ff);
    }

    .header p {
        opacity: 0.8;
        font-size: 15px;
        color: var(--rosy-granite, #959599ff);
    }

    .content {
        padding: 30px;
        background: var(--white-2, #FEFEFEff);
    }

    .error-message {
        background: var(--platinum, #F6F7F9ff);
        border: 1px solid #E22427ff;
        color: #E22427ff;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 25px;
        border-left: 4px solid #E22427ff;
        font-weight: 500;
        display: <?php echo $error ? 'block' : 'none'; ?>;
    }

    .form-group {
        margin-bottom: 25px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: var(--dim-grey, #656464ff);
        font-weight: 500;
        font-size: 14px;
    }

    input {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--alabaster-grey, #D9DADFff);
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s;
        background: var(--white, #FFFFFFff);
        color: var(--dim-grey, #656464ff);
    }

    input:focus {
        outline: none;
        border-color: #E22427ff;
        background: var(--white, #FFFFFFff);
        box-shadow: 0 0 0 3px rgba(226, 36, 39, 0.05);
    }

    input::placeholder {
        color: var(--rosy-granite, #959599ff);
        font-size: 14px;
    }

    .submit-btn {
        width: 100%;
        padding: 16px;
        background: #E22427ff;
        color: var(--white, #FFFFFFff);
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
        letter-spacing: 0.3px;
    }

    .submit-btn:hover {
        background: #c41e21;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(226, 36, 39, 0.15);
    }

    .help-section {
        margin-top: 30px;
        padding: 20px;
        background: var(--platinum, #F6F7F9ff);
        border-radius: 16px;
        border-left: 4px solid #E22427ff;
        border: 1px solid var(--alabaster-grey, #D9DADFff);
    }

    .help-section h3 {
        color: var(--dim-grey, #656464ff);
        margin-bottom: 15px;
        font-size: 17px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }

    .help-section ul {
        list-style: none;
        padding: 0;
    }

    .help-section li {
        margin-bottom: 10px;
        color: var(--rosy-granite, #959599ff);
        padding-left: 20px;
        position: relative;
        font-size: 14px;
        line-height: 1.5;
    }

    .help-section li:before {
        content: '•';
        color: #E22427ff;
        font-size: 18px;
        position: absolute;
        left: 0;
        top: -2px;
    }

    .back-link {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid var(--alabaster-grey, #D9DADFff);
    }

    .back-link a {
        color: var(--rosy-granite, #959599ff);
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s;
    }

    .back-link a:hover {
        color: #E22427ff;
    }

    .order-info-note {
        background: var(--platinum, #F6F7F9ff);
        border-left: 4px solid #E22427ff;
        border: 1px solid var(--alabaster-grey, #D9DADFff);
        padding: 15px;
        border-radius: 12px;
        margin-top: 20px;
        font-size: 14px;
        color: var(--rosy-granite, #959599ff);
        line-height: 1.6;
    }

    .order-info-note strong {
        color: #E22427ff;
        font-weight: 600;
    }

    @media (max-width: 480px) {
        .content {
            padding: 20px;
        }

        .header {
            padding: 25px 20px;
        }

        .header h1 {
            font-size: 24px;
        }
    }
</style>
</head>

<body>
    <div class="access-container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Application</h1>
            <p>Access your order to edit applicant details</p>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="accessForm">
                <div class="form-group">
                    <label for="order_id">Order ID *</label>
                    <input type="text" id="order_id" name="order_id"
                        placeholder="Enter your Order ID (e.g., 11, 99)"
                        value="<?php echo htmlspecialchars($_POST['order_id'] ?? ''); ?>"
                        required>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                        Found in your confirmation email (just the number)
                    </div>
                </div>

                <div class="form-group">
                    <label for="order_email">Primary Contact Email *</label>
                    <input type="email" id="order_email" name="order_email"
                        placeholder="Enter the primary email for this order"
                        value="<?php echo htmlspecialchars($_POST['order_email'] ?? ''); ?>"
                        required>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                        The main email used for this application
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-unlock-alt"></i> Access Order
                </button>
            </form>

            <div class="order-info-note">
                <p><strong><i class="fas fa-info-circle"></i> Note:</strong> Use the <strong>primary contact email</strong> for the Order. This is different from individual applicant emails.</p>
            </div>

            <div class="help-section">
                <h3><i class="fas fa-question-circle"></i> For devs: example multple applicant order</h3>
                <ul>
                    <li><strong>Order ID:</strong> 11</li>
                    <li><strong>Primary Email:</strong> ankitverma76@testmail.com in visa_orders</li>
                </ul>
            </div>

            <div class="back-link">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        document.getElementById('accessForm').addEventListener('submit', function(e) {
            const orderId = document.getElementById('order_id').value.trim();
            const orderEmail = document.getElementById('order_email').value.trim();

            if (!orderId || !orderEmail) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }

            // Validate email 
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(orderEmail)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }

            return true;
        });

        // Auto-focus first field
        document.getElementById('order_id').focus();
    </script>
</body>

</html>