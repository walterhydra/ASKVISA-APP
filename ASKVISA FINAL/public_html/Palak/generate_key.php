<?php

require_once 'services/EncryptionService.php';

echo "<h3>Generating Encryption Key</h3>";

$newKey = EncryptionService::generateKey();

echo "Your new encryption key:<br>";
echo "<textarea readonly style='width:500px;height:100px;font-family:monospace;'>$newKey</textarea>";
echo "<br><br>";

echo "Add this to your .env file:<br>";
echo "<code>ENCRYPTION_KEY=\"$newKey\"</code>";

echo "<br><br><strong style='color:red;'>⚠️ ONE KEY IS ALREADY IN PLACE, DELETE AFTER REPLACING⚠️</strong>";
