<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/encyclopedia/fixtures.php';
function credit_fixtures(bool $remaster=false): array {
 [$g,$r]=encyclopedia_fixture_data();
 $id=fn($n)=>encyclopedia_fixture_id($n);
 $artist=['id'=>$id(30),'name'=>'Fictional multi-role musician','type'=>'Person'];
 $label=['id'=>$id(31),'name'=>'Fictional rights company','type'=>'Original Production'];
 $place=['id'=>$id(32),'name'=>'Fictional studio','type'=>'Studio','address'=>'Fictional address, not a real person','area'=>['id'=>$id(33),'name'=>'Fictional city']];
 $edge=fn($role,$type,$entity,$attrs=[])=>['type'=>$role,'type-id'=>$id(100+strlen($role)),'direction'=>'backward','target-type'=>$type,$type=>$entity,'attributes'=>$attrs,'attribute-ids'=>array_fill_keys($attrs,$id(500)),'attribute-credits'=>array_fill_keys($attrs,'Custom instrument credit'),'begin'=>'1984-01','end'=>'1984-06','ended'=>true,'source-credit'=>'Printed album alias','target-credit'=>$entity['name']??$entity['title']??null];
 $g['relations']=[$edge('producer','artist',$artist,['executive']),$edge('unknown future role','series',['id'=>$id(34),'name'=>'Fictional unknown collection'])];
 $r['relations']=[$edge('design','artist',$artist),$edge('engineer','artist',$artist,['additional','assistant']),$edge('manufactured','label',$label),$edge('phonographic copyright','label',$label),$edge('recorded at','place',$place)];
 if($remaster){$r['id']=$id(22);$r['relations'][]=$edge('mastering','artist',['id'=>$id(35),'name'=>'Fictional remaster engineer','type'=>'Person'],['remaster']);}
 $rec=$r['media'][0]['tracks'][0]['recording'];
 $work=['id'=>$id(7),'title'=>'Fictional work','relations'=>[$edge('composer','artist',$artist),$edge('publisher','label',$label)]];
 $rec['relations']=[$edge('instrument','artist',$artist,['guest','guitar','solo']),$edge('performance','work',$work),$edge('mixed at','place',$place)];
 $rec['relations'][0]['attribute-values']=['guest'=>'Special session'];
 $r['media'][0]['tracks'][0]['recording']=$rec;
 $second=$r['media'][0]['tracks'][0];$second['position']=2;$second['number']='B1';$second['title']='Fictional second song';$second['recording']=['id'=>$id(8),'title'=>'Fictional second song','length'=>200000,'relations'=>[]];$r['media'][0]['tracks'][]=$second;$r['media'][0]['track-count']=count($r['media'][0]['tracks']);
 return ['release/'.$r['id']=>$r,'release-group/'.$g['id']=>$g,'recording/'.$rec['id']=>$rec,'recording/'.$id(8)=>$second['recording'],'work/'.$work['id']=>$work,'place/'.$place['id']=>$place,'label/'.$label['id']=>$label];
}
