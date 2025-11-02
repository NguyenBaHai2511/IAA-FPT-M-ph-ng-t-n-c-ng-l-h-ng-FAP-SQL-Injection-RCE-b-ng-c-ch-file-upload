<?php
require_once __DIR__ . '/db.php';
$pdo = getDB();
$tables = ['users','students','grades','payments','complaints','attendance','schedule'];
foreach ($tables as $t) {
    try{
        $c = $pdo->query("SELECT COUNT(*) as c FROM $t")->fetch(PDO::FETCH_ASSOC)['c'];
    } catch (Exception $e) {
        $c = 'N/A';
    }
    echo "$t: $c\n";
}
?>