<?php
declare(strict_types=1);
require __DIR__ . '/connect.php';
require __DIR__ . '/fixtures.php';
$_ENV['VLEX_ENC_ENABLED'] = '1';
use App\Services\Metadata\MusicBrainzNormalizer;
use App\Services\EncyclopediaCollection;
use App\Models\EncyclopediaRecord;
use App\Core\HttpException;

$count = 0;
function check(bool $ok, string $label): void { global $count; if (!$ok) { throw new RuntimeException('FAIL: ' . $label); } $count++; }
[$g, $r] = encyclopedia_fixture_data();
$m = MusicBrainzNormalizer::release(['data' => $r, 'fetched_at' => time()], ['data' => $g, 'fetched_at' => time()]);
$data = ['title' => 'My fictional correction', 'authors' => ['Fictional band'], 'producer' => '', 'release_date' => '1984'];
$key = bin2hex(random_bytes(32));
$id = EncyclopediaCollection::create(7, $data, $m, $key, ['notes' => 'Private user seven']);
check(EncyclopediaCollection::create(7, $data, $m, $key, ['notes' => 'Private user seven']) === $id, 'idempotent retry');
$record = EncyclopediaRecord::forCopy($id, 7);
check($record['vinyl']['Title'] === 'My fictional correction' && $record['album']['title'] === 'Fictional album', 'personal correction does not overwrite shared title');
check(count($record['tracks']) === 1 && count($record['credits']) >= 3, 'track, recording, composition and credits persist');
check(count($record['genres']) === 1 && count($record['labels']) === 1, 'genres and label persist');
check(EncyclopediaRecord::forCopy($id, 8) === null, 'foreign personal copy denied');
$id2 = EncyclopediaCollection::create(8, $data, $m, bin2hex(random_bytes(32)));
$other = EncyclopediaRecord::forCopy($id2, 8);
check($other['album']['id'] === $record['album']['id'] && $other['edition']['id'] === $record['edition']['id'], 'identities reused across owners');
check($other['copy']['notes'] === null, 'private notes not shared');
check((int)$pdo->query('SELECT COUNT(*) FROM ENC_EDITIONS WHERE musicbrainz_release_id="' . $r['id'] . '"')->fetchColumn() === 1, 'one edition for repeated imports');
try { EncyclopediaCollection::create(7, $data + ['is_favorite' => 'on'], $m, $key, ['notes' => 'Private user seven']); throw new RuntimeException('Key conflict accepted'); }
catch (HttpException $e) { check($e->status === 409, 'changed payload rejected'); }
$before = (int)$pdo->query('SELECT COUNT(*) FROM ENC_ALBUMS')->fetchColumn();
$bad = $m; $bad['album']['id'] = encyclopedia_fixture_id(101); $bad['edition']['id'] = encyclopedia_fixture_id(102);
try { EncyclopediaCollection::create(7, ['title' => 'HTTP rollback upload'] + $data, $bad, bin2hex(random_bytes(32))); throw new RuntimeException('Rollback trigger not fired'); }
catch (PDOException) { check((int)$pdo->query('SELECT COUNT(*) FROM ENC_ALBUMS')->fetchColumn() === $before, 'late SQL failure rolls back imported graph'); }
$manual = EncyclopediaCollection::create(7, ['title' => 'Manual fictional'] + $data);
$manualRecord = EncyclopediaRecord::forCopy($manual, 7);
check($manualRecord['edition'] === null && (int)$manualRecord['album']['draft_owner_id'] === 7, 'manual uses same writer with private draft and unknown edition');
try { MusicBrainzNormalizer::date('2023-02-29'); throw new RuntimeException('Invalid date accepted'); }
catch (\App\Services\Metadata\MetadataException) { check(true, 'invalid date rejected'); }
check(MusicBrainzNormalizer::date('1984') === '1984' && MusicBrainzNormalizer::date(null) === null, 'date precision and unknown preserved');
$m2 = $m; $m2['album']['title'] = 'New conflicting remote title';
EncyclopediaCollection::create(7, $data, $m2);
check(EncyclopediaRecord::forCopy($id, 7)['album']['title'] === 'Fictional album', 'refresh cannot overwrite existing title');
check((int)$pdo->query("SELECT COUNT(*) FROM ENC_ASSERTIONS WHERE status='conflict'")->fetchColumn() > 0, 'conflict stored for review');
$parallelKey = bin2hex(random_bytes(32)); $workers = [];
for ($i=0; $i<2; $i++) {
    $process = proc_open([PHP_BINARY, __DIR__ . '/concurrent.php', $parallelKey], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    $workers[] = [$process, $pipes];
}
$ids = [];
foreach ($workers as [$process, $pipes]) {
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    check(proc_close($process) === 0 && $error === '' && ctype_digit($output), 'parallel import completes'); $ids[] = (int)$output;
}
check($ids[0] === $ids[1], 'concurrent confirmation produces exactly one copy');
$proposal = \App\Services\Recognition\PhotoSearchProposal::confirmedQuery(['artist' => 'Fictional', 'title' => 'Candidate', 'release_id' => 'untrusted', 'year' => 1999], true);
check($proposal === ['operation'=>'search','artist'=>'Fictional','title'=>'Candidate'], 'visual proposal strips unverified metadata and identifiers');
try { \App\Services\Recognition\PhotoSearchProposal::confirmedQuery(['title'=>'Candidate'], false); throw new RuntimeException('Unconfirmed visual proposal accepted'); }
catch (HttpException $error) { check($error->status === 422, 'visual candidate requires confirmation'); }
check(!\App\Services\Recognition\PhotoSearchProposal::available(), 'paid visual provider remains disabled');
// Public cached MusicBrainz response; test users/copies exist only in this new socket DB.
$referenceRelease=json_decode(file_get_contents(__DIR__.'/reference/enjoy-release.json'),true);
$referenceGroup=json_decode(file_get_contents(__DIR__.'/reference/enjoy-group.json'),true);
$enjoy=MusicBrainzNormalizer::release($referenceRelease,$referenceGroup);
$import=EncyclopediaCollection::create(7,['title'=>$enjoy['album']['title'],'authors'=>['Depeche Mode'],'producer'=>'','release_date'=>'1992'],$enjoy,null,['country'=>'US','catalogue_number'=>'9 18890-2','notes'=>'PRIVATE_REFERENCE_NOTE']);
$record=EncyclopediaRecord::forCopy($import,7);
check(count($record['tracks'])===2&&count($record['credits'])===20,'authentic public Enjoy response retains two tracks and twenty credits');
check(count($record['labels'])===3&&$record['media'][0]['format']==='CD','three labels and confirmed CD support persist');
$sharedTables=['ENC_ALBUMS','ENC_EDITIONS','ENC_MEDIA','ENC_TRACKS','ENC_RECORDINGS','ENC_WORKS','ENC_CREDITS','ENC_EDITION_LABELS','ENC_ARTISTS'];
$shared=[];foreach($sharedTables as $t)$shared[$t]=$pdo->query('SELECT * FROM '.$t)->fetchAll();
$input=['title'=>'Personal Enjoy correction','authors'=>['My private artist correction'],'producer'=>'My producer correction','release_date'=>'1993'];
$validated=App\Core\VinylInput::validate($input,null,true);$validated['image_path']=null;
$validated['enc_personal']=['notes'=>'Updated private notes','country'=>'Personal country','catalogue_number'=>'Personal catalogue'];
check(App\Models\Vinyl::updateForUser($import,7,$validated),'personal metadata edits save transactionally');
$updated=EncyclopediaRecord::forCopy($import,7);$personal=json_decode($updated['copy']['overrides_json'],true);
check($personal['country']==='Personal country'&&$personal['catalogue_number']==='Personal catalogue'&&$updated['copy']['notes']==='Updated private notes','private overrides persist separately');
foreach($sharedTables as $t)check($shared[$t]===$pdo->query('SELECT * FROM '.$t)->fetchAll(),'shared '.$t.' unaffected by edit');
check($updated['edition']['country']==='US'&&$updated['labels'][0]['catalogue_number']==='9 18890-2','personal changes do not alter source country/catalogue');
$producer=$pdo->query("SELECT id FROM ENC_ARTISTS WHERE name='Flood'")->fetchColumn();
$composer=$pdo->query("SELECT id FROM ENC_ARTISTS WHERE name='Martin L. Gore'")->fetchColumn();
foreach([$producer,$composer] as $artist) {
 $page=App\Models\EncyclopediaEntity::find('artist',(int)$artist,7);
 check(in_array($enjoy['album']['title'],array_column($page['albums'],'title'),true),'credit-only artist navigates to related album');
}
$privateAlbum=$pdo->prepare('INSERT INTO ENC_ALBUMS(title,draft_owner_id) VALUES(?,8)');$privateAlbum->execute(['SECRET_OTHER_DRAFT']);$privateId=(int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO ENC_CREDITS(album_id,artist_id,role) VALUES(?,?,'producer')")->execute([$privateId,$producer]);
check(!in_array('SECRET_OTHER_DRAFT',array_column(App\Models\EncyclopediaEntity::find('artist',(int)$producer,7)['albums'],'title'),true),'credit navigation never reveals another owner private draft');
check(!App\Models\Vinyl::updateForUser($import,8,$validated),'foreign owner cannot change personal metadata');
echo 'OK: ' . $count . " new collection checks; fictional metadata, real isolated MariaDB.\n";
