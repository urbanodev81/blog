@extends(config('blog.layout'))

@section(config('blog.secoes.titulo'), $post->titulo)
@section(config('blog.secoes.descricao'), $post->descricao)

@php
    $artigo = array_filter([
        '@'.'context' => 'https://schema.org',
        '@'.'type' => 'Article',
        'headline' => $post->titulo,
        'description' => $post->descricao,
        'datePublished' => $post->data->toDateString(),
        'dateModified' => ($post->atualizado ?? $post->data)->toDateString(),
        'author' => $post->autor ? ['@'.'type' => 'Person', 'name' => $post->autor] : null,
        'image' => $post->imagem ? url($post->imagem) : null,
        'mainEntityOfPage' => url()->current(),
    ]);
@endphp

@push(config('blog.pilha_cabecalho'))
    <link rel="stylesheet" href="{{ route(config('blog.nome_rota').'.estilo') }}">
    <script type="application/ld+json">{!! json_encode($artigo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section(config('blog.secoes.conteudo'))
<div class="blog">
    <p><a href="{{ route(config('blog.nome_rota').'.index') }}">← {{ config('blog.titulo') }}</a></p>
    <article>
        <h1>{{ $post->titulo }}</h1>
        <p class="blog-meta">
            @if ($post->autor) {{ $post->autor }} · @endif
            {{ $post->minutosDeLeitura() }} min de leitura
            @if ($post->atualizado) · revisto em <time datetime="{{ $post->atualizado->toDateString() }}">{{ $post->atualizado->format('d/m/Y') }}</time> @endif
        </p>
        @if ($post->imagem)
            <img class="blog-imagem" src="{{ $post->imagem }}" alt="{{ $post->imagemAlt ?? '' }}">
        @endif
        <div class="blog-texto">{!! $post->html() !!}</div>
    </article>
    @if ($acao)
        <p><a class="blog-acao" href="{{ $acao['url'] }}">{{ $acao['texto'] }}</a></p>
    @endif
</div>
@endsection
