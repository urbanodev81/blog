<?php

namespace UrbanoDev\Blog;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lê os textos da pasta. O endereço só escolhe um item da lista lida daqui,
 * nunca monta caminho de arquivo. Arquivo com erro fica de fora, e o resto do
 * site segue: quem escreve pelo github.com não pode derrubar nada com um typo.
 */
class Blog
{
    public const SLUG = '[a-z0-9]+(?:-[a-z0-9]+)*';

    public const MESES = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    /** A busca não liga para maiúscula nem acento: "Implantação" acha "implantacao". */
    public static function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }

    /** @return list<Post> os publicados, do mais novo para o mais velho */
    public function publicados(): array
    {
        $posts = array_values(array_filter($this->ler()['posts'], fn (Post $p) => $p->publicado()));
        usort($posts, fn (Post $a, Post $b) => $b->data <=> $a->data ?: strcmp($a->slug, $b->slug));

        return $posts;
    }

    /** @return list<Post> prontos com data futura, do mais próximo ao mais distante */
    public function agendados(): array
    {
        $posts = array_values(array_filter($this->ler()['posts'], fn (Post $p) => $p->agendado()));
        usort($posts, fn (Post $a, Post $b) => $a->data <=> $b->data ?: strcmp($a->slug, $b->slug));

        return $posts;
    }

    /** @return array<string, string> slug da seção => nome, só das que têm texto publicado, em ordem alfabética */
    public function secoes(): array
    {
        $secoes = [];
        foreach ($this->publicados() as $post) {
            if ($post->secao !== null) {
                $secoes[$post->secaoSlug()] ??= $post->secao;
            }
        }
        asort($secoes, SORT_LOCALE_STRING);

        return $secoes;
    }

    /** @return list<Post> os publicados da seção, do mais novo para o mais velho */
    public function daSecao(string $slug): array
    {
        return array_values(array_filter($this->publicados(), fn (Post $p) => $p->secaoSlug() === $slug));
    }

    /** @return array<string, string> slug da tag => nome (a grafia do texto mais novo), só das que têm texto publicado, em ordem alfabética */
    public function tags(): array
    {
        $tags = [];
        foreach ($this->publicados() as $post) {
            $tags += $post->tagsComSlug();
        }
        uasort($tags, fn (string $a, string $b) => strcmp(self::normalizar($a), self::normalizar($b)));

        return $tags;
    }

    /**
     * Os meses com texto publicado, do mais novo ao mais velho.
     *
     * @return list<array{ano: int, mes: int, nome: string, total: int}>
     */
    public function meses(): array
    {
        $meses = [];
        foreach ($this->publicados() as $post) {
            $chave = $post->data->format('Y-m');
            $meses[$chave] ??= ['ano' => $post->data->year, 'mes' => $post->data->month, 'nome' => self::MESES[$post->data->month].' de '.$post->data->year, 'total' => 0];
            $meses[$chave]['total']++;
        }

        return array_values($meses);
    }

    /**
     * Os publicados que passam em todos os filtros dados. Filtro null não
     * filtra. Na busca, cada palavra tem de aparecer no título, no resumo, na
     * seção ou nas tags.
     *
     * @return list<Post>
     */
    public function filtrar(?string $secao = null, ?string $tag = null, ?int $ano = null, ?int $mes = null, ?string $busca = null): array
    {
        $palavras = preg_split('/\s+/', self::normalizar(trim((string) $busca)), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($this->publicados(), function (Post $p) use ($secao, $tag, $ano, $mes, $palavras) {
            if ($secao !== null && $p->secaoSlug() !== $secao) {
                return false;
            }
            if ($tag !== null && ! array_key_exists($tag, $p->tagsComSlug())) {
                return false;
            }
            if ($ano !== null && $p->data->year !== $ano) {
                return false;
            }
            if ($mes !== null && $p->data->month !== $mes) {
                return false;
            }
            $onde = $palavras === [] ? '' : $p->textoDeBusca();
            foreach ($palavras as $palavra) {
                if (! str_contains($onde, $palavra)) {
                    return false;
                }
            }

            return true;
        }));
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
        foreach ($this->secoes() as $slug => $nome) {
            $paginas[$prefixo.'/secao/'.$slug] = $nome.' — '.config('blog.titulo');
        }
        foreach ($this->tags() as $slug => $nome) {
            $paginas[$prefixo.'/tag/'.$slug] = 'Textos sobre '.$nome.' — '.config('blog.titulo');
        }
        foreach ($this->meses() as $mes) {
            $paginas[$prefixo.'/'.$mes['ano'].'/'.sprintf('%02d', $mes['mes'])] = $mes['nome'].' — '.config('blog.titulo');
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
