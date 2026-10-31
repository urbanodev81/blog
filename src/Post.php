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
    ) {}

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
        return array_values(array_unique(array_filter(array_map(
            fn (string $tag) => ($limpa = Str::studly(preg_replace('/[^a-z0-9 ]+/i', ' ', Str::ascii($tag)))) === '' ? null : '#'.$limpa,
            $this->tags,
        ))));
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
