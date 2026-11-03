<?php

namespace UrbanoDev\Blog;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use UrbanoDev\Blog\Console\ConferirCommand;
use UrbanoDev\Blog\Console\InstalarCommand;
use UrbanoDev\Blog\Console\NovoCommand;
use UrbanoDev\Blog\Http\BlogController;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blog.php', 'blog');
        $this->app->singleton(Blog::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/blog.php' => config_path('blog.php')], 'blog-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/blog')], 'blog-views');
            $this->commands([InstalarCommand::class, NovoCommand::class, ConferirCommand::class]);
        }

        $nome = trim((string) config('blog.nome_rota'), '.').'.';

        Route::middleware(config('blog.middleware'))
            ->prefix(trim((string) config('blog.prefixo'), '/'))
            ->name($nome)
            ->group(function () {
                Route::get('/', [BlogController::class, 'index'])->name('index');
                Route::get('/estilo.css', [BlogController::class, 'estilo'])->name('estilo');
                Route::get('/compartilhar.js', [BlogController::class, 'script'])->name('script');
                Route::get('/secao/{secao}', [BlogController::class, 'secao'])->where('secao', Blog::SLUG)->name('secao');
                Route::get('/tag/{tag}', [BlogController::class, 'tag'])->where('tag', Blog::SLUG)->name('tag');
                Route::get('/{ano}/{mes}', [BlogController::class, 'arquivo'])->where(['ano' => '\d{4}', 'mes' => '0[1-9]|1[0-2]'])->name('mes');
                Route::get('/{ano}', [BlogController::class, 'arquivo'])->where('ano', '\d{4}')->name('ano');
                Route::get('/{slug}', [BlogController::class, 'show'])->where('slug', Blog::SLUG)->name('show');
            });
    }
}
