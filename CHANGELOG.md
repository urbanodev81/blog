# Changelog

## [Não lançado]

- Campo `secao` (ex.: `Experiência da Equipe`): vira chapéu no texto e na
  lista, `articleSection` no JSON-LD e página própria em `/<prefixo>/secao/<slug>`,
  com o sitemap junto. A lista ganha a barra de seções; `Blog::secoes()` serve
  para o site montar menu. Seção sem texto publicado não tem endereço.
- Textos em Markdown com cabeçalho, sem banco, servidos em Blade.
- Comandos `blog:instalar`, `blog:novo` e `blog:conferir`.
- Rascunho, data futura, sitemap, JSON-LD e botão de ação.
- Botões de compartilhar (LinkedIn, WhatsApp, Facebook, X, e-mail), "Copiar
  link" e "Copiar para o LinkedIn", que leva título, resumo, endereço e hashtags.
  A lista vem de `blog.compartilhar`.
- Campo `tags`, que vira `#Hashtags` no fim do texto e `keywords` no JSON-LD.
- Texto em parceria: `parceiro`, `parceiro_logo` e `parceiro_url` mostram "Em
  parceria com" e o logo, no texto e na lista.
- `imagem_credito` vira legenda da capa; `secoes.imagem` manda a capa para a
  prévia das redes (og:image) do layout que tiver essa seção.
- `blog:conferir` lista os textos agendados com a data em que entram no ar.
