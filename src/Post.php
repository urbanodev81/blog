<?php

namespace UrbanoDev\Blog;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class Post
{
    public function __construct(
        public string $slug,
        public string $titulo,
        public string $descricao,
        public CarbonImmutable $data,
        public ?CarbonImmutable $atualizado,
        public ?string $autor,
        public ?string $imagem,
        public ?string $imagemAlt,
        public bool $rascunho,
        public ?string $acaoTexto,
        public ?string $acaoUrl,
        public string $corpo,
    ) {}

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
