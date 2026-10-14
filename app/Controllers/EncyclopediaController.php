<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth, Controller, Encyclopedia, HttpException, Input, VinylInput, ReleaseFormat};
use App\Services\Metadata\{MetadataException, MusicBrainzNormalizer};
use App\Services\EncyclopediaCollection;
use App\Models\EncyclopediaRecord;

final class EncyclopediaController extends Controller
{
    public function start(): void
    {
        Encyclopedia::requireEnabled();
        $this->view('encyclopedia/search');
    }

    public function linkStart(): void
    {
        Encyclopedia::requireEnabled();
        $id=Input::integer($_GET['id']??null,1);
        $record=EncyclopediaRecord::forCopy($id,Auth::id());
        if (!$record) throw new HttpException(404);
        $personal=json_decode($record['copy']['overrides_json']??'{}',true)?:[];
        $this->view('encyclopedia/search',['target'=>$id,'defaults'=>['title'=>$record['vinyl']['Title'],'artist'=>$record['vinyl']['author_name'],'year'=>(string)($record['vinyl']['Release_date']??''),'catalogue'=>$personal['catalogue_number']??'']]);
    }

    public function association(): void
    {
        Encyclopedia::requireEnabled();$id=Input::integer($_GET['id']??null,1);
        $record=EncyclopediaRecord::forCopy($id,Auth::id());if (!$record) throw new HttpException(404);
        $this->view('encyclopedia/association',['record'=>$record,'key'=>bin2hex(random_bytes(32))]);
    }

    public function detach(): void
    {
        Encyclopedia::requireEnabled();
        if (($_POST['confirm']??'')!=='on') throw new HttpException(422,'Confirma expresamente la desvinculación.');
        $id=Input::integer($_POST['id']??null,1);
        \App\Services\EncyclopediaLink::apply($id,Auth::id(),null,Input::text($_POST['expected']??'',50),Input::text($_POST['key']??'',64));
        redirect('/vinyls/show?id='.$id);
    }

    private function draft(string $token): array
    {
        $draft = $_SESSION['enc_drafts'][$token] ?? null;
        if (!$draft || $draft['user'] !== Auth::id() || $draft['expires'] < time()) { throw new HttpException(422, 'La búsqueda ha caducado. Inicia otra búsqueda.'); }
        return $draft;
    }

    private function candidates(array $result, string $token, ?array $group=null): void
    {
        $preference=$_SESSION['enc_drafts'][$token]['format_preference']??'prefer';
        $this->view('encyclopedia/search',['result'=>ReleaseFormat::filter($result,$preference),'token'=>$token,'formatPreference'=>$preference]+($group?['group'=>$group]:[]));
    }

    public function action(): void
    {
        Encyclopedia::requireEnabled();
        $operation = Input::text($_POST['operation'] ?? '', 20);
        $token = Input::text($_POST['draft'] ?? '', 64);
        try {
            if ($operation==='filter') {
                $draft=$this->draft($token);
                $preference=ReleaseFormat::preference($_POST['format_preference']??'prefer');
                $result=$draft['editions']??$draft['result'];
                if ($result['entity_type']!=='release') throw new HttpException(422,'El filtro se aplica a ediciones.');
                $_SESSION['enc_drafts'][$token]['format_preference']=$preference;
                $this->candidates($result,$token,$draft['group']??null);return;
            }
            $client = Encyclopedia::client();
            if ($operation === 'search') {
                $preference=ReleaseFormat::preference($_POST['format_preference']??'prefer');
                $target=Input::integer($_POST['target']??'0');$existing=null;
                if ($target) { $existing=EncyclopediaRecord::forCopy($target,Auth::id());if (!$existing) throw new HttpException(404); }
                $fields = [];
                $artist = Input::text($_POST['artist'] ?? '', 100);
                $title = Input::text($_POST['title'] ?? '', 120);
                $barcode = Input::text($_POST['barcode'] ?? '', 64);
                $catalogue = Input::text($_POST['catalogue'] ?? '', 120);
                $entity = $barcode !== '' || $catalogue !== '' ? 'release' : 'release-group';
                if ($artist !== '') { $fields['artist'] = $artist; }
                if ($title !== '') { $fields[$entity === 'release' ? 'release' : 'releasegroup'] = $title; }
                if ($barcode !== '') { $fields['barcode'] = $barcode; }
                if ($catalogue !== '') { $fields['catno'] = $catalogue; }
                $year=Input::text($_POST['year']??'',4);if ($year!=='') { if (!preg_match('/^[0-9]{4}$/D',$year)) throw new HttpException(422,'Año inválido.');$fields[$entity==='release'?'date':'firstreleasedate']=$year; }
                if (!$fields) { throw new HttpException(422, 'Introduce artista, álbum, catálogo o código de barras.'); }
                $result = $client->search($entity, $fields, Input::integer($_POST['offset'] ?? '0'));
                $token = bin2hex(random_bytes(32));
                $_SESSION['enc_drafts'] = array_filter($_SESSION['enc_drafts'] ?? [], static fn(array $d): bool => $d['expires'] > time());
                $_SESSION['enc_drafts'] = array_slice($_SESSION['enc_drafts'], -7, null, true);
                $_SESSION['enc_drafts'][$token] = ['user' => Auth::id(), 'expires' => time() + 3600, 'result' => $result, 'target'=>$target,'expected'=>\App\Services\EncyclopediaLink::state($existing&&empty($existing['legacy'])?$existing['copy']:null), 'format_preference'=>$preference, 'save_key' => bin2hex(random_bytes(32))];
                $this->candidates($result,$token); return;
            }
            $draft = $this->draft($token);
            if ($operation === 'album') {
                $id = Input::text($_POST['id'] ?? '', 36);
                $found = array_filter($draft['result']['candidates'], static fn(array $c): bool => $c['id'] === $id);
                if (!$found || $draft['result']['entity_type'] !== 'release-group') { throw new HttpException(422, 'Selecciona un álbum de los resultados.'); }
                $group = $client->lookup('release-group', $id);
                $_SESSION['enc_drafts'][$token]['group'] = $group;
                unset($_SESSION['enc_drafts'][$token]['metadata']);
                $this->view('encyclopedia/search', ['token' => $token, 'group' => $group]); return;
            }
            if ($operation === 'editions') {
                if (!isset($draft['group'])) { throw new HttpException(422, 'Selecciona primero un álbum.'); }
                $result = $client->editions($draft['group']['data']['id'], Input::integer($_POST['offset'] ?? '0'));
                $_SESSION['enc_drafts'][$token]['editions'] = $result;
                $this->candidates($result,$token,$draft['group']); return;
            }
            if ($operation === 'release') {
                $id = Input::text($_POST['id'] ?? '', 36);
                $candidates = $draft['editions']['candidates'] ?? ($draft['result']['entity_type'] === 'release' ? $draft['result']['candidates'] : []);
                $candidates=ReleaseFormat::filter(['entity_type'=>'release','candidates'=>$candidates],$draft['format_preference']??'prefer')['candidates'];
                if (!array_filter($candidates, static fn(array $c): bool => $c['id'] === $id)) { throw new HttpException(422, 'Selecciona una edición de los resultados.'); }
                $release = $client->lookup('release', $id);
                if (isset($draft['group']) && $release['data']['release-group']['id'] !== $draft['group']['data']['id']) { throw new HttpException(422, 'La edición no pertenece al álbum elegido.'); }
                $_SESSION['enc_drafts'][$token]['release'] = $release;
                unset($_SESSION['enc_drafts'][$token]['metadata']);
                $this->view('encyclopedia/search', ['token' => $token, 'release' => $release]); return;
            }
            if ($operation === 'review') {
                if (!isset($draft['release'])) { throw new HttpException(422, 'Selecciona una edición.'); }
                $groupId = $draft['release']['data']['release-group']['id'];
                $group = $draft['group'] ?? $client->lookup('release-group', $groupId);
                $metadata = MusicBrainzNormalizer::release($draft['release'], $group);
                $_SESSION['enc_drafts'][$token]['group'] = $group;
                $_SESSION['enc_drafts'][$token]['metadata'] = $metadata;
                $_SESSION['enc_drafts'][$token]['save_key'] = bin2hex(random_bytes(32));
                if (!empty($draft['target'])) { $record=EncyclopediaRecord::forCopy($draft['target'],Auth::id());if (!$record) throw new HttpException(404);$this->view('encyclopedia/link-review',['metadata'=>$metadata,'token'=>$token,'record'=>$record]); } else $this->view('encyclopedia/review', ['metadata' => $metadata, 'token' => $token]); return;
            }
            throw new HttpException(404);
        } catch (MetadataException $error) {
            http_response_code(in_array($error->reason, ['rate_limit', 'busy'], true) ? 429 : 502);
            $wait = max(0, $error->retryAfter);
            if ($wait > 0) { header('Retry-After: ' . $wait); }
            $this->view('encyclopedia/search', ['error' => $error->getMessage(), 'retry' => $_POST, 'wait' => $wait]);
        } catch (\InvalidArgumentException) {
            throw new HttpException(422, 'La búsqueda o el identificador no son válidos.');
        }
    }

    public function store(): void
    {
        Encyclopedia::requireEnabled();
        $token = Input::text($_POST['draft'] ?? '', 64);
        $draft = $this->draft($token);
        $metadata = $draft['metadata'] ?? null;
        if (!$metadata) { throw new HttpException(422, 'Revisa primero los datos recuperados.'); }
        try {
            if (!hash_equals($draft['save_key'], Input::text($_POST['confirmation_key'] ?? '', 64))) { throw new HttpException(422, 'La selección ha cambiado. Vuelve a revisar los datos.'); }
            if (($_POST['confirm'] ?? '') !== 'on') { throw new HttpException(422, 'Confirma los datos antes de guardar.'); }
            if (!empty($draft['target'])) {
                $id=\App\Services\EncyclopediaLink::apply((int)$draft['target'],Auth::id(),$metadata,$draft['expected'],$draft['save_key']);
                redirect('/vinyls/show?id='.$id);
            }
            $input = $_POST;
            $input['authors'] = preg_split('/\r?\n/', Input::text($_POST['artists'] ?? '', 1100));
            $data = VinylInput::validate($input, null, true);
            $personal = ['notes' => Input::text($_POST['notes'] ?? '', 2000) ?: null,
                'catalogue_number' => Input::text($_POST['personal_catalogue'] ?? '', 120) ?: null,
                'country' => Input::text($_POST['personal_country'] ?? '', 80) ?: null];
            // No client-supplied IDs or metadata enter the shared catalog.
            foreach (['genre_id', 'format_id', 'condition_id', 'record_label_id', 'edition_id'] as $field) { $data[$field] = null; }
            $id = EncyclopediaCollection::create(Auth::id(), $data, $metadata, $draft['save_key'], $personal);
            redirect('/encyclopedia/copy?id=' . $id);
        } catch (HttpException $error) {
            if (!empty($draft['target']) && !EncyclopediaRecord::forCopy($draft['target'],Auth::id())) throw new HttpException(404);
            http_response_code($error->status);
            $this->view(!empty($draft['target'])?'encyclopedia/link-review':'encyclopedia/review', ['metadata' => $metadata, 'token' => $token, 'record'=>!empty($draft['target'])?EncyclopediaRecord::forCopy($draft['target'],Auth::id()):null, 'error' => $error->getMessage(), 'old' => $_POST]);
        } catch (\Throwable) {
            http_response_code(500);
            $this->view(!empty($draft['target'])?'encyclopedia/link-review':'encyclopedia/review', ['metadata' => $metadata, 'token' => $token, 'record'=>!empty($draft['target'])?EncyclopediaRecord::forCopy($draft['target'],Auth::id()):null, 'error' => 'No se guardó el ejemplar. La transacción se revirtió; puedes corregir o reintentar.', 'old' => $_POST]);
        }
    }

    public function album(): void { $this->entity('album'); }
    public function edition(): void { $this->entity('edition'); }
    public function label(): void { $this->entity('label'); }
    public function work(): void { $this->entity('work'); }
    public function recording(): void { $this->entity('recording'); }
    public function artist(): void { $this->entity('artist'); }

    private function entity(string $kind): void
    {
        Encyclopedia::requireEnabled();
        $record = \App\Models\EncyclopediaEntity::find($kind, Input::integer($_GET['id'] ?? null, 1), Auth::id());
        if (!$record) { throw new HttpException(404); }
        $this->view('encyclopedia/entity', $record + ['kind'=>$kind]);
    }

    public function show(): void
    {
        Encyclopedia::requireEnabled();
        $record = EncyclopediaRecord::forCopy(Input::integer($_GET['id'] ?? null, 1), Auth::id());
        if (!$record) { throw new HttpException(404); }
        $this->view('encyclopedia/show', $record);
    }
}
