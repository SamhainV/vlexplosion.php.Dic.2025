<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Auth,Controller,Encyclopedia,HttpException,Input};
use App\Services\EncyclopediaCreditSync as Sync;

final class EncyclopediaCreditController extends Controller
{
    public function show(): void
    {
        Encyclopedia::requireEnabled();Sync::requireSchema();
        $key=Input::text($_GET['job']??'',64);
        if($key){$j=Sync::job($key,Auth::id());$id=(int)$j['vinyl_id'];}
        else{$id=Input::integer($_GET['id']??null,1);$j=Sync::current($id,Auth::id());}
        $record=\App\Models\EncyclopediaRecord::forCopy($id,Auth::id());if(!$record)throw new HttpException(404);
        $this->view('encyclopedia/credit-update',['record'=>$record,'job'=>$j,'lastSaved'=>Sync::lastSaved($id,Auth::id()),'rows'=>$j&&in_array($j['status'],['ready','complete'],true)?Sync::graph($j):[],'error'=>null]);
    }
    public function action(): void
    {
        Encyclopedia::requireEnabled();Sync::requireSchema();$key=Input::text($_POST['job']??'',64);
        try {
            $op=Input::text($_POST['operation']??'',16);
            if(in_array($op,['cancel','revert'],true)&&($_POST['confirm']??'')!=='on')throw new HttpException(422,'Confirma esta acción antes de continuar.');
            if($op==='start')$key=Sync::start(Input::integer($_POST['id']??null,1),Auth::id());
            elseif($op==='step')Sync::step($key,Auth::id(),Encyclopedia::client());
            elseif($op==='apply')Sync::apply($key,Auth::id());
            elseif($op==='revert')Sync::revert($key,Auth::id());
            elseif($op==='cancel')Sync::cancel($key,Auth::id());
            elseif($op==='resume')Sync::resume($key,Auth::id());
            else throw new HttpException(422);
            redirect('/encyclopedia/credits?job='.$key);
        }catch(\Throwable $e){
            if($e instanceof HttpException && in_array($e->status,[403,404],true))throw $e;
            http_response_code($e instanceof HttpException?$e->status:503);
            $j=$key?Sync::job($key,Auth::id()):null;$record=$j?$j['record']:\App\Models\EncyclopediaRecord::forCopy(Input::integer($_POST['id']??null,1),Auth::id());if(!$record)throw new HttpException(404);
            $this->view('encyclopedia/credit-update',['record'=>$record,'job'=>$j,'lastSaved'=>Sync::lastSaved((int)$record['vinyl']['Id'],Auth::id()),'rows'=>$j&&in_array($j['status'],['ready','complete'],true)?Sync::graph($j):[],'error'=>$e instanceof HttpException?$e->getMessage():'La operación no se completó. Los créditos anteriores se conservan; consulta el estado antes de continuar.']);
        }
    }
}
