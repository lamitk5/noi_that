<?php

$caPath = getenv('MYSQL_ATTR_SSL_CA');

if (empty($caPath)) {
    echo "MYSQL_ATTR_SSL_CA is not set. Skipping SSL CA verification.\n";
    exit(0);
}

if (!file_exists($caPath) || !is_readable($caPath)) {
    fwrite(STDERR, "Error: Cannot read MySQL CA certificate at: {$caPath}\n");
    exit(1);
}

$content = file_get_contents($caPath);
if (!str_contains($content, 'BEGIN CERTIFICATE')) {
    fwrite(STDERR, "Error: Invalid CA certificate format at: {$caPath}\n");
    exit(1);
}

echo "MySQL SSL CA certificate verified successfully.\n";
exit(0);
