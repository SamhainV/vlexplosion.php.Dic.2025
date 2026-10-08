<?php
declare(strict_types=1);
namespace App\Models;
final class Vinyl
{
 public static function allowedSorts():array {return ['newest'=>'Nuevos primero','title_asc'=>'Título A → Z'];}
 public static function record(int $id):?array {
  if($id<1 || $id>6)return null;
  $base='http://'.$_SERVER['HTTP_HOST'];
  $images=[1=>$base.'/__fixture/existing.png',2=>null,3=>'uploads/covers/missing-copy.jpg',4=>'/uploads/covers/missing-copy.jpg',5=>$base.'/broken-cover.webp',6=>null];
  return ['Id'=>$id,'Title'=>$id===6?str_repeat('A',65):'The Quiet Sessions','author_name'=>'Clara Rivers, The Night Ensemble','authors'=>[['Id'=>1,'Author_Name'=>'Clara Rivers'],['Id'=>2,'Author_Name'=>'The Night Ensemble']],'Release_date'=>'2020','Producer'=>'Clara Rivers','Genres_Id'=>1,'Format_Id'=>1,'Condition_Id'=>1,'Record_Label_Id'=>1,'Edition_Id'=>1,'genre_name'=>'Jazz','format_name'=>'LP · 12 pulgadas','condition_name'=>'Muy bueno','record_label_name'=>'Green Room Records','edition_name'=>'Primera edición','Is_Favorite'=>1,'Is_Desired'=>1,'Image_Path'=>$images[$id]];
 }
 public static function findByIdForUser(int $id,int $user):?array{return $user===7?self::record($id):null;}
 public static function countByUser(int $user,array $filters=[]):int{return 5;}
 public static function paginateByUser(int $user,int $limit,int $offset,string $sort,array $filters=[]):array{return array_slice(array_map([self::class,'record'],range(1,5)),$offset,$limit);}
 public static function listGenres():array{return [['id'=>1,'name'=>'Jazz']];}
 public static function listFormats():array{return [['id'=>1,'name'=>'LP · 12 pulgadas']];}
 public static function listConditions():array{return [['id'=>1,'name'=>'Muy bueno']];}
 public static function listRecordLabels():array{return [['id'=>1,'name'=>'Green Room Records']];}
 public static function listEditions():array{return [['id'=>1,'name'=>'Primera edición']];}
}
