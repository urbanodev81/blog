@php($rota = config('blog.nome_rota'))
@extends(config('blog.layout'))

@section(config('blog.secoes.titulo'), $titulo ? $titulo.' — '.config('blog.titulo') : config('blog.titulo'))
@section(config('blog.secoes.descricao'), $descricao ?? (config('blog.introducao') ?? config('blog.titulo')))
{{-- Resultado de busca não vai para buscador: cada combinação seria uma página. --}}
@if (! $indexar && config('blog.secoes.robots'))
    @section(config('blog.secoes.robots'), 'noindex, follow')
@endif

@push(config('blog.pilha_cabecalho'))
    <link rel="stylesheet" href="{{ route($rota.'.estilo') }}">
    @if (! $indexar && ! config('blog.secoes.robots'))
        <meta name="robots" content="noindex, follow">
    @endif
@endpush

@section(config('blog.secoes.conteudo'))
<div class="blog">
    @if ($titulo)
        <p><a href="{{ route($rota.'.index') }}">← {{ config('blog.titulo') }}</a></p>
        <h1>{{ $titulo }}</h1>
    @else
        <h1>{{ config('blog.titulo') }}</h1>
        @if (config('blog.introducao'))
            <p class="blog-introducao">{{ config('blog.introducao') }}</p>
        @endif
    @endif

    @if ($secoes !== [])
        <nav class="blog-secoes" aria-label="Seções">
            @foreach ($secoes as $slug => $nome)
                <a href="{{ route($rota.'.secao', $slug) }}" @if ($nome === $secaoAtual) aria-current="page" @endif>{{ $nome }}</a>
            @endforeach
        </nav>
    @endif

    @if ($meses !== [])
        {{-- Formulário GET comum: funciona sem script, e o endereço do resultado pode ser guardado. --}}
        <form class="blog-filtro" method="get" action="{{ route($rota.'.index') }}" role="search" aria-label="Buscar e filtrar textos">
            <div class="blog-filtro-busca">
                <label for="blog-q">Buscar</label>
                <input type="search" id="blog-q" name="q" value="{{ $filtros['q'] ?? '' }}" maxlength="80" placeholder="Palavra do título, resumo ou tag">
            </div>
            @if (count($secoes) > 1)
                <div>
                    <label for="blog-secao">Seção</label>
                    <select id="blog-secao" name="secao">
                        <option value="">Todas</option>
                        @foreach ($secoes as $slug => $nome)
                            <option value="{{ $slug }}" @selected(($filtros['secao'] ?? null) === $slug)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($tags !== [])
                <div>
                    <label for="blog-tag">Tag</label>
                    <select id="blog-tag" name="tag">
                        <option value="">Todas</option>
                        @foreach ($tags as $slug => $nome)
                            <option value="{{ $slug }}" @selected(($filtros['tag'] ?? null) === $slug)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if (count($meses) > 1)
                <div>
                    <label for="blog-mes">Mês</label>
                    <select id="blog-mes" name="mes">
                        <option value="">Todos</option>
                        @foreach ($meses as $m)
                            @php($valor = sprintf('%04d-%02d', $m['ano'], $m['mes']))
                            <option value="{{ $valor }}" @selected(($filtros['mes'] ?? null) === $valor)>{{ $m['nome'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="blog-filtro-acoes">
                <button type="submit">Filtrar</button>
                @if ($filtros !== [])
                    <a href="{{ route($rota.'.index') }}">Limpar</a>
                @endif
            </div>
        </form>
    @endif

    @if ($resultado)
        <p class="blog-resultado" role="status">{{ $resultado }}</p>
    @endif

    @foreach ($posts as $post)
        <article class="blog-cartao">
            @if ($post->secao && ! $secaoAtual)
                <p class="blog-chapeu"><a href="{{ route($rota.'.secao', $post->secaoSlug()) }}">{{ $post->secao }}</a></p>
            @endif
            <h2><a href="{{ route($rota.'.show', $post->slug) }}">{{ $post->titulo }}</a></h2>
            <p>{{ $post->descricao }}</p>
            <p class="blog-meta">
                <time datetime="{{ $post->data->toDateString() }}">{{ $post->dataPorExtenso() }}</time>
                · {{ $post->minutosDeLeitura() }} min de leitura
                @if ($post->parceiro)
                    · <span class="blog-cartao-parceiro">@if ($post->parceiroLogo)<img src="{{ $post->parceiroLogo }}" alt="">@endif com {{ $post->parceiro }}</span>
                @endif
            </p>
        </article>
    @endforeach

    @foreach ((array) config('blog.encaixes.fim_do_indice', []) as $encaixe)
        @include($encaixe, ['inicio' => $inicio])
    @endforeach

    @if ($meses === [])
        <p>Nenhum texto publicado ainda.</p>
    @endif

    @if ($posts->hasPages())
        <nav class="blog-paginas" aria-label="Páginas">
            @if ($posts->previousPageUrl()) <a href="{{ $posts->previousPageUrl() }}" rel="prev">Anteriores</a> @endif
            @if ($posts->nextPageUrl()) <a href="{{ $posts->nextPageUrl() }}" rel="next">Seguintes</a> @endif
        </nav>
    @endif

    @if ($meses !== [])
        <nav class="blog-arquivo" aria-labelledby="blog-arquivo-titulo">
            <h2 id="blog-arquivo-titulo">Arquivo</h2>
            <ul>
                @foreach ($meses as $m)
                    <li><a href="{{ route($rota.'.mes', [$m['ano'], sprintf('%02d', $m['mes'])]) }}">{{ $m['nome'] }}</a> ({{ $m['total'] }})</li>
                @endforeach
            </ul>
        </nav>
    @endif

    @foreach ((array) config('blog.encaixes.pagina', []) as $encaixe)
        @include($encaixe, ['post' => null])
    @endforeach
</div>
@endsection
