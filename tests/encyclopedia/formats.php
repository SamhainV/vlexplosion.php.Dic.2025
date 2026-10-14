<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/Core/HttpException.php';
require dirname(__DIR__,2).'/app/Core/ReleaseFormat.php';
use App\Core\ReleaseFormat;
$n=0;function check(bool $v):void {global $n;if(!$v)throw new RuntimeException('Format contract failed');$n++;}
$cd=['id'=>'cd','title'=>'Vinyl title cannot establish format','media'=>[['format'=>'CD']]];
$vinyl=['id'=>'lp','media'=>[['format'=>'12" Vinyl']]];
$unknown=['id'=>'unknown','media'=>[['format'=>null]]];
$mixed=['id'=>'mixed','media'=>[['format'=>'CD'],['format'=>'7" Vinyl']]];
check(!ReleaseFormat::vinyl($cd['media']));check(ReleaseFormat::vinyl($vinyl['media']));check(!ReleaseFormat::vinyl($unknown['media']));check(ReleaseFormat::vinyl($mixed['media']));
check(ReleaseFormat::label($mixed['media'])==='CD + 7" Vinyl');check(ReleaseFormat::label([])==='Formato desconocido');
$r=['entity_type'=>'release','candidates'=>[$cd,$unknown,$vinyl,$mixed],'offset'=>25,'total'=>90];
$f=ReleaseFormat::filter($r,'vinyl');check(array_column($f['candidates'],'id')===['lp','mixed']);check($f['page_count']===4&&$f['offset']===25&&$f['total']===90);
check(array_column(ReleaseFormat::filter($r,'prefer')['candidates'],'id')===['lp','mixed','cd','unknown']);
check(ReleaseFormat::filter($r,'all')['candidates']===$r['candidates']);
check(ReleaseFormat::filter(['entity_type'=>'release-group','candidates'=>[$cd]],'vinyl')['candidates']===[$cd]);
foreach (['invalid',[],null] as $v) {try{ReleaseFormat::preference($v);throw new RuntimeException('Invalid preference accepted');}catch(App\Core\HttpException $e){check($e->status===422);}}
echo "OK: $n format checks; zero external requests.\n";
