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
        $this->assertSame(['/blog' => 'Blog', '/blog/guia' => 'Título guia — Resumo guia'], app(Blog::class)->paginas());
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
}
