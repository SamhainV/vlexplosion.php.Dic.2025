<?php
require __DIR__.'/connect.php';
echo \App\Services\EncyclopediaManual::save((int)$argv[1],7,'private','album',['title'=>'Concurrent private musical correction'],(int)$argv[2],$argv[3]);
