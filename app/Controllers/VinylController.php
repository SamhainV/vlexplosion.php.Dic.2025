<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Vinyl;

final class VinylController extends Controller
{
    private const COVER_MAX_SIZE = 5 * 1024 * 1024; // 5 MB
    private const COVER_UPLOAD_RELATIVE_DIR = 'uploads/covers';

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

        $returnPage = max(1, (int)($_GET['return_page'] ?? $_GET['page'] ?? 1));
        $returnSort = (string)($_GET['return_sort'] ?? $_GET['sort'] ?? 'newest');
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

        $returnPage = max(1, (int)($_GET['page'] ?? $_GET['return_page'] ?? 1));
        $sort = (string)($_GET['sort'] ?? $_GET['return_sort'] ?? 'newest');
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $this->renderCreate(null, [], $returnPage, $sort);
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

        $returnPage = max(1, (int)($_POST['return_page'] ?? 1));
        $returnSort = (string)($_POST['return_sort'] ?? 'newest');

        // Validación mínima basada en tu schema (NOT NULL en casi todo)
        if (
            $title === '' || $author === '' || $producer === '' || $release === '' ||
            $genreId <= 0 || $formatId <= 0 || $conditionId <= 0 || $labelId <= 0 || $editionId <= 0
        ) {
            $this->renderCreate(
                'Rellena todos los campos obligatorios (título, género, autor, formato, estado, discográfica, producer, año y edición).',
                $old,
                $returnPage,
                $returnSort
            );
            return;
        }

        $year = (int)$release;
        if ($year < 1900 || $year > ((int)date('Y') + 1)) {
            $this->renderCreate('El año no es válido.', $old, $returnPage, $returnSort);
            return;
        }

        $uploadResult = $this->handleCoverUpload();

        if (!$uploadResult['ok']) {
            $this->renderCreate($uploadResult['error'], $old, $returnPage, $returnSort);
            return;
        }

        $imagePath = $uploadResult['path'];

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
            'image_path' => $imagePath,
        ]);

        $sort = $returnSort;
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

        $sort = (string)($_POST['return_sort'] ?? $_POST['sort'] ?? 'newest');
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$sort])) {
            $sort = 'newest';
        }

        $page = max(1, (int)($_POST['return_page'] ?? $_POST['page'] ?? 1));
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

    private function renderCreate(?string $error, array $old, int $returnPage, string $returnSort): void
    {
        $allowed = Vinyl::allowedSorts();
        if (!isset($allowed[$returnSort])) {
            $returnSort = 'newest';
        }

        $this->view('vinyls/create', [
            'error' => $error,
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

    /**
     * Sube la carátula seleccionada desde el formulario.
     *
     * Devuelve:
     * - ok: true/false
     * - path: ruta relativa que se guarda en VINYLS_TBL.Image_Path, por ejemplo uploads/covers/cover_x.webp
     * - error: mensaje para mostrar en pantalla si falla
     *
     * @return array{ok: bool, path: ?string, error: ?string}
     */
    private function handleCoverUpload(): array
    {
        if (!isset($_FILES['cover']) || !is_array($_FILES['cover'])) {
            return ['ok' => true, 'path' => null, 'error' => null];
        }

        $file = $_FILES['cover'];
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null, 'error' => null];
        }

        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => null, 'error' => 'No se pudo subir la carátula. Código de error: ' . $error];
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['ok' => false, 'path' => null, 'error' => 'La carátula no se ha recibido correctamente.'];
        }

        if ($size <= 0 || $size > self::COVER_MAX_SIZE) {
            return ['ok' => false, 'path' => null, 'error' => 'La carátula debe pesar como máximo 5 MB.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmpName);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$mime])) {
            return ['ok' => false, 'path' => null, 'error' => 'Formato de carátula no permitido. Usa JPG, PNG o WEBP.'];
        }

        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . '/public/' . self::COVER_UPLOAD_RELATIVE_DIR;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['ok' => false, 'path' => null, 'error' => 'No se pudo crear la carpeta public/uploads/covers.'];
        }

        if (!is_writable($uploadDir)) {
            return ['ok' => false, 'path' => null, 'error' => 'La carpeta public/uploads/covers no tiene permisos de escritura.'];
        }

        $extension = $allowedMimes[$mime];
        $fileName = 'cover_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $absoluteDestination = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $absoluteDestination)) {
            return ['ok' => false, 'path' => null, 'error' => 'No se pudo guardar la carátula en public/uploads/covers.'];
        }

        return [
            'ok' => true,
            'path' => self::COVER_UPLOAD_RELATIVE_DIR . '/' . $fileName,
            'error' => null,
        ];
    }
}
