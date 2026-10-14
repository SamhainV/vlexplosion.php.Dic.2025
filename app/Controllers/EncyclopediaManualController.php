<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Auth,Controller,Database,Encyclopedia,HttpException,Input};
use App\Models\EncyclopediaRecord;
use App\Services\EncyclopediaManual;

final class EncyclopediaManualController extends Controller
{
    public function start(): void {Encyclopedia::requireEnabled();EncyclopediaManual::requireSchema();$this->render(Input::integer($_GET['id']??null,1));}
    private function render(int $id,?string $error=null,array $old=[]): void
    {
        $record=EncyclopediaRecord::forCopy($id,Auth::id());if(!$record)throw new HttpException(404);
        $pdo=Database::pdo();$artists=$pdo->query('SELECT id,name,kind,musicbrainz_id FROM ENC_ARTISTS ORDER BY name,id')->fetchAll();
        $artists=array_values(array_filter($artists,fn($a)=>$a['musicbrainz_id']!==null || !empty(\App\Models\EncyclopediaEntity::find('artist',(int)$a['id'],Auth::id())['albums'])));
        $q=$pdo->prepare('SELECT DISTINCT l.id,l.name FROM ENC_LABELS l LEFT JOIN ENC_EDITION_LABELS el ON el.label_id=l.id LEFT JOIN ENC_EDITIONS e ON e.id=el.edition_id LEFT JOIN ENC_ALBUMS a ON a.id=e.album_id WHERE l.musicbrainz_id IS NOT NULL OR a.draft_owner_id IS NULL OR a.draft_owner_id=? ORDER BY l.name');$q->execute([Auth::id()]);
        $history=$pdo->prepare('SELECT field_name,author_user_id,created_at,value_json FROM ENC_ASSERTIONS WHERE album_id=? AND field_name LIKE ? ORDER BY id DESC LIMIT 20');$history->execute([$record['album']['id']??0,'manual.%']);
        $this->view('encyclopedia/manual',['history'=>$history->fetchAll(),'record'=>$record,'error'=>$error,'old'=>$old,'artists'=>$artists,'labels'=>$q->fetchAll(),'revision'=>!empty($record['album']['id'])?EncyclopediaManual::revision((int)$record['album']['id']):0,'sharedEditor'=>EncyclopediaManual::sharedEditor(Auth::id())]);
    }
    public function save(): void
    {
        Encyclopedia::requireEnabled();$id=Input::integer($_POST['id']??null,1);
        if(!EncyclopediaRecord::forCopy($id,Auth::id()))throw new HttpException(404);
        try {
            if(($_POST['confirm']??'')!=='on')throw new HttpException(422,'Confirma expresamente el alcance de los cambios.');
            $operation=Input::text($_POST['operation']??'',16);$data=array_filter($_POST,'is_string');
            foreach(['_csrf','id','operation','scope','revision','key','confirm'] as $k)unset($data[$k]);
            if(isset($data['target_key'])) { $parts=explode(':',$data['target_key']);if(count($parts)!==2)throw new HttpException(422);$data['target_kind']=$parts[0];$data['target_id']=$parts[1];unset($data['target_key']); }
            EncyclopediaManual::save($id,Auth::id(),Input::text($_POST['scope']??'',10),$operation,$data,Input::integer($_POST['revision']??null),Input::text($_POST['key']??'',64));
            redirect('/encyclopedia/manual?id='.$id);
        }catch(HttpException $e){http_response_code($e->status);$this->render($id,$e->getMessage(),$_POST);}
        catch(\PDOException){http_response_code(409);$this->render($id,'No se guardó el cambio. Comprueba posiciones repetidas o relaciones; la transacción se revirtió.',$_POST);}
    }
}
