<?php

return [
    // Endereço das páginas: /blog e /blog/{slug}.
    'prefixo' => env('BLOG_PREFIXO', 'blog'),

    // Prefixo do nome das rotas. Troque se o sistema já tiver rotas `blog.*`.
    'nome_rota' => 'blog',

    'middleware' => ['web'],

    // Onde moram os .md. Arquivo que começa com `_` não vira página.
    'pasta' => resource_path('blog'),

    'titulo' => 'Blog',
    'introducao' => null,
    'por_pagina' => 12,

    // Botão no fim de todo texto que não tiver `acao_url` própria. O `{titulo}`
    // é trocado pelo título do texto, já codificado para URL, e `{slug}` pelo
    // nome do arquivo: é assim que o lead diz de qual texto veio.
    'acao_texto' => null,
    'acao_url' => null,

    // O casco. `blog::layout` serve para sistema Inertia, que não tem layout
    // Blade. Quem tem o seu aponta para ele e diz os nomes das seções.
    'layout' => 'blog::layout',
    'secoes' => ['titulo' => 'titulo', 'descricao' => 'descricao', 'conteudo' => 'conteudo'],
    'pilha_cabecalho' => 'blog_cabecalho',
];
