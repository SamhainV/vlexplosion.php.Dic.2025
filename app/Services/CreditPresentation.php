<?php
declare(strict_types=1);
namespace App\Services;
use App\Services\Metadata\MusicBrainzCreditGraph as Graph;
/** Read-only display projection: never mutates or deduplicates stored relationships. */
final class CreditPresentation
{
 public static function role(string $role,array $attributes=[]):string
 {
  if($role==='vocal'&&in_array('lead vocals',$attributes,true))return 'Voz principal';
  return ['performer'=>'Intérprete','artist credit'=>'Artista acreditado','producer'=>'Productor','vocal'=>'Voz','mix'=>'Mezcla','mixing'=>'Mezcla','mastering'=>'Masterización','engineer'=>'Ingeniero de sonido','composer'=>'Compositor','lyricist'=>'Letrista','arranger'=>'Arreglista','instrument'=>'Instrumentista','publishing'=>'Editorial de la obra','phonographic copyright'=>'Derechos fonográficos','copyright'=>'Derechos de autor','recording'=>'Grabación','photography'=>'Fotografía','design/illustration'=>'Diseño e ilustración','design'=>'Diseño','illustration'=>'Ilustración','artwork'=>'Diseño gráfico','manufactured by'=>'Fabricación','pressed by'=>'Prensado','recorded at'=>'Grabado en','mixed at'=>'Mezclado en','mastered at'=>'Masterizado en'][$role]??$role;
 }
 public static function songs(int $n):string{return 'Ver '.$n.($n===1?' canción relacionada':' canciones relacionadas');}
 public static function scope(string $scope):string{return ['release-group'=>'Álbum / grupo de ediciones','release'=>'Edición concreta','track'=>'Pista','recording'=>'Grabación','work'=>'Obra / composición'][$scope]??'Ámbito sin precisar';}
 public static function build(array $rows,array $tracks=[],array $works=[],array $recordings=[],?array $edition=null,?array $album=null):array
 {
  $sections=array_fill_keys(array_keys(Graph::GROUPS),[]);$perTrack=[];$context=[];$identities=[];$count=0;$rich=0;$unidentified=0;$review=0;
  foreach($rows as $index=>$c){
   $rel=$c['relation']??(!empty($c['relation_json'])?json_decode($c['relation_json'],true):null);$c['details']=$rel;$c['category']=$rel['category']??($c['role']==='artist credit'?'performance':Graph::category($c['role'],$rel['target_kind']??''));
   $c['scope']=$rel['source_kind']??(!empty($c['album_id'])?'release-group':(!empty($c['edition_id'])?'release':(!empty($c['track_id'])?'track':(!empty($c['recording_id'])?'recording':(!empty($c['work_id'])?'work':'unknown')))));
   $c['contexts']=[];$c['warnings']=[];
   if($rel&&Graph::isContext($rel)){$context[]=$c;continue;}
   $count++;if(!empty($c['relation_key']))$rich++;
   $valid=null;
   if($rel&&$edition!==null){$valid=match($c['scope']){'recording'=>in_array($rel['source_mbid'],array_column($recordings,'musicbrainz_id'),true),'work'=>in_array($rel['source_mbid'],array_column($works,'musicbrainz_id'),true),'release'=>$rel['source_mbid']===($edition['musicbrainz_release_id']??null),'release-group'=>$rel['source_mbid']===($album['musicbrainz_group_id']??null),default=>false};}
   if($valid===false)$c['warnings'][]='Ámbito por revisar: el origen no coincide con la edición, sus grabaciones u obras. No se atribuye a sus canciones.';
   if($c['scope']==='work'&&$c['category']==='rights')$c['warnings'][]='Editorial de la obra; su vigencia en esta edición no está verificada.';
   $year=substr($edition['release_date']??'',0,4);$begin=substr($rel['begin']??'',0,4);
   if(ctype_digit($year)&&ctype_digit($begin)&&(int)$begin>(int)$year){$c['warnings'][]='Revisar para esta edición: la relación comienza en '.$begin.', después de la fecha de edición ('.$year.').';$review++;}
   if($valid!==false){foreach($tracks as $t){
    $match=(!empty($c['track_id'])&&(int)$c['track_id']===(int)$t['id'])||(!empty($c['recording_id'])&&(int)$c['recording_id']===(int)$t['recording_id']);
    if(!empty($c['work_id']))foreach($works as $w)if((int)$w['id']===(int)$c['work_id']&&(int)($w['recording_id']??0)===(int)$t['recording_id'])$match=true;
    if($match){$c['contexts'][$t['id']]=$t['title'];}
   }}
   if(!$c['contexts']&&!empty($c['context'])&&!$tracks)$c['contexts'][]=$c['context'];
   foreach($c['contexts'] as $tid=>$title)if($tracks)$perTrack[$tid][]=$c;
   $identity=!empty($c['artist_id'])?'artist:'.$c['artist_id']:(!empty($rel['target_mbid'])?($rel['target_kind'].':'.$rel['target_mbid']):null);
   if($identity)$identities[$identity]=true;else{$identity='row:'.($c['id']??$index);$unidentified++;}
   $attrs=$rel['attributes']??[];sort($attrs);$key=json_encode(Graph::canonical([$identity,$c['role'],$rel['type_id']??null,$c['instrument']??null,$c['credited_as']??null,$attrs,$rel['attribute_credits']??[],$c['scope']]),JSON_THROW_ON_ERROR);
   if(!isset($sections[$c['category']]))$c['category']='other';
   $g=&$sections[$c['category']][$key];if(!$g)$g=['sample'=>$c,'entries'=>[],'contexts'=>[],'warnings'=>[]];
   $g['entries'][]=$c;$g['contexts']+=$c['contexts'];foreach($c['warnings'] as $w)$g['warnings'][$w]=true;unset($g);
  }
  return ['sections'=>$sections,'per_track'=>$perTrack,'context'=>$context,'entries'=>$count,'rich'=>$rich,'previous'=>$count-$rich,'identities'=>count($identities),'unidentified'=>$unidentified,'cards'=>array_sum(array_map('count',$sections)),'dated_review'=>$review];
 }
}
