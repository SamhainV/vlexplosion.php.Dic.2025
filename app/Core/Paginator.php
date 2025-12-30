<?php
declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    public int $page;
    public int $perPage;
    public int $total;
    public int $pages;

    public function __construct(int $page, int $perPage, int $total)
    {
        $this->page = max(1, $page);
        $this->perPage = max(1, $perPage);
        $this->total = max(0, $total);
        $this->pages = (int)max(1, ceil($this->total / $this->perPage));
        if ($this->page > $this->pages) {
            $this->page = $this->pages;
        }
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
