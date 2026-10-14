<?php
require __DIR__.'/connect.php';
$m=\App\Services\Metadata\MusicBrainzNormalizer::release(json_decode(file_get_contents(__DIR__.'/reference/enjoy-release.json'),true),json_decode(file_get_contents(__DIR__.'/reference/enjoy-group.json'),true));
echo \App\Services\EncyclopediaLink::apply((int)$argv[1],7,$m,$argv[2],$argv[3]);
