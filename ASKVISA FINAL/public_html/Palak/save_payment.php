
 
 <?php
    header('Content-Type: application/json');

    // Get payment data
    $data = json_decode(file_get_contents('php://input'), true);

    // Connect to their database (adjust credentials)
    $host = 'localhost';
    $dbname = 'visa_test';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

        $stmt = $pdo->prepare("
        INSERT INTO payments 
        (application_id, payment_id, amount, currency, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

        $stmt->execute([
            $data['applicationId'],
            $data['paymentId'],
            $data['amount'],
            $data['currency'],
            $data['status'],
            $data['timestamp']
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
