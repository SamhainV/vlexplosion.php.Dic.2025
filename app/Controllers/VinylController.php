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

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 12;

        $userId = Auth::id() ?? 0;
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
        $this->view('vinyls/create', ['error' => null, 'old' => []]);
    }

    public function store(): void
    {
        Auth::requireLogin();

        $title = trim($_POST['title'] ?? '');
        $producer = trim($_POST['producer'] ?? '');
        $release = trim($_POST['release_date'] ?? '');
        $isFav = isset($_POST['is_favorite']) ? 1 : 0;
        $isDesired = isset($_POST['is_desired']) ? 1 : 0;

        if ($title === '') {
            $this->view('vinyls/create', [
                'error' => 'El título es obligatorio.',
                'old' => $_POST,
            ]);
            return;
        }

        $userId = Auth::id() ?? 0;

        $newId = Vinyl::createForUser($userId, [
            'title' => $title,
            'producer' => $producer,
            'release_date' => $release,
            'is_favorite' => $isFav,
            'is_desired' => $isDesired,
        ]);

        redirect('/vinyls/show?id=' . $newId);
    }
}
