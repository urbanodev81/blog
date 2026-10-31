# urbanodev/blog

Blog em arquivos Markdown para qualquer sistema Laravel 12 ou 13, com Blade ou
Inertia. Não usa banco, build, painel nem IA: escreveu, deu push, está no ar.

## Instalar

```bash
composer config repositories.blog vcs https://github.com/urbanodev81/blog
composer require urbanodev/blog:^0.1
php artisan blog:instalar
```

As páginas já respondem em `/blog`. Na pasta `resources/blog` ficam o passo a
passo de quem escreve (`_LEIA.md`) e o modelo (`_MODELO.md`).

## Configurar

`php artisan vendor:publish --tag=blog-config` copia o `config/blog.php`, com
cada opção comentada.

**Sistema Inertia:** o layout `blog::layout` já serve, porque a página é Blade
pura e não passa pelo Vue. Para usar a cor do sistema, defina
`--blog-destaque` no CSS dele.

**Sistema com layout Blade:** aponte para o layout dele:

```php
'layout' => 'layouts.site',
'secoes' => ['titulo' => 'titulo', 'descricao' => 'descricao', 'conteudo' => 'conteudo'],
'pilha_cabecalho' => 'estilos',
```

**Sitemap e llms.txt:** `app(\UrbanoDev\Blog\Blog::class)->paginas()` devolve
`caminho => "Título — descrição"`.

**Rotas com o mesmo nome ou endereço:** troque `nome_rota` e `prefixo`.

## Quando não usar

Em blog que o cliente edita pelo painel, com um conteúdo por tenant, como os do
USI e do SSB. Este pacote serve aos textos da própria casa, um conjunto por
instalação.

## Segurança

O texto só entra por Markdown: HTML cru e link `javascript:` são removidos. O
endereço só escolhe um item da lista lida da pasta, nunca monta caminho de
arquivo. Arquivo com erro fica fora do ar e não afeta o resto do site.

## Testar

```bash
composer install
vendor/bin/phpunit
```
