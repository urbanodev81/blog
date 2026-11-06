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
        'articleSection' => $post->secao,
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
        @if ($post->secao)
            <p class="blog-chapeu"><a href="{{ route(config('blog.nome_rota').'.secao', $post->secaoSlug()) }}">{{ $post->secao }}</a></p>
        @endif
        <h1>{{ $post->titulo }}</h1>
        <p class="blog-meta">
            @if ($post->autor) {{ $post->autor }} · @endif
            <time datetime="{{ $post->data->toDateString() }}">{{ $post->dataPorExtenso() }}</time>
            · {{ $post->minutosDeLeitura() }} min de leitura
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
        @php($html = $post->html())
        <div class="blog-texto">{!! $html !!}</div>
        @if ($post->tagsComSlug())
            <ul class="blog-tags" aria-label="Tags">
                @foreach ($post->tagsComSlug() as $slug => $nome)
                    <li><a href="{{ route(config('blog.nome_rota').'.tag', $slug) }}">{{ \UrbanoDev\Blog\Post::hashtag($nome) ?? $nome }}</a></li>
                @endforeach
            </ul>
        @endif
    </article>
    @if ($acao)
        <p><a class="blog-acao" href="{{ $acao['url'] }}">{{ $acao['texto'] }}</a></p>
    @endif
    @if ($redes)
        {{-- div, não section: o layout do sistema costuma dar espaçamento grande a toda section. --}}
        <div class="blog-compartilhar" role="group" aria-labelledby="blog-compartilhar-titulo">
            <p class="blog-compartilhar-titulo" id="blog-compartilhar-titulo">Compartilhar</p>
            <ul>
                @foreach ($redes as $rede)
                    <li><a class="blog-rede blog-rede-{{ $rede['rede'] }}" href="{{ $rede['url'] }}" target="_blank" rel="noopener">{{ $rede['rotulo'] }}<span class="blog-sr"> (abre em nova aba)</span></a></li>
                @endforeach
                <li hidden data-blog-copiar="{{ url()->current() }}"><button type="button" class="blog-rede">Copiar link</button></li>
                <li hidden data-blog-copiar="{{ $textoParaRedes }}"><button type="button" class="blog-rede">Copiar para o LinkedIn</button></li>
            </ul>
            <p class="blog-copiado" role="status" aria-live="polite"></p>
        </div>
    @endif
    @foreach ((array) config('blog.encaixes.fim_do_texto', []) as $encaixe)
        @include($encaixe, ['post' => $post])
    @endforeach
    @if ($relacionados)
        <div class="blog-relacionados" role="group" aria-labelledby="blog-relacionados-titulo">
            <h2 id="blog-relacionados-titulo">Leia também</h2>
            @foreach ($relacionados as $outro)
                <article class="blog-cartao">
                    @if ($outro->secao)
                        <p class="blog-chapeu">{{ $outro->secao }}</p>
                    @endif
                    <h3><a href="{{ route(config('blog.nome_rota').'.show', $outro->slug) }}">{{ $outro->titulo }}</a></h3>
                    <p>{{ $outro->descricao }}</p>
                    <p class="blog-meta"><time datetime="{{ $outro->data->toDateString() }}">{{ $outro->dataPorExtenso() }}</time> · {{ $outro->minutosDeLeitura() }} min de leitura</p>
                </article>
            @endforeach
        </div>
    @endif
    @if (str_contains($html, 'data-ampliar="sim"'))
        <dialog class="blog-foto-ampliada" id="blog-foto-ampliada" aria-label="Foto ampliada">
            <button type="button" aria-label="Fechar a foto">×</button>
            <img alt="">
            <p hidden></p>
        </dialog>
    @endif
    @foreach ((array) config('blog.encaixes.pagina', []) as $encaixe)
        @include($encaixe, ['post' => $post])
    @endforeach
</div>
@endsection
