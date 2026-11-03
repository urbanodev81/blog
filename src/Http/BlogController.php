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
    /**
     * A lista. Com `?secao=`, `?tag=`, `?mes=2026-11` ou `?q=` ela vira o
     * resultado do filtro: é um formulário GET comum, que funciona sem script.
     * Valor fora do formato é ignorado, nunca vira erro nem texto na página.
     */
    public function index(Request $request, Blog $blog): View
    {
        $secao = $this->slug($request->query('secao'));
        $tag = $this->slug($request->query('tag'));
        [$ano, $mes] = preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/D', $this->texto($request->query('mes')), $m) === 1 ? [(int) $m[1], (int) $m[2]] : [null, null];
        $busca = mb_substr(trim($this->texto($request->query('q'))), 0, 80);

        $filtros = array_filter(['secao' => $secao, 'tag' => $tag, 'mes' => $ano ? sprintf('%04d-%02d', $ano, $mes) : null, 'q' => $busca]);
        if ($filtros === []) {
            return $this->listar($request, $blog, $blog->publicados());
        }

        $posts = $blog->filtrar($secao, $tag, $ano, $mes, $busca);

        return $this->listar($request, $blog, $posts, [
            'titulo' => 'Busca',
            'descricao' => 'Textos que passam no filtro.',
            'filtros' => $filtros,
            'resultado' => match (count($posts)) {
                0 => 'Nenhum texto encontrado.',
                1 => '1 texto encontrado.',
                default => count($posts).' textos encontrados.',
            },
            'indexar' => false,
        ]);
    }

    public function secao(Request $request, string $secao, Blog $blog): View
    {
        $nome = $blog->secoes()[$secao] ?? null;
        abort_if($nome === null, 404);

        return $this->listar($request, $blog, $blog->filtrar(secao: $secao), [
            'titulo' => $nome,
            'descricao' => 'Textos da seção '.$nome.'.',
            'secao' => $nome,
        ]);
    }

    public function tag(Request $request, string $tag, Blog $blog): View
    {
        $nome = $blog->tags()[$tag] ?? null;
        abort_if($nome === null, 404);

        return $this->listar($request, $blog, $blog->filtrar(tag: $tag), [
            'titulo' => Post::hashtag($nome) ?? $nome,
            'descricao' => 'Textos sobre '.$nome.'.',
            'filtros' => ['tag' => $tag],
        ]);
    }

    public function arquivo(Request $request, Blog $blog, string $ano, ?string $mes = null): View
    {
        $posts = $blog->filtrar(ano: (int) $ano, mes: $mes === null ? null : (int) $mes);
        abort_if($posts === [], 404);

        $nome = $mes === null ? $ano : Blog::MESES[(int) $mes].' de '.$ano;

        return $this->listar($request, $blog, $posts, [
            'titulo' => $nome,
            'descricao' => 'Textos publicados em '.mb_strtolower($nome).'.',
            'filtros' => $mes === null ? [] : ['mes' => $ano.'-'.$mes],
        ]);
    }

    /** `?mes[]=x` chega como array: vira vazio, não erro. */
    private function texto(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }

    private function slug(mixed $valor): ?string
    {
        return is_string($valor) && preg_match('/^'.Blog::SLUG.'$/D', $valor) === 1 ? $valor : null;
    }

    /**
     * @param list<Post> $posts
     * @param array{titulo?: string, descricao?: string, secao?: string, filtros?: array<string, string>, resultado?: string, indexar?: bool} $contexto
     */
    private function listar(Request $request, Blog $blog, array $posts, array $contexto = []): View
    {
        $posts = collect($posts);
        $porPagina = max(1, (int) config('blog.por_pagina'));
        $pagina = max(1, $request->integer('pagina', 1));

        abort_if($pagina > 1 && $posts->forPage($pagina, $porPagina)->isEmpty(), 404);

        $paginador = new LengthAwarePaginator(
            $posts->forPage($pagina, $porPagina)->values(),
            $posts->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'pageName' => 'pagina'],
        );
        // Na lista filtrada, a próxima página leva o filtro junto (já validado).
        // Na página da tag ou do mês o filtro está no próprio endereço.
        if ($request->routeIs(config('blog.nome_rota').'.index')) {
            $paginador->appends($contexto['filtros'] ?? []);
        }

        return view('blog::index', [
            'posts' => $paginador,
            'secoes' => $blog->secoes(),
            'tags' => $blog->tags(),
            'meses' => $blog->meses(),
            'titulo' => $contexto['titulo'] ?? null,
            'descricao' => $contexto['descricao'] ?? null,
            'secaoAtual' => $contexto['secao'] ?? null,
            'filtros' => $contexto['filtros'] ?? [],
            'resultado' => $contexto['resultado'] ?? null,
            'indexar' => $contexto['indexar'] ?? true,
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
            'relacionados' => $blog->relacionados($post, (int) config('blog.relacionados', 3)),
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
