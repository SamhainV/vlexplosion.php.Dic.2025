<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Vinyl;

final class VinylController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $sort = (string)($_GET['sort'] ?? 'newest');
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }


        $q = trim($_GET['q'] ?? '');
        $fav = (int)($_GET['fav'] ?? 0);
        $desired = (int)($_GET['desired'] ?? 0);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;


        $userId = Auth::id() ?? 0;

        // (De momento) el listado no filtra por $q/$fav/$desired porque este scaffold era mínimo.
        $total = Vinyl::countByUser($userId);
        $p = new Paginator($page, $perPage, $total);


        $items = Vinyl::paginateByUser($userId, $p->perPage, $p->offset(), $sort);


        $this->view('vinyls/index', [
            'items' => $items,
            'p' => $p,
            'sort' => $sort,
            'sortOptions' => $allowed,
        ]);
    }

    public function show(): void
    {
        Auth::requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            redirect('/vinyls');
        }

        $userId = Auth::id() ?? 0;
        $vinyl = Vinyl::findByIdForUser($id, $userId);

        if (!$vinyl) {
            http_response_code(404);
            $this->view('vinyls/show', ['vinyl' => null]);
            return;
        }

        $this->view('vinyls/show', ['vinyl' => $vinyl]);
    }

    public function create(): void
    {
        Auth::requireLogin();

        $sort = (string)($_GET['sort'] ?? 'newest');
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $this->view('vinyls/create', [
            'error' => null,
            'old' => [],
            'genres' => Vinyl::listGenres(),
            'formats' => Vinyl::listFormats(),
            'conditions' => Vinyl::listConditions(),
            'labels' => Vinyl::listRecordLabels(),
            'editions' => Vinyl::listEditions(),
            'return_sort' => $sort,

        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();

        $old = $_POST;

        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $producer = trim($_POST['producer'] ?? '');

        $genreId = (int)($_POST['genre_id'] ?? 0);
        $formatId = (int)($_POST['format_id'] ?? 0);
        $conditionId = (int)($_POST['condition_id'] ?? 0);
        $labelId = (int)($_POST['record_label_id'] ?? 0);
        $editionId = (int)($_POST['edition_id'] ?? 0);

        $release = trim($_POST['release_date'] ?? '');
        $isFav = isset($_POST['is_favorite']) ? 1 : 0;
        $isDesired = isset($_POST['is_desired']) ? 1 : 0;

        // Validación mínima basada en tu schema (NOT NULL en casi todo)
        if (
            $title === '' || $author === '' || $producer === '' || $release === '' ||
            $genreId <= 0 || $formatId <= 0 || $conditionId <= 0 || $labelId <= 0 || $editionId <= 0
        ) {

            $this->view('vinyls/create', [
                'error' => 'Rellena todos los campos obligatorios (título, género, autor, formato, estado, discográfica, producer, año y edición).',
                'old' => $old,
                'genres' => Vinyl::listGenres(),
                'formats' => Vinyl::listFormats(),
                'conditions' => Vinyl::listConditions(),
                'labels' => Vinyl::listRecordLabels(),
                'editions' => Vinyl::listEditions(),
            ]);
            return;
        }

        $year = (int)$release;
        if ($year < 1900 || $year > ((int)date('Y') + 1)) {
            $this->view('vinyls/create', [
                'error' => 'El año no es válido.',
                'old' => $old,
                'genres' => Vinyl::listGenres(),
                'formats' => Vinyl::listFormats(),
                'conditions' => Vinyl::listConditions(),
                'labels' => Vinyl::listRecordLabels(),
                'editions' => Vinyl::listEditions(),
            ]);
            return;
        }

        $userId = Auth::id() ?? 0;

        $newId = Vinyl::createForUser($userId, [
            'title' => $title,
            'genre_id' => $genreId,
            'format_id' => $formatId,
            'condition_id' => $conditionId,
            'record_label_id' => $labelId,
            'producer' => $producer,
            'release_date' => $year,
            'edition_id' => $editionId,
            'author' => $author,
            'is_favorite' => $isFav,
            'is_desired' => $isDesired,
        ]);


        $sort = (string)($_POST['return_sort'] ?? 'newest');
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $perPage = 12;
        $page = Vinyl::pageForIdByUser($userId, $newId, $perPage, $sort);

        redirect('/vinyls?page=' . $page . '&sort=' . urlencode($sort) . '&highlight=' . $newId . '#vinyl-' . $newId);
    }

    public function destroy(): void
    {
        Auth::requireLogin();

        $id = (int)($_POST['id'] ?? 0);
        $userId = Auth::id() ?? 0;

        $sort = (string)($_POST['sort'] ?? 'newest');
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $page = max(1, (int)($_POST['page'] ?? 1));
        $perPage = 12;

        if ($id > 0) {
            Vinyl::deleteForUser($id, $userId);
        }

        // Si borras el último disco de la última página, evitamos quedarnos en una página vacía.
        $total = Vinyl::countByUser($userId);
        $maxPage = max(1, (int)ceil($total / $perPage));
        $page = min($page, $maxPage);

        redirect('/vinyls?page=' . $page . '&sort=' . urlencode($sort) . '&deleted=1');
    }

}
