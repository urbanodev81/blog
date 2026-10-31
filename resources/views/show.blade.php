@extends(config('blog.layout'))

@section(config('blog.secoes.titulo'), $post->titulo)
@section(config('blog.secoes.descricao'), $post->descricao)
@if (config('blog.secoes.imagem') && $post->imagem)
    @section(config('blog.secoes.imagem'), url($post->imagem))
@endif

@php
    $artigo = array_filter([
        '@'.'context' => 'https://schema.org',
        '@'.'type' => 'Article',
        'headline' => $post->titulo,
        'description' => $post->descricao,
        'datePublished' => $post->data->toDateString(),
        'dateModified' => ($post->atualizado ?? $post->data)->toDateString(),
        'author' => array_values(array_filter([
            $post->autor ? ['@'.'type' => 'Person', 'name' => $post->autor] : null,
            $post->parceiro ? array_filter(['@'.'type' => 'Organization', 'name' => $post->parceiro, 'url' => $post->parceiroUrl, 'logo' => $post->parceiroLogo ? url($post->parceiroLogo) : null]) : null,
        ])) ?: null,
        'keywords' => $post->tags ? implode(', ', $post->tags) : null,
        'image' => $post->imagem ? url($post->imagem) : null,
        'mainEntityOfPage' => url()->current(),
    ]);
@endphp

@push(config('blog.pilha_cabecalho'))
    <link rel="stylesheet" href="{{ route(config('blog.nome_rota').'.estilo') }}">
    <script src="{{ route(config('blog.nome_rota').'.script') }}" defer></script>
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
        @if ($post->parceiro)
            <p class="blog-parceiro">
                @if ($post->parceiroLogo)
                    <img src="{{ $post->parceiroLogo }}" alt="" width="48" height="48">
                @endif
                <span>Em parceria com
                    @if ($post->parceiroUrl)
                        <a href="{{ $post->parceiroUrl }}" rel="noopener">{{ $post->parceiro }}</a>
                    @else
                        <strong>{{ $post->parceiro }}</strong>
                    @endif
                </span>
            </p>
        @endif
        @if ($post->imagem)
            <figure class="blog-figura">
                <img class="blog-imagem" src="{{ $post->imagem }}" alt="{{ $post->imagemAlt ?? '' }}">
                @if ($post->imagemCredito)
                    <figcaption>{{ $post->imagemCredito }}</figcaption>
                @endif
            </figure>
        @endif
        <div class="blog-texto">{!! $post->html() !!}</div>
        @if ($post->hashtags())
            <p class="blog-tags">{{ implode(' ', $post->hashtags()) }}</p>
        @endif
    </article>
    @if ($redes)
        <section class="blog-compartilhar" aria-labelledby="blog-compartilhar-titulo">
            <h2 id="blog-compartilhar-titulo">Compartilhar</h2>
            <ul>
                @foreach ($redes as $rede)
                    <li><a class="blog-rede blog-rede-{{ $rede['rede'] }}" href="{{ $rede['url'] }}" target="_blank" rel="noopener">{{ $rede['rotulo'] }}<span class="blog-sr"> (abre em nova aba)</span></a></li>
                @endforeach
                <li hidden data-blog-copiar="{{ url()->current() }}"><button type="button" class="blog-rede">Copiar link</button></li>
                <li hidden data-blog-copiar="{{ $textoParaRedes }}"><button type="button" class="blog-rede">Copiar para o LinkedIn</button></li>
            </ul>
            <p class="blog-copiado" role="status" aria-live="polite"></p>
        </section>
    @endif
    @if ($acao)
        <p><a class="blog-acao" href="{{ $acao['url'] }}">{{ $acao['texto'] }}</a></p>
    @endif
</div>
@endsection
