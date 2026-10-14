<?php
declare(strict_types=1);
require __DIR__ . '/connect.php';
$s = $pdo->prepare('UPDATE Users_TBL SET password=? WHERE id IN (7,8)');
$s->execute([password_hash('fictional-password', PASSWORD_BCRYPT)]);
echo "Usuarios ficticios preparados; no hay datos personales reales.\n";
