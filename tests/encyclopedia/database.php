<?php
declare(strict_types=1);

// No bootstrap or .env. Only the runner-created socket can be used.
$root = dirname(__DIR__, 2);
$socket = getenv('VLEXPLOSION_TEST_SOCKET');
$directory = $socket ? realpath(dirname($socket)) : false;
$runtime = realpath($root . '/tests/runtime');
if (!$directory || !$runtime || dirname($directory) !== $runtime ||
    !preg_match('/^db-enc-[a-f0-9]{8}$/D', basename($directory)) || basename($socket) !== 's.sock') {
    throw new RuntimeException('Isolated socket required');
}
$pdo = new PDO('mysql:unix_socket=' . $socket . ';dbname=vlexplosion_test;charset=utf8mb4', 'test_runner', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$isolation = $pdo->query('SELECT @@datadir AS dir,@@socket AS socket,@@skip_networking AS isolated')->fetch();
if (realpath($isolation['dir']) !== $directory || realpath($isolation['socket']) !== realpath($socket) || (int)$isolation['isolated'] !== 1) {
    throw new RuntimeException('Server isolation mismatch');
}
require $root . '/app/Services/LegacyEncyclopediaMigration.php';
use App\Services\LegacyEncyclopediaMigration;

$count = 0;
function check(bool $ok, string $message): void {
    global $count;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $message); }
    $count++;
}
function rejects(PDO $pdo, string $sql, string $message): void {
    try { $pdo->exec($sql); } catch (PDOException) { check(true, $message); return; }
    throw new RuntimeException('FAIL: ' . $message);
}
function originals(PDO $pdo): array {
    return [
        $pdo->query('SELECT * FROM VINYLS_TBL ORDER BY Id')->fetchAll(),
        $pdo->query('SELECT * FROM AUTHORS_TBL ORDER BY Id')->fetchAll(),
        $pdo->query('SELECT * FROM AUTOR_VINYLS_TBL ORDER BY Autor_Id,Vinilo_Id')->fetchAll(),
    ];
}

// Explicitly synthetic legacy data. No real collection, external API or artwork.
$pdo->exec("INSERT INTO VINYLS_TBL(Id,Title,Genres_Id,Format_Id,Condition_Id,Record_Label_Id,Producer,Release_date,Edition_Id,User_Id,Is_Favorite,Is_Desired,Image_Path) VALUES
 (101,'Fictional legacy album',1,1,1,1,'Fictional producer',1984,1,7,1,1,'uploads/covers/fictional-not-a-file.png'),
 (102,'Fictional legacy album',1,1,1,1,'Fictional producer',1984,1,8,0,0,NULL)");
$pdo->exec("INSERT INTO AUTHORS_TBL(Id,Author_Name) VALUES(101,'Fictional author A'),(102,'Fictional author B')");
$pdo->exec('INSERT INTO AUTOR_VINYLS_TBL VALUES(101,101),(102,101),(101,102)');
$before = originals($pdo);
check(LegacyEncyclopediaMigration::run($pdo) === 2, 'two legacy copies migrated');
check(originals($pdo) === $before, 'all original fields, IDs, authors and paths unchanged');
check(LegacyEncyclopediaMigration::run($pdo) === 0, 'repeating migration does not duplicate');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_ALBUMS WHERE draft_owner_id IS NOT NULL AND original_date IS NULL')->fetchColumn() === 2, 'private drafts without inferred original dates');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE edition_id IS NULL')->fetchColumn() === 2, 'unknown editions remain unknown');
$snapshot = json_decode($pdo->query('SELECT legacy_snapshot FROM ENC_COPIES WHERE vinyl_id=101')->fetchColumn(), true, 512, JSON_THROW_ON_ERROR);
check($snapshot['vinyl'] === $before[0][0] && count($snapshot['authors']) === 2, 'complete private source snapshot');

// Every subsequent test transaction is rolled back, preserving rehearsal evidence.
$pdo->beginTransaction();
$pdo->exec("INSERT INTO ENC_ALBUMS(id,title) VALUES(1001,'Fictional shared album'),(1002,'Fictional second album')");
$pdo->exec("INSERT INTO ENC_ARTISTS(id,name,kind) VALUES(1001,'Fictional group','group'),(1002,'Fictional composer','person')");
$pdo->exec("INSERT INTO ENC_ALBUM_ARTISTS VALUES(1001,1001,1,NULL,'primary'),(1001,1002,2,NULL,'guest')");
$pdo->exec("INSERT INTO ENC_TERMS(id,kind,name) VALUES(1001,'genre','Fictional genre A'),(1002,'genre','Fictional genre B'),(1003,'style','Fictional style')");
$pdo->exec('INSERT INTO ENC_ALBUM_TERMS VALUES(1001,1001),(1001,1002),(1001,1003)');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_ALBUM_TERMS WHERE album_id=1001')->fetchColumn() === 3, 'multiple genres and styles');
$pdo->exec("INSERT INTO ENC_EDITIONS(id,album_id,country,barcode) VALUES(1001,1001,'Fictional country A','001234'),(1002,1001,'Fictional country B',NULL),(1003,1002,NULL,NULL)");
$pdo->exec('UPDATE ENC_COPIES SET album_id=1001,edition_id=1001 WHERE vinyl_id IN (101,102)');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE edition_id=1001')->fetchColumn() === 2, 'shared edition with two owners');
$pdo->exec("INSERT INTO VINYLS_TBL(Id,Title,Genres_Id,Format_Id,Condition_Id,Record_Label_Id,Producer,Release_date,Edition_Id,User_Id) VALUES(103,'Fictional second copy',1,1,1,1,'Fictional',2000,1,7)");
$pdo->exec('INSERT INTO ENC_COPIES(vinyl_id,owner_id,album_id,edition_id) VALUES(103,7,1001,1001)');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE owner_id=7 AND edition_id=1001')->fetchColumn() === 2, 'one owner may own multiple copies of an edition');
rejects($pdo, 'UPDATE ENC_COPIES SET owner_id=8 WHERE vinyl_id=101', 'copy owner must match original');
rejects($pdo, 'UPDATE ENC_COPIES SET edition_id=1003 WHERE vinyl_id=101', 'edition must belong to selected album');
rejects($pdo, 'UPDATE ENC_COPIES SET rating=6 WHERE vinyl_id=101', 'rating validated');
rejects($pdo, 'UPDATE ENC_COPIES SET purchase_price=-1,currency="EUR" WHERE vinyl_id=101', 'negative price rejected');
rejects($pdo, 'UPDATE ENC_COPIES SET purchase_price=10 WHERE vinyl_id=101', 'price needs currency');
$pdo->exec("UPDATE ENC_COPIES SET purchase_price=10,currency='EUR',seller='Fictional seller',notes='Private fictional note' WHERE vinyl_id=101");
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE owner_id=8 AND vinyl_id=101')->fetchColumn() === 0, 'owner-scoped copy query excludes another owner');
$pdo->exec("INSERT INTO ENC_MEDIA(id,edition_id,position,format,rpm) VALUES(1001,1001,1,'LP',33.333),(1002,1002,1,'LP',45)");
$pdo->exec("INSERT INTO ENC_WORKS(id,title) VALUES(1001,'Fictional composition')");
$pdo->exec("INSERT INTO ENC_RECORDINGS(id,title) VALUES(1001,'Fictional studio recording'),(1002,'Fictional live recording')");
$pdo->exec('INSERT INTO ENC_RECORDING_WORKS VALUES(1001,1001),(1002,1001)');
$pdo->exec("INSERT INTO ENC_TRACKS(id,medium_id,position,printed_number,side,title,recording_id) VALUES(1001,1001,1,'A1','A','Fictional track',1001),(1002,1002,1,'B1','B','Fictional alternate track',1002)");
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_RECORDING_WORKS WHERE work_id=1001')->fetchColumn() === 2, 'composition distinct from recordings and edition tracks');
rejects($pdo, "INSERT INTO ENC_TRACKS(medium_id,position,title) VALUES(1001,1,'Duplicate')", 'unique position per medium');
$pdo->exec("INSERT INTO ENC_CREDITS(work_id,artist_id,role) VALUES(1001,1002,'composer')");
$pdo->exec("INSERT INTO ENC_CREDITS(album_id,organisation,role) VALUES(1001,'Fictional studio','recording studio')");
rejects($pdo, "INSERT INTO ENC_CREDITS(album_id,work_id,artist_id,role) VALUES(1001,1001,1002,'ambiguous')", 'credit has exactly one target');
$pdo->exec("INSERT INTO ENC_SOURCES(id,provider,entity_type,external_id,fetched_at,license_note) VALUES(1001,'fixture','album','fiction-1',NOW(),'Synthetic test data')");
$pdo->exec("INSERT INTO ENC_ASSERTIONS(album_id,field_name,value_json,author_user_id,status,manually_locked) VALUES(1001,'title','\"Manual fictional title\"',7,'accepted',1)");
$pdo->exec("INSERT INTO ENC_ASSERTIONS(album_id,field_name,value_json,source_id,status) VALUES(1001,'title','\"Different fictional title\"',1001,'conflict')");
check((int)$pdo->query("SELECT COUNT(*) FROM ENC_ASSERTIONS WHERE album_id=1001 AND status='accepted' AND manually_locked=1")->fetchColumn() === 1, 'manual correction retained beside conflicting assertion');
rejects($pdo, "INSERT INTO ENC_ASSERTIONS(album_id,field_name,value_json,source_id) VALUES(1001,'title','invalid JSON',1001)", 'invalid metadata rejected');
$pdo->exec("UPDATE ENC_ALBUMS SET musicbrainz_group_id='00000000-0000-4000-8000-000000000001' WHERE id=1001");
rejects($pdo, "UPDATE ENC_ALBUMS SET musicbrainz_group_id='00000000-0000-4000-8000-000000000001' WHERE id=1002", 'external ID unique');
rejects($pdo, 'DELETE FROM ENC_ALBUMS WHERE id=1001', 'shared album protected against cascade');
rejects($pdo, 'DELETE FROM ENC_EDITIONS WHERE id=1001', 'shared edition protected against cascade');
$pdo->exec("INSERT INTO ENC_PHOTOS(vinyl_id,storage_key,kind,mime_type) VALUES(101,'fiction-only-no-file','cover','image/png')");
$pdo->exec('DELETE FROM VINYLS_TBL WHERE Id=101 AND User_Id=7');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE vinyl_id=101')->fetchColumn() === 0, 'legacy delete removes only private extension');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_PHOTOS WHERE vinyl_id=101')->fetchColumn() === 0, 'photo metadata follows private copy');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_EDITIONS WHERE id=1001')->fetchColumn() === 1, 'edition survives copy deletion');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE owner_id=8')->fetchColumn() === 1, 'other owner survives deletion');
$pdo->rollBack();
check(originals($pdo) === $before, 'rollback restores all original fictional records');

// Force a late migration error without triggers or changes to real data.
$pdo->exec("INSERT INTO VINYLS_TBL(Id,Title,Genres_Id,Format_Id,Condition_Id,Record_Label_Id,Producer,Release_date,Edition_Id,User_Id) VALUES(201,'Fiction rollback A',1,1,1,1,'Fiction',2020,1,7),(202,'Fiction rollback B',1,1,1,1,'Fiction',2020,1,7)");
$pdo->exec("INSERT INTO ENC_ALBUMS(title,draft_owner_id,legacy_vinyl_id) VALUES('Fiction conflict',7,202)");
try { LegacyEncyclopediaMigration::run($pdo); throw new LogicException('Expected unique key failure'); }
catch (PDOException) {
    check((int)$pdo->query('SELECT COUNT(*) FROM ENC_COPIES WHERE vinyl_id IN (201,202)')->fetchColumn() === 0, 'migration rolls back earlier inserted copies');
    check((int)$pdo->query('SELECT COUNT(*) FROM ENC_ALBUMS WHERE legacy_vinyl_id=201')->fetchColumn() === 0, 'migration rolls back earlier inserted album');
}
// Remove exactly the synthetic records created here so old regression counts apply.
$pdo->exec('DELETE FROM ENC_COPIES WHERE vinyl_id IN (101,102)');
$pdo->exec('DELETE FROM ENC_ALBUMS WHERE legacy_vinyl_id IN (101,102,202)');
$pdo->exec('DELETE FROM VINYLS_TBL WHERE Id IN (101,102,201,202)');
$pdo->exec('DELETE FROM AUTHORS_TBL WHERE Id IN (101,102)');
// Existing regression tests assume the first inserted vinyl has Id 1.
// Explicit fixture-only counter reset is performed by the runner, not this service.
echo 'OK: ' . $count . " encyclopedia database checks; fictional data only.\n";
