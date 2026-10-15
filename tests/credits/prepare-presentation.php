<?php
require dirname(__DIR__).'/encyclopedia/connect.php';
// Fictional fixture only: reproduce the pre-006 import's actual "performer" role.
$s=json_decode(file_get_contents(getenv('VLEX_ENC_RUNTIME').'/scope-states.json'),true);
$r=App\Models\EncyclopediaRecord::forCopy((int)$s['copy'],7);
$ids=array_column($r['tracks'],'id');if(count($ids)!==2)throw new RuntimeException('Expected two fictional tracks');
$q=implode(',',array_fill(0,count($ids),'?'));$pdo->prepare("UPDATE ENC_CREDITS SET role='performer' WHERE role='artist credit' AND track_id IN ($q) AND relation_key IS NULL")->execute($ids);
// Reproduce the separate pending operation explicitly in this fictional fixture only.
App\Services\EncyclopediaCreditSync::start((int)$s['copy'],7);
echo "Fictional legacy performer fixture matches the audited historical importer.\n";
