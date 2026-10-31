<?php

namespace UrbanoDev\Blog\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\Post;

class BlogController
{
    public function index(Request $request, Blog $blog): View
    {
        $posts = collect($blog->publicados());
        $porPagina = max(1, (int) config('blog.por_pagina'));
        $pagina = max(1, $request->integer('pagina', 1));

        abort_if($pagina > 1 && $posts->forPage($pagina, $porPagina)->isEmpty(), 404);

        return view('blog::index', [
            'posts' => new LengthAwarePaginator(
                $posts->forPage($pagina, $porPagina)->values(),
                $posts->count(),
                $porPagina,
                $pagina,
                ['path' => $request->url(), 'pageName' => 'pagina'],
            ),
        ]);
    }

    public function show(string $slug, Blog $blog): View
    {
        $post = $blog->achar($slug);
        abort_if($post === null, 404);

        return view('blog::show', ['post' => $post, 'acao' => $this->acao($post)]);
    }

    public function estilo(): Response
    {
        return response((string) file_get_contents(__DIR__.'/../../resources/css/blog.css'), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** @return array{texto: string, url: string}|null */
    private function acao(Post $post): ?array
    {
        $texto = $post->acaoTexto ?? config('blog.acao_texto');
        $url = $post->acaoUrl ?? config('blog.acao_url');

        if (! filled($texto) || ! filled($url)) {
            return null;
        }

        return ['texto' => (string) $texto, 'url' => str_replace(['{titulo}', '{slug}'], [rawurlencode($post->titulo), $post->slug], (string) $url)];
    }
}
