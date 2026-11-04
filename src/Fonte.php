<?php

namespace UrbanoDev\Blog;

/**
 * De onde vêm os textos. O pacote traz a de arquivos; o sistema que quiser
 * outra (banco, por exemplo) implementa esta e a põe em `blog.fontes`.
 *
 * Fonte que devolve `Post` com `htmlPronto` responde pelo saneamento desse
 * HTML: o pacote o imprime como veio.
 */
interface Fonte
{
    /** @return array{posts: list<Post>, erros: array<string, list<string>>} erros: identificador => mensagens */
    public function ler(): array;
}
