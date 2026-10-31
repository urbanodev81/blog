@extends(config('blog.layout'))

@section(config('blog.secoes.titulo'), config('blog.titulo'))
@section(config('blog.secoes.descricao'), config('blog.introducao') ?? config('blog.titulo'))

@push(config('blog.pilha_cabecalho'))
    <link rel="stylesheet" href="{{ route(config('blog.nome_rota').'.estilo') }}">
@endpush

@section(config('blog.secoes.conteudo'))
<div class="blog">
    <h1>{{ config('blog.titulo') }}</h1>
    @if (config('blog.introducao'))
        <p class="blog-introducao">{{ config('blog.introducao') }}</p>
    @endif

    @forelse ($posts as $post)
        <article class="blog-cartao">
            <h2><a href="{{ route(config('blog.nome_rota').'.show', $post->slug) }}">{{ $post->titulo }}</a></h2>
            <p>{{ $post->descricao }}</p>
            <p class="blog-meta">{{ $post->minutosDeLeitura() }} min de leitura</p>
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
