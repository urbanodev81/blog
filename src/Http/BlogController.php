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
        return $this->listar($request, $blog, collect($blog->publicados()), null);
    }

    public function secao(Request $request, string $secao, Blog $blog): View
    {
        $nome = $blog->secoes()[$secao] ?? null;
        abort_if($nome === null, 404);

        return $this->listar($request, $blog, collect($blog->daSecao($secao)), $nome);
    }

    /** @param \Illuminate\Support\Collection<int, Post> $posts */
    private function listar(Request $request, Blog $blog, \Illuminate\Support\Collection $posts, ?string $secao): View
    {
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
            'secoes' => $blog->secoes(),
            'secaoAtual' => $secao,
        ]);
    }

    public function show(string $slug, Blog $blog): View
    {
        $post = $blog->achar($slug);
        abort_if($post === null, 404);

        return view('blog::show', [
            'post' => $post,
            'acao' => $this->acao($post),
            'redes' => $this->redes($post, url()->current()),
            'textoParaRedes' => $post->textoParaRedes(url()->current()),
        ]);
    }

    public function script(): Response
    {
        return response((string) file_get_contents(__DIR__.'/../../resources/js/blog.js'), 200, [
            'Content-Type' => 'text/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function estilo(): Response
    {
        return response((string) file_get_contents(__DIR__.'/../../resources/css/blog.css'), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Links comuns, sem script de terceiro: cada rede abre a própria tela de
     * compartilhar. A ordem e quais aparecem vêm de `blog.compartilhar`.
     *
     * @return list<array{rede: string, rotulo: string, url: string}>
     */
    private function redes(Post $post, string $url): array
    {
        $u = rawurlencode($url);
        $t = rawurlencode($post->titulo);
        $tags = rawurlencode(implode(' ', $post->hashtags()));
        $todas = [
            'linkedin' => ['LinkedIn', "https://www.linkedin.com/sharing/share-offsite/?url={$u}"],
            'whatsapp' => ['WhatsApp', 'https://wa.me/?text='.rawurlencode($post->titulo.' '.$url)],
            'facebook' => ['Facebook', "https://www.facebook.com/sharer/sharer.php?u={$u}"],
            'x' => ['X', "https://x.com/intent/post?text={$t}%20{$tags}&url={$u}"],
            'email' => ['E-mail', "mailto:?subject={$t}&body=".rawurlencode($post->descricao."\n\n".$url)],
        ];

        $redes = [];
        foreach ((array) config('blog.compartilhar', array_keys($todas)) as $rede) {
            if (isset($todas[$rede])) {
                $redes[] = ['rede' => $rede, 'rotulo' => $todas[$rede][0], 'url' => $todas[$rede][1]];
            }
        }

        return $redes;
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
