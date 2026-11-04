<?php

namespace UrbanoDev\Blog;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A fonte de sempre: os `.md` da pasta `blog.pasta`. Arquivo com erro fica de
 * fora (e vai para o log), e o resto do blog segue.
 */
class FonteDeArquivos implements Fonte
{
    /** @return array{posts: list<Post>, erros: array<string, list<string>>} */
    public function ler(): array
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

        if (preg_match('/^'.Blog::SLUG.'$/D', $slug) !== 1) {
            $problemas[] = 'nome do arquivo: use só letras minúsculas sem acento, números e hífen';
        }
        if (preg_match('/^\d{4}$/', $slug) === 1) {
            $problemas[] = 'nome do arquivo: não pode ser só um ano, porque /2026 é o arquivo daquele ano';
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

        foreach (['imagem', 'acao_url', 'parceiro_logo', 'parceiro_url'] as $campo) {
            if (($campos[$campo] ?? '') !== '' && preg_match('~^(https?://|/(?!/))~i', $campos[$campo]) !== 1) {
                $problemas[] = "{$campo}: use um endereço que comece com https:// ou /";
            }
        }

        if (($campos['parceiro_logo'] ?? '').($campos['parceiro_url'] ?? '') !== '' && ($campos['parceiro'] ?? '') === '') {
            $problemas[] = 'parceiro_logo e parceiro_url pedem o campo parceiro, com o nome dele';
        }

        $tags = array_values(array_filter(array_map('trim', explode(',', $campos['tags'] ?? '')), fn (string $t) => $t !== ''));
        if (($campos['secao'] ?? '') !== '' && \Illuminate\Support\Str::slug($campos['secao']) === '') {
            $problemas[] = 'secao: use um nome com letras ou números';
        }
        if (mb_strlen($campos['secao'] ?? '') > 60) {
            $problemas[] = 'secao: no máximo 60 caracteres';
        }

        if (count($tags) > 8) {
            $problemas[] = 'tags: no máximo 8, separadas por vírgula';
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
            imagemCredito: ($campos['imagem_credito'] ?? '') ?: null,
            rascunho: in_array(mb_strtolower($campos['rascunho'] ?? ''), ['sim', 'true'], true),
            acaoTexto: ($campos['acao_texto'] ?? '') ?: null,
            acaoUrl: ($campos['acao_url'] ?? '') ?: null,
            corpo: $partes[2],
            tags: $tags,
            parceiro: ($campos['parceiro'] ?? '') ?: null,
            parceiroLogo: ($campos['parceiro_logo'] ?? '') ?: null,
            parceiroUrl: ($campos['parceiro_url'] ?? '') ?: null,
            secao: ($campos['secao'] ?? '') ?: null,
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
