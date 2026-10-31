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
| `imagem`, `imagem_alt` | não | endereço da imagem e o que ela mostra |
| `acao_texto`, `acao_url` | não | o botão do fim do texto |

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
