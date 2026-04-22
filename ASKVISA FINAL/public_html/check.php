<?php
$path = __DIR__ . '/../gov_id/';

echo "realpath: " . realpath($path) . "\n";
echo "exists: " . (is_dir($path) ? "YES" : "NO") . "\n";
echo "perm: " . decoct(fileperms($path)) . "\n";
