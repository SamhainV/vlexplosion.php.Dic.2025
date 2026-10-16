<?php
declare(strict_types=1);
namespace App\Services\Metadata;

/** Lossless direct-edge representation, independently of UI role classification. */
final class MusicBrainzCreditGraph
{
    public const GROUPS = [
        'performance'=>'Músicos e intérpretes','production'=>'Producción','sound'=>'Grabación, mezcla y mastering',
        'composition'=>'Composición y arreglos','places'=>'Estudios e instalaciones','artwork'=>'Diseño, fotografía y artwork',
        'manufacturing'=>'Fabricación y prensado','rights'=>'Discográficas, editoriales y derechos','other'=>'Otros créditos',
    ];
    public static function category(string $role, string $target=''): string
    {
        $r=strtolower($role);
        if($target==='place')return str_contains($r,'press')||str_contains($r,'manufactur')?'manufacturing':'places';
        if($target==='work'&&$r==='performance')return 'composition';
        if(in_array($target,['recording','release','release-group','series','event','area','url','instrument'],true))return 'other';
        foreach (['manufacturing'=>['manufactur','press','printed','printing','lacquer cutting company'],
          'rights'=>['copyright','phonographic','publisher','publishing','distribut','license','licenc','label'],
          'artwork'=>['artwork','art direction','design','illustrat','photograph','liner notes','booklet'],
          'composition'=>['compos','lyric','writer','arrang','orchestrat','adapt','translat','librett'],
          'sound'=>['engineer','recorded','mix','master','lacquer','edit','transfer','sound','technical'],
          'production'=>['produc','coordination','artists and repertoire'],
          'performance'=>['artist credit','perform','vocal','instrument','conductor','chorus','programming','orchestra','concertmaster']] as $category=>$words) {
            foreach($words as $word) if(str_contains($r,$word)) return $category;
        }
        return 'other';
    }
    /** A lossless relationship is not necessarily a credit for a participant. */
    public static function isContext(array $relation): bool
    {
        return !empty($relation['target_kind'])&&!in_array($relation['target_kind'],['artist','label','place'],true);
    }
    public static function canonical(mixed $data): mixed
    {
        if (!is_array($data)) return $data;
        if (!array_is_list($data)) ksort($data);
        foreach ($data as &$value) $value=self::canonical($value);
        return $data;
    }
    /** Shared import/synchronization identity. Display names are deliberately excluded. */
    public static function identityKey(string $kind,string $source,string $targetKind,string $id,array $relation): string
    {
        $identity=$relation;unset($identity[$targetKind]);
        if(isset($identity['attributes'])&&is_array($identity['attributes']))sort($identity['attributes']);
        return hash('sha256',json_encode(self::canonical([$kind,$source,$targetKind,$id,$identity]),JSON_THROW_ON_ERROR));
    }
    /** Display projection only. All attributes, including unknown qualifiers, stay in raw. */
    public static function instrumentAttributes(string $role,array $attributes): array
    {
        if(!in_array(strtolower($role),['instrument','vocal'],true))return [];
        return array_values(array_filter($attributes,static fn($a)=>!in_array(strtolower($a),['additional','guest','solo'],true)));
    }
    public static function relations(string $kind, array $response, array $enrichment=[]): array
    {
        $data=$response['data'];$source=MusicBrainzNormalizer::id($data['id']??null);$rows=[];
        foreach ($data['relations']??[] as $rel) {
            if (!is_array($rel) || !is_string($rel['type']??null) || !is_string($rel['target-type']??null)) throw new MetadataException('invalid_response');
            if(!is_array($rel['attributes']??[])||!array_is_list($rel['attributes']??[]))throw new MetadataException('invalid_response');
            foreach($rel['attributes']??[] as $a)if(!is_string($a))throw new MetadataException('invalid_response');
            foreach(['attribute-ids','attribute-values','attribute-credits'] as $map){
                if(!is_array($rel[$map]??[]))throw new MetadataException('invalid_response');
                foreach($rel[$map]??[] as $value)if(!is_string($value))throw new MetadataException('invalid_response');
            }
            foreach(['target-credit','source-credit','begin','end'] as $field)if(isset($rel[$field])&&!is_string($rel[$field]))throw new MetadataException('invalid_response');
            $targetKind=$rel['target-type'];$target=$rel[$targetKind]??null;
            if (!is_array($target) || !is_string($target['id']??null)) throw new MetadataException('invalid_response');
            $id=MusicBrainzNormalizer::id($target['id']);
            $name=$target['name']??$target['title']??$target['resource']??$id;
            if (!is_string($name) || !preg_match('//u',$name) || strlen($rel['type'])>480 || str_contains($name,"\0")) throw new MetadataException('invalid_response');
            $key=self::identityKey($kind,$source,$targetKind,$id,$rel);
            $rows[$key]=['key'=>$key,'source_kind'=>$kind,'source_mbid'=>$source,'target_kind'=>$targetKind,'target_mbid'=>$id,
              'name'=>$name,'role'=>$rel['type'],'category'=>self::category($rel['type'],$targetKind),
              'credited_as'=>$rel['target-credit']??null,'source_credit'=>$rel['source-credit']??null,
              'attributes'=>$rel['attributes']??[],'attribute_ids'=>$rel['attribute-ids']??[],
              'attribute_values'=>$rel['attribute-values']??[],'attribute_credits'=>$rel['attribute-credits']??[],
              'begin'=>$rel['begin']??null,'end'=>$rel['end']??null,'ended'=>$rel['ended']??null,
              'direction'=>$rel['direction']??null,'type_id'=>$rel['type-id']??null,'relationship_id'=>$rel['id']??null,
              'raw'=>$rel,'target_details'=>$enrichment[$targetKind.'/'.$id]['data']??$target,
              'source_url'=>'https://musicbrainz.org/'.$kind.'/'.$source,'fetched_at'=>(int)$response['fetched_at']];
        }
        return array_values($rows);
    }
}
