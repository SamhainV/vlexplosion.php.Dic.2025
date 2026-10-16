<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, AiFeature, Controller};

/** Compatibility endpoints for the unavailable photographic prototype; no provider calls. */
final class AiVinylController extends Controller
{
    public function start(): void
    {
        Auth::requireLogin();
        $this->view('ai/unavailable');
    }

    public function api(): void { AiFeature::requireEnabled(); }
    public function store(): void { AiFeature::requireEnabled(); }
    public function album(): void { AiFeature::requireEnabled(); }
    public function edition(): void { AiFeature::requireEnabled(); }
}
