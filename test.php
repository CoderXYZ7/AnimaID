<?php
/**
 * Root diagnostic - access at https://animaidsgn.mywire.org/test.php
 */
header('Content-Type: text/plain');
echo "Root index.php is working!\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "This file: " . __FILE__ . "\n";
