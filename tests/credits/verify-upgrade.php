<?php
declare(strict_types=1);
require dirname(__DIR__).'/encyclopedia/connect.php';
$before=json_decode(file_get_contents(getenv('VLEX_ENC_RUNTIME').'/legacy-before.json'),true,128,JSON_THROW_ON_ERROR);$checks=0;
foreach($before['tables'] as $table=>$expected){
 $rows=$pdo->query('SELECT * FROM `'.$table.'`')->fetchAll();
 foreach($rows as &$row)if($table==='ENC_CREDITS')foreach(['relation_key','relation_json','context_edition_id','sync_active','seen_at'] as $column)unset($row[$column]);unset($row);
 usort($rows,fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));
 if($rows!==$expected)throw new RuntimeException('006 changed original rows in '.$table);$checks++;
}
$after=$pdo->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION")->fetchAll();
foreach($before['columns'] as $column){if(!in_array($column,$after,true))throw new RuntimeException('Original column properties changed');$checks++;}
if((int)$pdo->query('SELECT COUNT(*) FROM ENC_CREDIT_JOBS')->fetchColumn()!==0)throw new RuntimeException('Migration invented a job');
$instrument=array_values(array_filter($after,fn($c)=>$c['TABLE_NAME']==='ENC_CREDITS'&&$c['COLUMN_NAME']==='instrument'))[0];
if($instrument['COLUMN_TYPE']!=='varchar(120)'||$instrument['IS_NULLABLE']!=='YES')throw new RuntimeException('005 instrument changed');
file_put_contents(dirname(__DIR__,2).'/docs/credits/upgrade-checks.json',json_encode(['checks'=>$checks,'stable_base'=>'b0d935ed5fad6966bcff6c005d6b8af9d8ae59b7','old_tables'=>count($before['tables']),'preserved_columns'=>count($before['columns']),'original_rows_identical'=>true,'instrument_005_unchanged'=>true],JSON_PRETTY_PRINT)."\n");
echo 'PASS '.$checks." upgrade checks: every original table row and column property identical after isolated 006.\n";
