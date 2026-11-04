# Changelog

## [Não lançado]

- Estilo para `figure`/`figcaption` no texto e para `.blog-video` (iframe em
  16:9, na largura do texto), que é o que a fonte do banco do urbisdev gera
  para vídeo do YouTube ou do Vimeo (v0.5.1).
- Fontes de texto (v0.5.0): o contrato `Fonte` e a config `blog.fontes`, em
  ordem de prioridade. O padrão continua sendo só `FonteDeArquivos`, e quem não
  mexe na config não vê mudança nenhuma. Uma fonte do sistema (banco) entra na
  frente: o mesmo slug fica com a primeira, e fonte que quebra vai para o log
  sem derrubar o blog. O `Post` ganhou `htmlPronto` (HTML que a fonte já
  saneou) e `fonte` (quem trouxe o texto), e o tempo de leitura conta as
  palavras desse HTML.
- "Leia também" no fim de cada texto: até 3 outros (`blog.relacionados`; 0
  esconde), primeiro os que dividem tag ou seção, depois os mais novos.
  `Blog::relacionados()` serve para o site usar em outro lugar.
- Tags clicáveis: cada tag do texto vira link para `/<prefixo>/tag/<slug>`, com
  a lista dos textos dela. Grafias diferentes da mesma tag viram uma só.
- Arquivo por ano e mês: `/<prefixo>/2026` e `/<prefixo>/2026/11`, e a lista
  ganha o bloco "Arquivo" com "Novembro de 2026 (3)". A data aparece no cartão
  e no texto, por extenso e sem depender do locale do servidor.
- Busca e filtro no topo da lista: palavra (título, resumo, seção e tags, sem
  ligar para acento nem maiúscula), seção, tag e mês. É formulário GET comum,
  sem script; a paginação leva o filtro junto; valor fora do formato é
  ignorado. O resultado sai com `noindex` (pela seção `secoes.robots` do layout,
  quando houver, ou pela pilha do cabeçalho).
- Tags e meses entram no sitemap (`Blog::paginas()`); `Blog::tags()`,
  `Blog::meses()` e `Blog::filtrar()` servem para o site montar menu.
- Arquivo com nome de ano (`2026.md`) vira erro no `blog:conferir`.

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
