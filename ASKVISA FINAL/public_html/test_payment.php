<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Payment Flow</title>
</head>
<body>
    <h1>Test Payment Flow</h1>
    
    <h2>Simulate Payments</h2>
    
    <h3>Success Flow:</h3>
    <ol>
        <li><a href="exco.php">Go to Main Chat</a></li>
        <li>Type "payment" in chat</li>
        <li>Click "Complete Payment" button</li>
        <li>You'll be redirected to success page</li>
        <li>Click "Back to Application"</li>
        <li>See success message in chat</li>
    </ol>
    
    <h3>Failure Flow:</h3>
    <ol>
        <li><a href="exco.php">Go to Main Chat</a></li>
        <li>Type "payment" in chat</li>
        <li>Click "Simulate Failure" button</li>
        <li>You'll be redirected to failure page</li>
        <li>Click "Back to Home"</li>
        <li>See error message in chat</li>
    </ol>
    
    <h2>Direct Links:</h2>
    <p><a href="process_payment.php?test=success">Simulate Successful Payment</a></p>
    <p><a href="process_payment.php?test=failed">Simulate Failed Payment</a></p>
    <p><a href="exco.php">Back to Main Application</a></p>
</body>
</html>