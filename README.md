# urbanodev/blog

Blog em arquivos Markdown para qualquer sistema Laravel 12 ou 13, com Blade ou
Inertia. Não usa banco, build, painel nem IA: escreveu, deu push, está no ar.

## Instalar

```bash
composer config repositories.blog vcs https://github.com/urbanodev81/blog
composer require urbanodev/blog:^0.7
php artisan blog:instalar
```

As páginas já respondem em `/blog`. Além do texto (`/blog/<nome>`) existem:

| Endereço | O que mostra |
|---|---|
| `/blog/secao/<seção>` | os textos de uma seção (campo `secao`) |
| `/blog/tag/<tag>` | os textos de uma tag (campo `tags`); as tags do texto viram link para cá |
| `/blog/2026` e `/blog/2026/11` | o arquivo do ano e do mês; a lista mostra "Novembro de 2026 (3)" |
| `/blog?q=docker&secao=…&tag=…&mes=2026-11` | busca e filtro: formulário GET no topo da lista, sem script. A busca ignora acento e maiúscula e procura no título, no resumo, na seção e nas tags |

No fim de cada texto vem o "Leia também": até 3 outros, primeiro os que dividem
tag ou seção, depois os mais novos (`relacionados` no config; 0 esconde).

O resultado da busca sai com `noindex`. Nome de arquivo que é só um ano
(`2026.md`) é recusado, porque o endereço é do arquivo daquele ano. Na pasta `resources/blog` ficam o passo a
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

**Outra fonte além dos `.md` (banco, por exemplo):** implemente
`UrbanoDev\Blog\Fonte` (um método, `ler()`, que devolve os `Post`) e ponha a
classe antes da de arquivos:

```php
'fontes' => [App\Blog\FonteDoBanco::class, UrbanoDev\Blog\FonteDeArquivos::class],
```

O mesmo slug nas duas fica com o da primeira. Se uma fonte quebrar (banco fora),
ela vai para o log e o blog segue com as outras, então os `.md` continuam sendo a
reserva. O `Post` que traz `htmlPronto` é impresso como veio: **o saneamento
desse HTML é da fonte**, não do pacote.

**Bloco próprio dentro das páginas do blog (comentários, "ajudou?", mais
lidos, botão de contato):** o pacote não tem banco, mas inclui views do sistema
em três pontos fixos:

```php
'encaixes' => [
    'fim_do_texto' => ['blog.leitores'],   // depois de "Compartilhar"; recebe $post
    'fim_do_indice' => ['blog.mais-lidos'], // depois dos cartões; recebe $inicio
    'pagina' => ['blog.whatsapp'],          // fecha toda página; $post é null na lista
],
```

`$inicio` só é `true` em `/blog` sem filtro, na página 1.

**Foto dentro do texto, com o texto ao lado:** o estilo e o "ampliar ao
clicar" vêm do pacote. Basta o HTML que a fonte entrega trazer estes atributos
na `<img>` (o editor do sistema grava, e o saneador dele só deixa passar estes
valores):

| Atributo | Valores | Efeito |
|---|---|---|
| `data-tamanho` | `pequena` (33%), `media` (50%) | sem ele, a foto ocupa a largura do texto |
| `data-alinhar` | `esquerda`, `centro`, `direita` | esquerda e direita põem o texto ao lado da foto; título, `hr`, `pre` e `figure` descem para baixo dela |
| `data-ampliar` | `sim` | clicar (ou Enter) abre a foto grande num `<dialog>`; Esc ou clique fecha |

Abaixo de 600 px a foto volta ao centro, sem texto ao lado. Texto em `.md` não
usa isso, porque o Markdown remove HTML cru.

## Quando não usar

Em blog que o cliente edita pelo painel, com um conteúdo por tenant, como os do
USI e do SSB. Este pacote serve aos textos da própria casa, um conjunto por
instalação.

## Segurança

O texto de arquivo só entra por Markdown: HTML cru e link `javascript:` são
removidos. HTML de outra fonte (`htmlPronto`) chega saneado por ela. O
endereço só escolhe um item da lista lida da pasta, nunca monta caminho de
arquivo. Arquivo com erro fica fora do ar e não afeta o resto do site.

## Testar

```bash
composer install
vendor/bin/phpunit
```
