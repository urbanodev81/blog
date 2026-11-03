@extends(config('blog.layout'))

@section(config('blog.secoes.titulo'), $secaoAtual ? $secaoAtual.' — '.config('blog.titulo') : config('blog.titulo'))
@section(config('blog.secoes.descricao'), $secaoAtual ? 'Textos da seção '.$secaoAtual.'.' : (config('blog.introducao') ?? config('blog.titulo')))

@push(config('blog.pilha_cabecalho'))
    <link rel="stylesheet" href="{{ route(config('blog.nome_rota').'.estilo') }}">
@endpush

@section(config('blog.secoes.conteudo'))
<div class="blog">
    @if ($secaoAtual)
        <p><a href="{{ route(config('blog.nome_rota').'.index') }}">← {{ config('blog.titulo') }}</a></p>
        <h1>{{ $secaoAtual }}</h1>
    @else
        <h1>{{ config('blog.titulo') }}</h1>
        @if (config('blog.introducao'))
            <p class="blog-introducao">{{ config('blog.introducao') }}</p>
        @endif
    @endif

    @if ($secoes !== [])
        <nav class="blog-secoes" aria-label="Seções">
            @foreach ($secoes as $slug => $nome)
                <a href="{{ route(config('blog.nome_rota').'.secao', $slug) }}" @if ($nome === $secaoAtual) aria-current="page" @endif>{{ $nome }}</a>
            @endforeach
        </nav>
    @endif

    @forelse ($posts as $post)
        <article class="blog-cartao">
            @if ($post->secao && ! $secaoAtual)
                <p class="blog-chapeu"><a href="{{ route(config('blog.nome_rota').'.secao', $post->secaoSlug()) }}">{{ $post->secao }}</a></p>
            @endif
            <h2><a href="{{ route(config('blog.nome_rota').'.show', $post->slug) }}">{{ $post->titulo }}</a></h2>
            <p>{{ $post->descricao }}</p>
            <p class="blog-meta">
                {{ $post->minutosDeLeitura() }} min de leitura
                @if ($post->parceiro)
                    · <span class="blog-cartao-parceiro">@if ($post->parceiroLogo)<img src="{{ $post->parceiroLogo }}" alt="">@endif com {{ $post->parceiro }}</span>
                @endif
            </p>
        </article>
    @empty
        <p>Nenhum texto publicado ainda.</p>
    @endforelse

    @if ($posts->hasPages())
        <nav class="blog-paginas" aria-label="Páginas">
            @if ($posts->previousPageUrl()) <a href="{{ $posts->previousPageUrl() }}" rel="prev">Anteriores</a> @endif
            @if ($posts->nextPageUrl()) <a href="{{ $posts->nextPageUrl() }}" rel="next">Seguintes</a> @endif
        </nav>
    @endif
</div>
@endsection
