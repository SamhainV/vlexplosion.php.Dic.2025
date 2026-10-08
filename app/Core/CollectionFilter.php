<?php
declare(strict_types=1);
namespace App\Core;
final class CollectionFilter
{
    public static function read(): array
    {
        $post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $source = $post ? $_POST : $_GET;
        $prefix = $post ? 'return_' : '';
        $data = ['q' => Input::text($source[$prefix . 'q'] ?? '', 200)];
        foreach (['fav', 'desired'] as $key) {
            $value = Input::integer($source[$prefix . $key] ?? 0);
            if ($value > 1) { throw new HttpException(422); }
            $data[$key] = $value;
        }
        return $data;
    }
    public static function sql(array $filters): array
    {
        $clauses = []; $params = [];
        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
            $clauses[] = "(v.Title LIKE :search_title ESCAPE '!' OR v.Producer LIKE :search_producer ESCAPE '!' OR EXISTS (SELECT 1 FROM AUTOR_VINYLS_TBL search_av JOIN AUTHORS_TBL search_a ON search_a.Id = search_av.Autor_Id WHERE search_av.Vinilo_Id = v.Id AND search_a.Author_Name LIKE :search_author ESCAPE '!'))";
            $params = ['search_title' => $like, 'search_producer' => $like, 'search_author' => $like];
        }
        if (!empty($filters['fav'])) { $clauses[] = 'v.Is_Favorite = 1'; }
        if (!empty($filters['desired'])) { $clauses[] = 'v.Is_Desired = 1'; }
        return [$clauses ? ' AND ' . implode(' AND ', $clauses) : '', $params];
    }
}
