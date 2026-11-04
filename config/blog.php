<?php

return [
    // Endereço das páginas: /blog e /blog/{slug}.
    'prefixo' => env('BLOG_PREFIXO', 'blog'),

    // Prefixo do nome das rotas. Troque se o sistema já tiver rotas `blog.*`.
    'nome_rota' => 'blog',

    'middleware' => ['web'],

    // De onde vêm os textos, em ordem: o mesmo slug em duas fontes fica com o
    // da primeira. Sistema com banco põe a sua fonte (implementa
    // UrbanoDev\Blog\Fonte) antes desta, e os .md seguem valendo de reserva.
    'fontes' => [UrbanoDev\Blog\FonteDeArquivos::class],

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

    // Botões de compartilhar no fim de cada texto, nesta ordem. Tire o que não
    // quiser; [] esconde a barra. São links comuns, sem script de rede social.
    // O "Copiar link" e o "Copiar para o LinkedIn" (título, resumo, endereço e
    // hashtags das `tags`) aparecem quando o navegador roda o script do pacote.
    'compartilhar' => ['linkedin', 'whatsapp', 'facebook', 'x', 'email'],

    // Quantos textos o "Leia também" mostra no fim de cada texto: primeiro os
    // do mesmo assunto (tags e seção), depois os mais novos. 0 esconde o bloco.
    'relacionados' => 3,

    // O casco. `blog::layout` serve para sistema Inertia, que não tem layout
    // Blade. Quem tem o seu aponta para ele e diz os nomes das seções.
    'layout' => 'blog::layout',
    // `imagem` é opcional: se o layout tiver uma seção para a imagem da prévia
    // nas redes (og:image), dê o nome dela e o texto com `imagem` a preenche.
    // `robots` também: se o layout tiver uma seção para a meta robots, o
    // resultado da busca a preenche com "noindex, follow". Sem ela, o pacote
    // põe a meta na pilha do cabeçalho.
    'secoes' => ['titulo' => 'titulo', 'descricao' => 'descricao', 'conteudo' => 'conteudo', 'imagem' => null, 'robots' => null],
    'pilha_cabecalho' => 'blog_cabecalho',
];
