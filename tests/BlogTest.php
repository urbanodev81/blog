<?php

namespace UrbanoDev\Blog\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\BlogServiceProvider;

class BlogTest extends TestCase
{
    private string $pasta;

    protected function getPackageProviders($app): array
    {
        return [BlogServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->pasta = sys_get_temp_dir().'/blog-'.uniqid();
        mkdir($this->pasta);
        $app['config']->set('blog.pasta', $this->pasta);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function tearDown(): void
    {
        FonteDeTeste::$posts = [];
        FonteDeTeste::$quebrar = false;
        File::deleteDirectory($this->pasta);
        parent::tearDown();
    }

    private function texto(string $slug, string $cabecalho = '', string $corpo = 'Corpo do texto.', string $data = '2026-01-10'): void
    {
        file_put_contents("{$this->pasta}/{$slug}.md", "---\ntitulo: Título {$slug}\ndescricao: Resumo {$slug}\ndata: {$data}\n{$cabecalho}---\n\n{$corpo}\n");
    }

    public function test_lista_e_abre_do_mais_novo_para_o_mais_velho(): void
    {
        $this->texto('velho', data: '2026-01-01');
        $this->texto('novo', data: '2026-02-01');

        $this->get('/blog')->assertOk()->assertSeeInOrder(['Título novo', 'Título velho']);
        $this->get('/blog/novo')->assertOk()->assertSee('Corpo do texto.')->assertSee('application/ld+json', false);
    }

    public function test_encaixes_do_sistema_entram_nos_pontos_fixos(): void
    {
        $views = sys_get_temp_dir().'/blog-views-'.uniqid();
        mkdir($views);
        file_put_contents("{$views}/fim-texto.blade.php", 'ENCAIXE-TEXTO {{ $post->slug }}');
        file_put_contents("{$views}/fim-indice.blade.php", 'ENCAIXE-INDICE {{ $inicio ? "inicio" : "filtrado" }}');
        file_put_contents("{$views}/pagina.blade.php", 'ENCAIXE-PAGINA {{ $post?->slug ?? "lista" }}');
        $this->app['view']->addNamespace('teste', $views);
        $this->texto('um');

        // Sem config, nada aparece: os outros sites não mudam.
        $this->get('/blog/um')->assertDontSee('ENCAIXE');

        config(['blog.encaixes' => ['fim_do_texto' => ['teste::fim-texto'], 'fim_do_indice' => ['teste::fim-indice'], 'pagina' => ['teste::pagina']]]);

        $this->get('/blog/um')->assertSee('ENCAIXE-TEXTO um')->assertSee('ENCAIXE-PAGINA um')->assertDontSee('ENCAIXE-INDICE');
        $this->get('/blog')->assertSee('ENCAIXE-INDICE inicio')->assertSee('ENCAIXE-PAGINA lista');
        $this->get('/blog?q=um')->assertSee('ENCAIXE-INDICE filtrado');
        File::deleteDirectory($views);
    }

    public function test_rascunho_e_data_futura_nao_tem_endereco(): void
    {
        $this->texto('oculto', "rascunho: sim\n");
        $this->texto('futuro', data: now()->addDay()->toDateString());

        $this->get('/blog/oculto')->assertNotFound();
        $this->get('/blog/futuro')->assertNotFound();
        $this->get('/blog')->assertDontSee('Título oculto')->assertDontSee('Título futuro');
    }

    public function test_arquivo_com_erro_sai_sozinho_e_o_conferir_acusa(): void
    {
        $this->texto('bom');
        file_put_contents("{$this->pasta}/sem-cabecalho.md", 'só texto');
        file_put_contents("{$this->pasta}/sem-titulo.md", "---\ndescricao: x\ndata: 2026-01-01\n---\ntexto");
        file_put_contents("{$this->pasta}/data-ruim.md", "---\ntitulo: x\ndescricao: x\ndata: 30/10/2026\n---\ntexto");
        file_put_contents("{$this->pasta}/Com Espaco.md", "---\ntitulo: x\ndescricao: x\ndata: 2026-01-01\n---\ntexto");

        $this->get('/blog')->assertOk()->assertSee('Título bom');
        $this->assertCount(4, app(Blog::class)->erros());
        $this->artisan('blog:conferir')->assertFailed();
    }

    public function test_html_e_link_perigoso_nao_passam(): void
    {
        $this->texto('xss', corpo: "<script>alert(1)</script>\n\n[clique](javascript:alert(2))\n\n<img src=x onerror=alert(3)>");

        $this->get('/blog/xss')->assertOk()
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('onerror=', false);
    }

    public function test_titulo_e_escapado_e_acao_perigosa_derruba_so_o_arquivo(): void
    {
        file_put_contents("{$this->pasta}/titulo.md", "---\ntitulo: </script><b>x\ndescricao: y\ndata: 2026-01-01\n---\ntexto");
        $this->texto('acao-ruim', "acao_url: javascript:alert(1)\n");

        $this->get('/blog/titulo')->assertOk()->assertDontSee('</script><b>', false);
        $this->get('/blog/acao-ruim')->assertNotFound();
    }

    public function test_endereco_nao_alcanca_arquivo_fora_da_lista(): void
    {
        $this->texto('bom');
        file_put_contents("{$this->pasta}/_MODELO.md", "---\ntitulo: Modelo\ndescricao: x\ndata: 2026-01-01\n---\ntexto");

        $this->get('/blog/_MODELO')->assertNotFound();
        $this->get('/blog/..%2f..%2fcomposer')->assertNotFound();
    }

    public function test_acao_do_config_leva_o_titulo_do_texto(): void
    {
        config(['blog.acao_texto' => 'Fale', 'blog.acao_url' => '/contato?assunto={titulo}']);
        $this->texto('guia');

        $this->get('/blog/guia')->assertOk()->assertSee('/contato?assunto=T%C3%ADtulo%20guia', false);
    }

    public function test_paginas_alimenta_sitemap_e_some_sem_texto(): void
    {
        $this->assertSame([], app(Blog::class)->paginas());
        $this->texto('guia');
        $this->assertSame(['/blog' => 'Blog', '/blog/guia' => 'Título guia — Resumo guia', '/blog/2026/01' => 'Janeiro de 2026 — Blog'], app(Blog::class)->paginas());
    }

    public function test_pagina_alem_do_fim_da_404_e_primeira_vazia_abre(): void
    {
        $this->get('/blog')->assertOk()->assertSee('Nenhum texto');
        $this->get('/blog?pagina=9')->assertNotFound();
    }

    public function test_estilo_e_arquivo_externo_para_nao_brigar_com_csp(): void
    {
        $this->get('/blog/estilo.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_comandos_criam_pasta_modelo_e_rascunho(): void
    {
        $this->artisan('blog:instalar')->assertSuccessful();
        $this->assertFileExists("{$this->pasta}/_LEIA.md");

        $this->artisan('blog:novo', ['slug' => 'meu-texto'])->assertSuccessful();
        $this->artisan('blog:novo', ['slug' => 'meu-texto'])->assertFailed();
        $this->artisan('blog:novo', ['slug' => 'Com Espaco'])->assertFailed();
        $this->assertSame([], app(Blog::class)->erros());
        $this->get('/blog/meu-texto')->assertNotFound();
    }

    public function test_rotas_respeitam_o_cache_de_rotas(): void
    {
        $this->assertTrue(Route::has('blog.index'));
        $this->assertStringContainsString('BlogController', Route::getRoutes()->getByName('blog.show')->getActionName());
    }

    public function test_agendado_aparece_no_conferir_com_a_data(): void
    {
        $dia = now()->addDays(3);
        $this->texto('amanha', data: $dia->toDateString());

        $this->artisan('blog:conferir')
            ->expectsOutput('agendado: amanha entra no ar em '.$dia->format('d/m/Y'))
            ->assertSuccessful();
    }

    public function test_tags_viram_hashtags_e_texto_para_o_linkedin(): void
    {
        $this->texto('com-tags', "tags: deploy automático, segurança, DevOps\n");

        $this->get('/blog/com-tags')->assertOk()
            ->assertSee('#DeployAutomatico #Seguranca #DevOps')
            ->assertSee('"keywords":"deploy automático, segurança, DevOps"', false)
            ->assertSee('Copiar para o LinkedIn');
    }

    public function test_barra_de_compartilhar_segue_a_configuracao(): void
    {
        $this->texto('dividir');

        $this->get('/blog/dividir')->assertOk()
            ->assertSee('https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode('http://localhost/blog/dividir'), false)
            ->assertSee('https://wa.me/?text=', false);

        config(['blog.compartilhar' => ['whatsapp']]);
        $this->get('/blog/dividir')->assertDontSee('linkedin.com', false)->assertSee('wa.me', false);

        config(['blog.compartilhar' => []]);
        $this->get('/blog/dividir')->assertDontSee('blog-compartilhar-titulo', false);
        $this->get('/blog/compartilhar.js')->assertOk()->assertHeader('Content-Type', 'text/javascript; charset=UTF-8');
    }

    public function test_parceiro_aparece_com_logo_e_na_lista(): void
    {
        $this->texto('juntos', "parceiro: Oficina WJ\nparceiro_logo: /logos/wj.png\nparceiro_url: https://wj.example\n");

        $this->get('/blog/juntos')->assertOk()
            ->assertSee('Em parceria com')
            ->assertSee('<a href="https://wj.example" rel="noopener">Oficina WJ</a>', false)
            ->assertSee('src="/logos/wj.png"', false);
        $this->get('/blog')->assertSee('com Oficina WJ');
    }

    public function test_logo_de_parceiro_sem_nome_ou_endereco_invalido_e_erro(): void
    {
        $this->texto('sem-nome', "parceiro_logo: /logo.png\n");
        $this->texto('logo-ruim', "parceiro: X\nparceiro_logo: javascript:alert(1)\n");

        $erros = app(Blog::class)->erros();
        $this->assertArrayHasKey('sem-nome.md', $erros);
        $this->assertArrayHasKey('logo-ruim.md', $erros);
    }

    public function test_imagem_vai_para_a_secao_da_previa_quando_configurada(): void
    {
        $this->texto('capa', "imagem: /blog/capa.jpg\nimagem_credito: Foto: Wikimedia Commons, CC0\n");

        $this->get('/blog/capa')->assertOk()->assertSee('Foto: Wikimedia Commons, CC0');
    }

    public function test_secao_vira_chapeu_e_pagina_propria(): void
    {
        $this->texto('relato', "secao: Experiência da Equipe\n");
        $this->texto('guia');

        $this->get('/blog/relato')->assertOk()
            ->assertSee('Experiência da Equipe')
            ->assertSee('/blog/secao/experiencia-da-equipe', false)
            ->assertSee('"articleSection":"Experiência da Equipe"', false);

        $this->get('/blog')->assertOk()
            ->assertSee('aria-label="Seções"', false)
            ->assertSee('Título relato')->assertSee('Título guia');

        $this->get('/blog/secao/experiencia-da-equipe')->assertOk()
            ->assertSee('<h1>Experiência da Equipe</h1>', false)
            ->assertSee('Título relato')->assertDontSee('Título guia');
    }

    public function test_secao_sem_texto_publicado_nao_tem_endereco(): void
    {
        $this->texto('futuro', "secao: Diário de obra\n", data: now()->addDay()->toDateString());
        $this->texto('guia');

        $this->get('/blog/secao/diario-de-obra')->assertNotFound();
        $this->get('/blog/secao/nao-existe')->assertNotFound();
        $this->get('/blog')->assertDontSee('aria-label="Seções"', false);
    }

    public function test_secao_entra_no_sitemap_e_nome_invalido_e_erro(): void
    {
        $this->texto('relato', "secao: Experiência da Equipe\n");
        $this->texto('ruim', "secao: ???\n");

        $this->assertArrayHasKey('/blog/secao/experiencia-da-equipe', app(Blog::class)->paginas());
        $this->assertSame(['secao: use um nome com letras ou números'], app(Blog::class)->erros()['ruim.md']);
    }

    public function test_tag_vira_link_e_pagina_propria(): void
    {
        $this->texto('deploy', "tags: Deploy automático, Docker\n");
        $this->texto('backup', "tags: deploy automatico\n", data: '2026-01-01');
        $this->texto('outro');

        $this->get('/blog/deploy')->assertOk()
            ->assertSee('href="http://localhost/blog/tag/deploy-automatico"', false)
            ->assertSee('#DeployAutomatico');
        $this->get('/blog/tag/deploy-automatico')->assertOk()
            ->assertSee('Título deploy')->assertSee('Título backup')->assertDontSee('Título outro');
        $this->get('/blog/tag/nao-existe')->assertNotFound();
        $this->assertSame(['deploy-automatico' => 'Deploy automático', 'docker' => 'Docker'], app(Blog::class)->tags());
    }

    public function test_arquivo_por_ano_e_mes(): void
    {
        $this->texto('nov', data: '2026-11-03');
        $this->texto('nov2', data: '2026-11-02');
        $this->texto('out', data: '2026-10-01');

        $this->get('/blog/2026/11')->assertOk()->assertSee('Novembro de 2026')
            ->assertSee('Título nov2')->assertDontSee('Título out');
        $this->get('/blog/2026')->assertOk()->assertSee('Título out')->assertSee('Título nov');
        $this->get('/blog/2026/09')->assertNotFound();
        $this->get('/blog/2025')->assertNotFound();
        $this->get('/blog/2026/13')->assertNotFound();
        $this->get('/blog')->assertSee('Novembro de 2026</a> (2)', false)->assertSee('3 de novembro de 2026');

        $paginas = app(Blog::class)->paginas();
        $this->assertArrayHasKey('/blog/2026/11', $paginas);
        $this->assertArrayHasKey('/blog/2026/10', $paginas);
    }

    public function test_arquivo_com_nome_de_ano_e_erro(): void
    {
        $this->texto('2026');

        $this->assertSame(['nome do arquivo: não pode ser só um ano, porque /2026 é o arquivo daquele ano'], app(Blog::class)->erros()['2026.md']);
    }

    public function test_busca_ignora_acento_e_maiuscula_e_exige_todas_as_palavras(): void
    {
        $this->texto('implantacao', "tags: Docker\n");
        file_put_contents("{$this->pasta}/captcha.md", "---\ntitulo: Por que trocamos o captcha\ndescricao: Sem rastreio de terceiro\ndata: 2026-01-10\n---\n\nTexto.\n");

        $this->get('/blog?q=TROCAMOS')->assertOk()->assertSee('Por que trocamos o captcha')->assertDontSee('Título implantacao')
            ->assertSee('1 texto encontrado.')->assertSee('noindex, follow', false);
        $this->get('/blog?q=rastreio%20docker')->assertOk()->assertSee('Nenhum texto encontrado.');
        $this->get('/blog?q=docker')->assertOk()->assertSee('Título implantacao');
        $this->get('/blog?q=t%C3%ADtulo')->assertOk()->assertSee('Título implantacao');
    }

    public function test_filtro_combina_secao_tag_e_mes_e_ignora_lixo(): void
    {
        $this->texto('a', "secao: Diário\ntags: Docker\n", data: '2026-11-01');
        $this->texto('b', "secao: Diário\ntags: PHP\n", data: '2026-11-02');
        $this->texto('c', "secao: Outra\ntags: Docker\n", data: '2026-10-02');

        $this->get('/blog?secao=diario&tag=docker&mes=2026-11')->assertOk()
            ->assertSee('Título a')->assertDontSee('Título b')->assertDontSee('Título c')
            ->assertSee('<option value="docker" selected>', false);
        // Valor fora do formato é ignorado, não vira texto nem erro.
        $this->get('/blog?mes[]=1&q[]=2&secao[]=3')->assertOk()->assertSee('Título c');
        $this->get('/blog?secao=%3Cscript%3E&mes=2026-99&tag[]=x')->assertOk()
            ->assertSee('Título c')->assertDontSee('<script>', false)->assertDontSee('noindex', false);
    }

    public function test_leia_tambem_traz_o_mesmo_assunto_antes_dos_mais_novos(): void
    {
        $this->texto('atual', "secao: Diário\ntags: Docker, Deploy\n", data: '2026-01-05');
        $this->texto('duas-tags', "tags: docker, deploy\n", data: '2026-01-01');
        $this->texto('mesma-secao', "secao: Diário\n", data: '2026-01-02');
        $this->texto('uma-tag', "tags: Docker\n", data: '2026-01-03');
        $this->texto('novissimo', data: '2026-01-09');
        $this->texto('rascunho', "tags: Docker\nrascunho: sim\n");

        $blog = app(Blog::class);
        $slugs = fn (array $posts) => array_map(fn ($p) => $p->slug, $posts);
        $this->assertSame(['duas-tags', 'uma-tag', 'mesma-secao'], $slugs($blog->relacionados($blog->achar('atual'))));
        // Sem assunto em comum, as vagas vão para os mais novos.
        $this->assertSame(['atual', 'uma-tag'], $slugs($blog->relacionados($blog->achar('novissimo'), 2)));
        $this->assertSame([], $blog->relacionados($blog->achar('atual'), 0));

        $this->get('/blog/atual')->assertOk()->assertSee('Leia também')
            ->assertSeeInOrder(['Título duas-tags', 'Título uma-tag', 'Título mesma-secao'])
            ->assertDontSee('Título novissimo')->assertDontSee('Título rascunho');

        config(['blog.relacionados' => 0]);
        $this->get('/blog/atual')->assertOk()->assertDontSee('Leia também');
    }

    public function test_paginacao_do_filtro_leva_o_filtro_junto(): void
    {
        config(['blog.por_pagina' => 1]);
        $this->texto('a', "tags: Docker\n", data: '2026-01-01');
        $this->texto('b', "tags: Docker\n", data: '2026-01-02');

        $this->get('/blog?tag=docker')->assertOk()->assertSee('tag=docker&amp;pagina=2', false);
        $this->get('/blog/tag/docker')->assertOk()->assertSee('/blog/tag/docker?pagina=2', false);
    }

    public function test_fonte_extra_vem_antes_e_ganha_o_mesmo_slug(): void
    {
        $this->texto('repetido');
        $this->texto('so-arquivo');
        FonteDeTeste::$posts = [FonteDeTeste::post('repetido', '<p>Do <strong>banco</strong>.</p>'), FonteDeTeste::post('so-banco', '<p>Só no banco.</p>')];
        config()->set('blog.fontes', [FonteDeTeste::class, \UrbanoDev\Blog\FonteDeArquivos::class]);

        $this->get('/blog')->assertOk()->assertSee('Título so-arquivo')->assertSee('Banco so-banco');
        $this->get('/blog/repetido')->assertOk()->assertSee('<p>Do <strong>banco</strong>.</p>', false)->assertDontSee('Corpo do texto.');
        $this->assertSame('teste', app(Blog::class)->achar('repetido')->fonte);
        $this->assertSame('arquivo', app(Blog::class)->achar('so-arquivo')->fonte);
    }

    public function test_fonte_que_quebra_nao_derruba_o_blog(): void
    {
        $this->texto('sobrevive');
        FonteDeTeste::$quebrar = true;
        config()->set('blog.fontes', [FonteDeTeste::class, \UrbanoDev\Blog\FonteDeArquivos::class]);

        $this->get('/blog')->assertOk()->assertSee('Título sobrevive');
        $this->get('/blog/sobrevive')->assertOk();
    }

    public function test_minutos_de_leitura_contam_o_html_pronto(): void
    {
        $post = FonteDeTeste::post('longo', '<p>'.str_repeat('palavra ', 450).'</p><p>fim</p>');

        $this->assertSame(3, $post->minutosDeLeitura());
    }

    public function test_foto_ao_lado_do_texto_e_ampliar_vem_do_pacote(): void
    {
        FonteDeTeste::$posts = [
            FonteDeTeste::post('com-foto', '<p><img src="/a.webp" alt="Painel" data-tamanho="media" data-alinhar="esquerda" data-ampliar="sim">Texto ao lado.</p>'),
            FonteDeTeste::post('sem-ampliar', '<p><img src="/b.webp" alt="" data-alinhar="direita">Texto.</p>'),
        ];
        config()->set('blog.fontes', [FonteDeTeste::class]);

        $this->get('/blog/com-foto')->assertOk()->assertSee('id="blog-foto-ampliada"', false);
        $this->get('/blog/sem-ampliar')->assertOk()->assertDontSee('id="blog-foto-ampliada"', false);

        $css = $this->get('/blog/estilo.css')->getContent();
        $this->assertStringContainsString('img[data-alinhar="esquerda"] { float: left;', $css);
        $this->assertStringContainsString('img[data-tamanho="media"] { width: 50%; }', $css);
        $this->assertStringContainsString('blog-foto-ampliada', $this->get('/blog/compartilhar.js')->getContent());
    }
}

/** Fonte de mentira para os testes: devolve o que estiver em $posts. */
class FonteDeTeste implements \UrbanoDev\Blog\Fonte
{
    /** @var list<\UrbanoDev\Blog\Post> */
    public static array $posts = [];

    public static bool $quebrar = false;

    public function ler(): array
    {
        if (self::$quebrar) {
            throw new \RuntimeException('banco fora');
        }

        return ['posts' => self::$posts, 'erros' => []];
    }

    public static function post(string $slug, string $html): \UrbanoDev\Blog\Post
    {
        return new \UrbanoDev\Blog\Post(
            slug: $slug, titulo: 'Banco '.$slug, descricao: 'Resumo', data: \Carbon\CarbonImmutable::parse('2026-01-05'),
            atualizado: null, autor: null, imagem: null, imagemAlt: null, imagemCredito: null, rascunho: false,
            acaoTexto: null, acaoUrl: null, corpo: '', htmlPronto: $html, fonte: 'teste',
        );
    }
}
