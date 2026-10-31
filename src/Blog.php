<?php

namespace UrbanoDev\Blog;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lê os textos da pasta. O endereço só escolhe um item da lista lida daqui,
 * nunca monta caminho de arquivo. Arquivo com erro fica de fora, e o resto do
 * site segue: quem escreve pelo github.com não pode derrubar nada com um typo.
 */
class Blog
{
    public const SLUG = '[a-z0-9]+(?:-[a-z0-9]+)*';

    /** @return list<Post> os publicados, do mais novo para o mais velho */
    public function publicados(): array
    {
        $posts = array_values(array_filter($this->ler()['posts'], fn (Post $p) => $p->publicado()));
        usort($posts, fn (Post $a, Post $b) => $b->data <=> $a->data ?: strcmp($a->slug, $b->slug));

        return $posts;
    }

    public function achar(string $slug): ?Post
    {
        foreach ($this->publicados() as $post) {
            if ($post->slug === $slug) {
                return $post;
            }
        }

        return null;
    }

    /** @return array<string, string> caminho => "Título — descrição", para sitemap e llms.txt */
    public function paginas(): array
    {
        $posts = $this->publicados();
        if ($posts === []) {
            return [];
        }

        $prefixo = '/'.trim((string) config('blog.prefixo'), '/');
        $paginas = [$prefixo => (string) config('blog.titulo')];
        foreach ($posts as $post) {
            $paginas[$prefixo.'/'.$post->slug] = $post->titulo.' — '.$post->descricao;
        }

        return $paginas;
    }

    /** @return array<string, list<string>> arquivo => erros */
    public function erros(): array
    {
        return $this->ler()['erros'];
    }

    /** @return array{posts: list<Post>, erros: array<string, list<string>>} */
    private function ler(): array
    {
        $posts = [];
        $erros = [];

        foreach (glob(rtrim((string) config('blog.pasta'), '/').'/*.md') ?: [] as $arquivo) {
            $nome = basename($arquivo);
            if (str_starts_with($nome, '_')) {
                continue;
            }

            $problemas = [];
            $post = $this->interpretar(basename($nome, '.md'), (string) file_get_contents($arquivo), $problemas);

            if ($post === null) {
                $erros[$nome] = $problemas;
                Log::warning('Blog: texto fora do ar', ['arquivo' => $nome, 'erros' => $problemas]);
            } else {
                $posts[] = $post;
            }
        }

        return ['posts' => $posts, 'erros' => $erros];
    }

    /** @param list<string> $problemas */
    private function interpretar(string $slug, string $texto, array &$problemas): ?Post
    {
        $texto = str_replace("\r\n", "\n", preg_replace('/^\xEF\xBB\xBF/', '', $texto));

        if (preg_match('/^'.self::SLUG.'$/D', $slug) !== 1) {
            $problemas[] = 'nome do arquivo: use só letras minúsculas sem acento, números e hífen';
        }
        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $texto, $partes) !== 1) {
            $problemas[] = 'falta o cabeçalho entre duas linhas ---';

            return null;
        }

        $campos = [];
        foreach (explode("\n", $partes[1]) as $n => $linha) {
            if (trim($linha) === '' || str_starts_with(ltrim($linha), '#')) {
                continue;
            }
            if (preg_match('/^([a-z_]+):\s*(.*)$/', $linha, $m) !== 1) {
                $problemas[] = 'linha '.($n + 2).' do cabeçalho: escreva "campo: valor"';

                continue;
            }
            $campos[$m[1]] = trim(trim($m[2]), "\"'");
        }

        foreach (['titulo', 'descricao', 'data'] as $obrigatorio) {
            if (($campos[$obrigatorio] ?? '') === '') {
                $problemas[] = "falta o campo {$obrigatorio}";
            }
        }
        if (trim($partes[2]) === '') {
            $problemas[] = 'o texto está vazio';
        }

        $data = $this->data($campos['data'] ?? '', 'data', $problemas);
        $atualizado = ($campos['atualizado'] ?? '') !== '' ? $this->data($campos['atualizado'], 'atualizado', $problemas) : null;

        foreach (['imagem', 'acao_url'] as $campo) {
            if (($campos[$campo] ?? '') !== '' && preg_match('~^(https?://|/(?!/))~i', $campos[$campo]) !== 1) {
                $problemas[] = "{$campo}: use um endereço que comece com https:// ou /";
            }
        }

        if ($problemas !== [] || $data === null) {
            return null;
        }

        return new Post(
            slug: $slug,
            titulo: $campos['titulo'],
            descricao: $campos['descricao'],
            data: $data,
            atualizado: $atualizado,
            autor: ($campos['autor'] ?? '') ?: null,
            imagem: ($campos['imagem'] ?? '') ?: null,
            imagemAlt: ($campos['imagem_alt'] ?? '') ?: null,
            rascunho: in_array(mb_strtolower($campos['rascunho'] ?? ''), ['sim', 'true'], true),
            acaoTexto: ($campos['acao_texto'] ?? '') ?: null,
            acaoUrl: ($campos['acao_url'] ?? '') ?: null,
            corpo: $partes[2],
        );
    }

    /** @param list<string> $problemas */
    private function data(string $valor, string $campo, array &$problemas): ?CarbonImmutable
    {
        if ($valor === '') {
            return null;
        }

        try {
            $data = CarbonImmutable::createFromFormat('!Y-m-d', $valor);
            if ($data !== null && $data->format('Y-m-d') === $valor) {
                return $data;
            }
        } catch (Throwable) {
            // cai na mensagem abaixo
        }
        $problemas[] = "{$campo}: use o formato 2026-10-30";

        return null;
    }
}
