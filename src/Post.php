<?php

namespace UrbanoDev\Blog;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class Post
{
    /** @param list<string> $tags */
    public function __construct(
        public string $slug,
        public string $titulo,
        public string $descricao,
        public CarbonImmutable $data,
        public ?CarbonImmutable $atualizado,
        public ?string $autor,
        public ?string $imagem,
        public ?string $imagemAlt,
        public ?string $imagemCredito,
        public bool $rascunho,
        public ?string $acaoTexto,
        public ?string $acaoUrl,
        public string $corpo,
        public array $tags = [],
        public ?string $parceiro = null,
        public ?string $parceiroLogo = null,
        public ?string $parceiroUrl = null,
        public ?string $secao = null,
    ) {}

    /** O endereço da seção: "Experiência da Equipe" → experiencia-da-equipe. */
    public function secaoSlug(): ?string
    {
        return $this->secao === null ? null : Str::slug($this->secao);
    }

    /** Agendado = pronto, mas com `data` no futuro: entra no ar sozinho nesse dia. */
    public function agendado(): bool
    {
        return ! $this->rascunho && ! $this->publicado();
    }

    /**
     * As tags como hashtag de rede social: sem acento, sem espaço e com a
     * inicial de cada palavra em maiúscula ("deploy automático" → #DeployAutomatico).
     *
     * @return list<string>
     */
    public function hashtags(): array
    {
        return array_values(array_unique(array_filter(array_map(self::hashtag(...), $this->tags))));
    }

    public static function hashtag(string $tag): ?string
    {
        $limpa = Str::studly(preg_replace('/[^a-z0-9 ]+/i', ' ', Str::ascii($tag)));

        return $limpa === '' ? null : '#'.$limpa;
    }

    /**
     * As tags com endereço: slug => nome ("Deploy automático" → deploy-automatico).
     * Duas grafias da mesma tag viram uma só, com o nome da primeira.
     *
     * @return array<string, string>
     */
    public function tagsComSlug(): array
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            if (($slug = Str::slug($tag)) !== '') {
                $tags[$slug] ??= $tag;
            }
        }

        return $tags;
    }

    /** "3 de novembro de 2026", sem depender do locale do servidor. */
    public function dataPorExtenso(): string
    {
        return $this->data->day.' de '.mb_strtolower(Blog::MESES[$this->data->month]).' de '.$this->data->year;
    }

    /** Título, resumo, seção e tags, em minúsculas e sem acento: é onde a busca procura. */
    public function textoDeBusca(): string
    {
        return Blog::normalizar(implode(' ', [$this->titulo, $this->descricao, $this->secao ?? '', ...$this->tags]));
    }

    /** Texto pronto para colar num post do LinkedIn: título, resumo, endereço e hashtags. */
    public function textoParaRedes(string $url): string
    {
        return trim($this->titulo."\n\n".$this->descricao."\n\nLeia: ".$url."\n\n".implode(' ', $this->hashtags()));
    }

    public function publicado(): bool
    {
        return ! $this->rascunho && $this->data->startOfDay()->lte(CarbonImmutable::now());
    }

    /** HTML cru do arquivo é removido: o texto só entra por Markdown. */
    public function html(): string
    {
        return (string) Str::markdown($this->corpo, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public function minutosDeLeitura(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->corpo), 0, 'À..ÿ') / 200));
    }
}
