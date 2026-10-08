<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Vinyl;
use App\Core\Input;
use App\Core\VinylInput;
use App\Core\HttpException;
use App\Core\CollectionFilter;
use App\Core\CoverStore;

final class VinylController extends Controller
{

    public function index(): void
    {
        Auth::requireLogin();

        $sort = Input::text($_GET['sort'] ?? 'newest', 40);
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $filters = CollectionFilter::read();

        $page = max(1, Input::integer($_GET['page'] ?? 1));
        $perPage = 12;

        $userId = Auth::id() ?? 0;

        // (De momento) el listado no filtra por $q/$fav/$desired porque este scaffold era mínimo.
        $total = Vinyl::countByUser($userId, CollectionFilter::read());
        $p = new Paginator($page, $perPage, $total);

        $items = Vinyl::paginateByUser($userId, $p->perPage, $p->offset(), $sort, $filters);

        $this->view('vinyls/index', [
            'items' => $items,
            'p' => $p,
            'sort' => $sort,
            'sortOptions' => $allowed,
            'filters' => $filters,
        ]);
    }

    public function show(): void
    {
        Auth::requireLogin();

        $id = Input::integer($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/vinyls');
        }

        $returnPage = max(1, Input::integer($_GET['return_page'] ?? $_GET['page'] ?? 1));
        $returnSort = Input::text($_GET['return_sort'] ?? $_GET['sort'] ?? 'newest', 40);
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$returnSort])) {
            $returnSort = 'newest';
        }

        $userId = Auth::id() ?? 0;
        $vinyl = Vinyl::findByIdForUser($id, $userId);

        if (!$vinyl) {
            http_response_code(404);
            $this->view('vinyls/show', [
                'vinyl' => null,
                'return_page' => $returnPage,
                'return_sort' => $returnSort,
            ]);
            return;
        }

        $this->view('vinyls/show', [
            'vinyl' => $vinyl,
            'return_page' => $returnPage,
            'return_sort' => $returnSort,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();

        $returnPage = max(1, Input::integer($_GET['page'] ?? $_GET['return_page'] ?? 1));
        $sort = Input::text($_GET['sort'] ?? $_GET['return_sort'] ?? 'newest', 40);
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $this->renderCreate(null, [], $returnPage, $sort);
    }

    public function update(): void { $this->save(true); }
    public function store(): void { $this->save(false); }

    public function edit(): void
    {
        Auth::requireLogin();
        $id = Input::integer($_GET['id'] ?? null, 1);
        $vinyl = Vinyl::findByIdForUser($id, Auth::id() ?? 0);
        if (!$vinyl) { throw new HttpException(404); }
        $old = [];
        foreach (['title' => 'Title', 'producer' => 'Producer', 'genre_id' => 'Genres_Id', 'format_id' => 'Format_Id', 'condition_id' => 'Condition_Id', 'record_label_id' => 'Record_Label_Id', 'release_date' => 'Release_date', 'edition_id' => 'Edition_Id', 'is_favorite' => 'Is_Favorite', 'is_desired' => 'Is_Desired'] as $field => $column) { $old[$field] = $vinyl[$column]; }
        $old['authors'] = array_column($vinyl['authors'], 'Author_Name');
        $this->renderCreate(null, $old, max(1, Input::integer($_GET['return_page'] ?? 1)), Input::text($_GET['return_sort'] ?? 'newest', 40), $id, $vinyl['Image_Path'] ?? null);
    }

    private function save(bool $editing): void
    {
        Auth::requireLogin();

        $editId = $editing ? Input::integer($_POST['id'] ?? null, 1) : 0;
        $existing = $editing ? Vinyl::findByIdForUser($editId, Auth::id() ?? 0) : null;
        if ($editing && !$existing) { throw new HttpException(404); }
        $old = array_filter($_POST, 'is_string');
        $old['authors'] = is_array($_POST['authors'] ?? null) ? array_values(array_filter($_POST['authors'], 'is_string')) : [is_string($_POST['author'] ?? null) ? $_POST['author'] : ''];
        $returnPage = max(1, Input::integer($_POST['return_page'] ?? 1));
        $returnSort = Input::text($_POST['return_sort'] ?? 'newest', 40);
        try {
            $data = VinylInput::validate($_POST);
            VinylInput::validateCatalogues($data, [
                'genre_id' => Vinyl::listGenres(),
                'format_id' => Vinyl::listFormats(),
                'condition_id' => Vinyl::listConditions(),
                'record_label_id' => Vinyl::listRecordLabels(),
                'edition_id' => Vinyl::listEditions(),
            ]);
        } catch (HttpException $error) {
            http_response_code(422);
            $this->renderCreate($error->getMessage(), $old, $returnPage, $returnSort, $editId, $existing['Image_Path'] ?? null);
            return;
        }

        $userId = Auth::id() ?? 0;
        try {
            $newId = CoverStore::withLock(function () use ($editing, $editId, $userId, $data): int {
                // Re-read under the shared image lifecycle lock to avoid stale cover cleanup.
                $current = $editing ? Vinyl::findByIdForUser($editId, $userId) : null;
                if ($editing && !$current) { throw new HttpException(404); }
                $newPath = CoverStore::upload($_FILES['cover'] ?? null);
                $saved = $data;
                $saved['image_path'] = $newPath ?? ($current['Image_Path'] ?? null);
                try {
                    if ($editing) {
                        if (!Vinyl::updateForUser($editId, $userId, $saved)) { throw new HttpException(404); }
                        $id = $editId;
                    } else { $id = Vinyl::createForUser($userId, $saved); }
                } catch (\Throwable $error) {
                    CoverStore::cleanup($newPath, [Vinyl::class, 'imageReferences']);
                    throw $error;
                }
                if ($editing && $newPath) { CoverStore::cleanup($current['Image_Path'] ?? null, [Vinyl::class, 'imageReferences']); }
                return $id;
            });
        } catch (HttpException $error) {
            if ($error->status !== 422) { throw $error; }
            http_response_code(422);
            $this->renderCreate($error->getMessage(), $old, $returnPage, $returnSort, $editId, $existing['Image_Path'] ?? null);
            return;
        }

        $sort = $returnSort;
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $perPage = 12;
        $page = Vinyl::pageForIdByUser($userId, $newId, $perPage, $sort, CollectionFilter::read());

        $_SESSION['_flash'] = $editing ? 'Cambios guardados correctamente.' : 'Vinilo añadido correctamente.';
        redirect('/vinyls?' . collection_query($page, $sort) . '&highlight=' . $newId . '#vinyl-' . $newId);
    }

    public function destroy(): void
    {
        Auth::requireLogin();

        $id = Input::integer($_POST['id'] ?? 0);
        $userId = Auth::id() ?? 0;

        $sort = Input::text($_POST['return_sort'] ?? $_POST['sort'] ?? 'newest', 40);
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $page = max(1, Input::integer($_POST['return_page'] ?? $_POST['page'] ?? 1));
        $perPage = 12;

        $deleted = $id > 0 && CoverStore::withLock(function () use ($id, $userId): bool {
            $current = Vinyl::findByIdForUser($id, $userId);
            if (!$current || !Vinyl::deleteForUser($id, $userId)) { return false; }
            CoverStore::cleanup($current['Image_Path'] ?? null, [Vinyl::class, 'imageReferences']);
            return true;
        });
        $_SESSION['_flash'] = $deleted ? 'Vinilo eliminado correctamente.' : 'No se ha eliminado ningún vinilo.';

        // Si borras el último disco de la última página, evitamos quedarnos en una página vacía.
        $total = Vinyl::countByUser($userId, CollectionFilter::read());
        $maxPage = max(1, (int)ceil($total / $perPage));
        $page = min($page, $maxPage);

        redirect('/vinyls?' . collection_query($page, $sort));
    }

    private function renderCreate(?string $error, array $old, int $returnPage, string $returnSort, int $id = 0, ?string $currentCover = null): void
    {
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$returnSort])) {
            $returnSort = 'newest';
        }

        $this->view('vinyls/create', [
            'error' => $error,
            'editing' => $id > 0,
            'vinylId' => $id,
            'currentCover' => $currentCover,
            'old' => $old,
            'genres' => Vinyl::listGenres(),
            'formats' => Vinyl::listFormats(),
            'conditions' => Vinyl::listConditions(),
            'labels' => Vinyl::listRecordLabels(),
            'editions' => Vinyl::listEditions(),
            'return_page' => $returnPage,
            'return_sort' => $returnSort,
        ]);
    }

}
