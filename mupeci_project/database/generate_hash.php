<?php
// Script to generate bcrypt hashes for admin123 and password123

$passwords = ['admin123', 'password123'];
foreach ($passwords as $pwd) {
    echo "$pwd: " . password_hash($pwd, PASSWORD_BCRYPT) . "<br>\n";
} 