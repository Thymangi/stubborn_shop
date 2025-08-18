<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=stubborn_shop', 'root', '');
    echo "OK PDO MySQL fonctionne";
} catch (PDOException $e) {
    echo $e->getMessage();
}