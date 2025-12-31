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

        $q = trim($_GET['q'] ?? '');
        $fav = (int)($_GET['fav'] ?? 0);
        $desired = (int)($_GET['desired'] ?? 0);

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 12;

        $userId = Auth::id() ?? 0;

        // (De momento) el listado no filtra por $q/$fav/$desired porque este scaffold era mínimo.
        $total = Vinyl::countByUser($userId);
        $p = new Paginator($page, $perPage, $total);

        $items = Vinyl::paginateByUser($userId, $p->perPage, $p->offset());

        $this->view('vinyls/index', [
            'items' => $items,
            'p' => $p,
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

        $this->view('vinyls/create', [
            'error' => null,
            'old' => [],
            'genres' => Vinyl::listGenres(),
            'formats' => Vinyl::listFormats(),
            'conditions' => Vinyl::listConditions(),
            'labels' => Vinyl::listRecordLabels(),
            'editions' => Vinyl::listEditions(),
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



        $perPage = 12; // IMPORTANTE: el mismo que en index()
        $page = Vinyl::pageForIdByUser($userId, $newId, $perPage);

        redirect('/vinyls?page=' . $page . '&highlight=' . $newId . '#vinyl-' . $newId);
    }
}
