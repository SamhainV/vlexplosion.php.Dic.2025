<?php
declare(strict_types=1);
require __DIR__ . '/connect.php';
require __DIR__ . '/fixtures.php';
[$g, $r] = encyclopedia_fixture_data();
$g['id'] = encyclopedia_fixture_id(801); $r['id'] = encyclopedia_fixture_id(802); $r['release-group'] = $g;
$metadata = \App\Services\Metadata\MusicBrainzNormalizer::release(['data' => $r, 'fetched_at' => 1700000000], ['data' => $g, 'fetched_at' => 1700000000]);
$id = \App\Services\EncyclopediaCollection::create(7, ['title' => 'Concurrent fictional import', 'authors' => ['Fictional band'], 'release_date' => '1984'], $metadata, $argv[1]);
echo $id;
