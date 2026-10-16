<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Input;
use App\Core\HttpException;
use App\Models\{CoverArt,CoverArtSource};
use App\Services\CoverArtClient;
final class CoverArtController extends Controller
{
    private function guard(): int {Auth::requireLogin();if(!CoverArt::available())throw new HttpException(503,'Carátulas automáticas pendientes de instalación.');return Auth::id()??0;}
    public function show(): void
    {
        $owner=$this->guard();$id=Input::integer($_GET['id']??0);$v=$id?CoverArt::copy($id,$owner):null;
        $message=$_SESSION['_cover_message']??null;unset($_SESSION['_cover_message']);
        if(is_string($message))$message=['kind'=>'info','text'=>$message];
        $this->view('covers/show',['vinyl'=>$v,'info'=>$v?CoverArt::info($v):null,'eligible'=>$v?[]:CoverArt::eligible($owner),'batch'=>$v?null:CoverArt::batch($owner),'message'=>$message,'sourceReview'=>$v?CoverArtSource::review($id,$owner):null]);
    }
    public function action(): void
    {
        $owner=$this->guard();$id=Input::integer($_POST['id']??0);$action=Input::text($_POST['action']??'',30);
        try{
            if(!$id){if(!in_array($action,['start','step','pause'],true))throw new \RuntimeException('Acción no válida.');$batch=CoverArt::batchAction($owner,$action);$message=self::batchMessage($batch,$action);}
            else{
                $v=CoverArt::copy($id,$owner);
                if($action==='confirm-source'){
                    CoverArtSource::confirm($id,$owner,Input::integer($_POST['source_edition']??0),Input::text($_POST['source_context']??'',64),($_POST['confirm']??'')==='1');
                    $message=['kind'=>'success','text'=>'Edición confirmada exclusivamente para la carátula. Tu revisión musical y tus imágenes se conservan. Ahora puedes buscar su portada.'];
                }elseif($action==='remove-source'){
                    CoverArtSource::remove($id,$owner,($_POST['confirm']??'')==='1');
                    $message=['kind'=>'info','text'=>'Fuente visual retirada. La revisión musical y todas las imágenes se conservan.'];
                }elseif(in_array($action,['manual','auto','group'],true)) {CoverArt::select($id,$owner,$action);$message=['kind'=>'success','text'=>match($action){'manual'=>'Carátula manual seleccionada.','group'=>'Carátula automática aproximada seleccionada.',default=>'Carátula automática seleccionada.'}.' Las imágenes se conservan.'];}
                elseif(in_array($action,['fetch','refresh','fetch-group'],true)){
                    $group=$action==='fetch-group';$mbid=$group?$v['group_mbid']:$v['release_mbid'];
                    if(!CoverArtClient::mbid($mbid))throw new \RuntimeException('Primero confirma una edición MusicBrainz.');
                    $before=CoverArt::info($v);
                    $previous=$before[$group?'alternative':'exact']['storage_key']??null;
                    $r=CoverArtClient::fetch($group?'release-group':'release',$mbid,$action==='refresh');
                    $message=match($r['result']){
                        'ready'=>['kind'=>$previous&&$previous===($r['storage_key']??null)?'info':'success','text'=>$previous&&$previous===($r['storage_key']??null)?($action==='refresh'?'Ya tienes la última carátula disponible.':'Carátula disponible en la caché local; no hay cambios.'):($group?'Alternativa aproximada descargada. Revisa su procedencia antes de seleccionarla.':($action==='refresh'?'Carátula actualizada correctamente.':'Carátula descargada correctamente.')).' Tu imagen manual se conserva.'],
                        'missing'=>['kind'=>'info','text'=>'No hay carátula disponible para esta referencia en Cover Art Archive. Si había una imagen local, se conserva.'],
                        'waiting'=>['kind'=>'warning','text'=>'El proveedor solicita una pausa hasta las '.gmdate('H:i:s',(int)$r['retry_at']).' UTC. Puedes intentarlo después; tus imágenes se conservan.'],
                        default=>['kind'=>'error','text'=>'No se ha podido completar la consulta. Puedes intentarlo más tarde. Tus imágenes se conservan.']};
                }else throw new \RuntimeException('Acción no válida.');
            }
        }catch(HttpException $e){if(!in_array($e->status,[409,422,503],true))throw $e;$message=['kind'=>'warning','text'=>'No se ha confirmado el cambio. Recarga y revisa la edición y tu confirmación antes de continuar.'];}catch(\Throwable){$message=['kind'=>'error','text'=>'No se ha podido completar esta acción. Puedes intentarlo más tarde. Tus datos e imágenes se conservan.'];}
        if(!$id && ($_POST['response']??'')==='json') {
            $state=CoverArt::batch($owner);if($state){$state['total']=count($state['queue']);unset($state['queue']);}
            header('Content-Type: application/json; charset=utf-8');$_SESSION['_cover_message']=$message;echo json_encode(['batch'=>$state,'message'=>$message['text'],'kind'=>$message['kind'],'ok'=>$message['kind']!=='error'],JSON_THROW_ON_ERROR);return;
        }
        $_SESSION['_cover_message']=$message;\redirect('/covers'.($id?'?id='.$id:''));
    }
    private static function batchMessage(?array $state,string $action): array
    {
        if(!$state)return ['kind'=>'error','text'=>'No se ha podido recuperar el estado del lote. Recarga antes de continuar.'];
        $counts=' Revisados '.(int)$state['reviewed'].'/'.count($state['queue']).' · con carátula '.(int)$state['downloaded'].' · sin imagen '.(int)$state['missing'].' · omitidos '.(int)$state['skipped'].' · errores '.(int)$state['errors'].'.';
        if($state['state']==='complete')return ['kind'=>$state['errors']?'warning':'success','text'=>'Lote terminado.'.($state['errors']?' Hay imágenes que no se pudieron recuperar.':'').$counts];
        if(($state['retry_at']??0)>time())return ['kind'=>'warning','text'=>'Lote pausado hasta las '.gmdate('H:i:s',(int)$state['retry_at']).' UTC por una espera o un error. El progreso se conserva.'.$counts];
        return ['kind'=>'info','text'=>($action==='start'?'Lote preparado; todavía no se han descargado imágenes.':'Lote pausado. Puedes continuar sin perder el progreso.').$counts];
    }
    public function image(): void
    {
        $owner=$this->guard();$v=CoverArt::copy(Input::integer($_GET['id']??0),$owner);
        $scope=($_GET['scope']??'')==='group'?'release-group':((($_GET['scope']??'')==='exact')?'release':CoverArt::info($v)['scope']);
        $q=\App\Core\Database::pdo()->prepare('SELECT storage_key FROM CAA_IMAGES WHERE scope=? AND mbid=?');$q->execute([$scope,$scope==='release'?$v['release_mbid']:$v['group_mbid']]);$key=$q->fetchColumn();
        if(!$key||!is_file($file=CoverArtClient::path($key)))throw new HttpException(404,'Carátula no disponible.');
        header('Content-Type: image/png');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');readfile($file);
    }
}
