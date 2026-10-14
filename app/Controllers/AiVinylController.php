<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Auth,AiFeature,Controller,Csrf,HttpException,Input,VinylInput};
use App\Models\{AlbumCollection,Vinyl};
use App\Services\Recognition\MusicMetadata;
final class AiVinylController extends Controller {
 private function json(array $value,int $status=200):never{http_response_code($status);header('Content-Type: application/json');header('Cache-Control: no-store');echo json_encode($value,JSON_THROW_ON_ERROR);exit;}
 private function body():array{
  if((int)($_SERVER['CONTENT_LENGTH']??0)>2900000)throw new HttpException(413);
  return $_POST; // Router has already verified the application's unchanged CSRF guard.
 }
 public function start():void{
  Auth::requireLogin();
  if (!AiFeature::enabled()) { $this->view('ai/unavailable'); return; }
  AiFeature::requireEnabled();$_SESSION['ai_draft']=['key'=>bin2hex(random_bytes(32)),'user'=>Auth::id(),'verified'=>[],'expires'=>time()+3600];
  $this->view('ai/create',['draft'=>$_SESSION['ai_draft'],'catalogues'=>['genre_id'=>Vinyl::listGenres(),'format_id'=>Vinyl::listFormats(),'condition_id'=>Vinyl::listConditions(),'record_label_id'=>Vinyl::listRecordLabels(),'edition_id'=>Vinyl::listEditions()]]);
 }
 public function api():void{
  AiFeature::requireEnabled();try{
   $in=$this->body();$op=Input::text($in['operation']??'',20);$draft=$_SESSION['ai_draft']??[];
   if(($draft['user']??null)!==Auth::id()||($draft['expires']??0)<time())throw new HttpException(422,'La vista previa ha caducado. Abre una nueva alta.');
   if($op==='models')$this->json(['models'=>AiFeature::provider()->models()]);
   if($op==='recognize'){
    unset($_SESSION['ai_draft']['selected']);$_SESSION['ai_draft']['verified']=[];
    if(($in['consent']??'')!=='1')throw new HttpException(422,'Autoriza el envío de la fotografía a OpenAI.');AiFeature::limit();$start=microtime(true);$result=AiFeature::provider()->recognize(Input::text($in['model']??'',100),$in['image']??'');$this->json($result+['processing_ms'=>(int)round((microtime(true)-$start)*1000),'simulated'=>getenv('VLEX_AI_PROVIDER')==='fixture']);
   }
   if($op==='verify'){
    $result=MusicMetadata::search($in);$_SESSION['ai_draft']['verified']=$result['candidates'];$this->json($result);
   }
   if($op==='select'){
    $index=Input::integer($in['index']??null);$c=$_SESSION['ai_draft']['verified'][$index]??null;if(!$c)throw new HttpException(422,'Selecciona un resultado consultado en MusicBrainz.');$_SESSION['ai_draft']['selected']=$c;
    $this->json(['selected'=>$c,'copies'=>AlbumCollection::copies(Auth::id(),$c['id'],null,['title'=>$c['title']])]);
   }
   if($op==='existing'){
    $album=AlbumCollection::album(Input::integer($in['album_id']??null,1),Auth::id());if(!$album)throw new HttpException(404);$this->json(['album'=>$album,'copies'=>AlbumCollection::copies(Auth::id(),null,(int)$album['Id']),'releases'=>AlbumCollection::releases((int)$album['Id'],Auth::id())]);
   }
   throw new HttpException(404);
  }catch(HttpException $e){$this->json(['error'=>$e->getMessage()],$e->status);}catch(\Throwable $e){$this->json(['error'=>'El servicio no pudo completar la operación. Se conserva la fotografía; puedes reintentar o continuar manualmente.'],502);}
 }
 public function store():void{
  AiFeature::requireEnabled();try{
   if(($_POST['confirm']??'')!=='on')throw new HttpException(422,'Confirma expresamente antes de guardar.');$draft=$_SESSION['ai_draft']??[];$key=Input::text($_POST['request_key']??'',64);
   if(($draft['user']??null)!==Auth::id()||($draft['expires']??0)<time()||!hash_equals($draft['key']??'',$key))throw new HttpException(422,'La confirmación ha caducado. Abre una nueva alta.');
   $input=$_POST;$input['authors']=preg_split('/\r?\n/',Input::text($_POST['artists']??'',1100));$data=VinylInput::validate($input,null,true);$data['notes']=Input::text($_POST['notes']??'',2000)?:null;
   VinylInput::validateCatalogues($data,['genre_id'=>Vinyl::listGenres(),'format_id'=>Vinyl::listFormats(),'condition_id'=>Vinyl::listConditions(),'record_label_id'=>Vinyl::listRecordLabels(),'edition_id'=>Vinyl::listEditions()]);
   $selected=$draft['selected']??null;$verified=$selected&&$selected['title']===$data['title']&&$selected['artists']===$data['authors']&&$selected['original_year']===$data['release_date'];
   $musical=['mbid'=>$verified?$selected['id']:null,'source'=>$verified?($selected['source']??'musicbrainz'):'manual'];
   $albumId=($_POST['album_id']??'')===''?null:Input::integer($_POST['album_id'],1);$releaseId=($_POST['musical_release_id']??'')===''?null:Input::integer($_POST['musical_release_id'],1);
   $image=($_POST['keep_photo']??'')==='on'?Input::text($_POST['photo_data']??'',2800000):null;
   $id=AlbumCollection::save(Auth::id(),$data,$musical,$key,$albumId,$releaseId,$image);$this->json(['id'=>$id,'url'=>base_url('vinyls/show?id='.$id),'message'=>'Ejemplar guardado tras tu confirmación.']);
  }catch(HttpException $e){$this->json(['error'=>$e->getMessage()],$e->status);}catch(\Throwable $e){$this->json(['error'=>'No se guardó el ejemplar. La transacción se revirtió; conserva la vista previa y reintenta.'],500);}
 }
 public function album():void{AiFeature::requireEnabled();$a=AlbumCollection::album(Input::integer($_GET['id']??null,1),Auth::id());if(!$a)throw new HttpException(404);$this->view('ai/album',['album'=>$a,'copies'=>AlbumCollection::copies(Auth::id(),null,(int)$a['Id']),'releases'=>AlbumCollection::releases((int)$a['Id'],Auth::id())]);}
 public function edition():void{AiFeature::requireEnabled();$v=Vinyl::findByIdForUser(Input::integer($_GET['id']??null,1),Auth::id());if(!$v)throw new HttpException(404);$a=!empty($v['Album_Id'])?AlbumCollection::album((int)$v['Album_Id'],Auth::id()):null;$this->view('ai/edition',['vinyl'=>$v,'album'=>$a,'releases'=>$a?AlbumCollection::releases((int)$a['Id'],Auth::id()):[]]);}
}
