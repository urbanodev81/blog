# Como publicar um texto, sem ajuda de ninguém

Cada arquivo `.md` desta pasta é uma página. Arquivo que começa com `_` não
vira página.

## Pelo site do GitHub, inclusive pelo celular

1. Abra esta pasta no github.com, na branch que publica este sistema.
2. **Add file → Create new file.**
3. Dê o nome em minúsculas, sem acento e com hífen: `quanto-custa-um-sistema.md`.
   Esse nome é o endereço da página.
4. Copie o conteúdo de `_MODELO.md` e escreva. Troque a `data`.
5. Para publicar, apague a linha `rascunho: sim`.
6. **Commit changes**, espere o deploy e abra a página.

## Pelo terminal

```bash
php artisan blog:novo quanto-custa-um-sistema   # cria o arquivo como rascunho
php artisan blog:conferir                       # diz o que está errado
```

## Campos do cabeçalho

| Campo | Obrigatório | Para quê |
|---|---|---|
| `titulo` | sim | título da página e do Google |
| `descricao` | sim | a frase que aparece no Google |
| `data` | sim | `2026-10-30`; data futura só aparece nesse dia |
| `rascunho` | não | `sim` deixa fora do ar |
| `atualizado` | não | a data da última revisão |
| `autor` | não | quem escreveu |
| `imagem`, `imagem_alt` | não | endereço da imagem de capa e o que ela mostra |
| `imagem_credito` | não | legenda com a origem da foto (ex.: `Foto: Wikimedia Commons, CC0`) |
| `tags` | não | até 8, separadas por vírgula; viram `#Hashtags` no fim do texto e no "Copiar para o LinkedIn" |
| `parceiro` | não | nome do parceiro que escreveu junto; aparece "Em parceria com" |
| `parceiro_logo`, `parceiro_url` | não | logo quadrado do parceiro e o site dele |
| `acao_texto`, `acao_url` | não | o botão do fim do texto |

## Agendar

Escreva tudo, apague `rascunho: sim` e ponha em `data` o dia em que o texto deve
entrar no ar. Até lá ele não tem endereço, não aparece na lista nem no Google;
no dia, entra sozinho, sem deploy. `php artisan blog:conferir` lista os
agendados com a data.

## Imagens

Foto só de banco gratuito com licença que permita uso comercial (Wikimedia
Commons com CC0 ou domínio público, Unsplash, Pexels) ou feita por nós.
**Baixe e guarde no próprio site** (`public/`): o site não carrega imagem de
outro endereço. Capa em 1200×630, que é o tamanho da prévia no LinkedIn e no
WhatsApp. Imagem no meio do texto: `![o que ela mostra](/caminho/foto.jpg)`.

## Compartilhar

No fim de cada texto há botões de LinkedIn, WhatsApp, Facebook, X e e-mail, e
"Copiar para o LinkedIn", que copia título, resumo, endereço e as hashtags das
`tags`: é só colar num post novo.

## Texto que não apareceu

Rode `php artisan blog:conferir`, ou procure `Blog: texto fora do ar` no log. O
arquivo com erro fica fora do ar e não afeta o resto do site. Os erros mais
comuns: faltou um dos campos obrigatórios, a data está em outro formato ou o
nome do arquivo tem maiúscula, espaço ou acento.

## Como escrever

- Responda à pergunta logo na primeira frase.
- Conte o que só você sabe: caso real, número real, erro real.
- Texto genérico não ajuda ninguém e nem aparece no Google.
- Cliente só com autorização dele.
