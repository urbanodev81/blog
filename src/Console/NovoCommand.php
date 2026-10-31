<?php

namespace UrbanoDev\Blog\Console;

use Illuminate\Console\Command;
use UrbanoDev\Blog\Blog;

class NovoCommand extends Command
{
    protected $signature = 'blog:novo {slug : Nome do arquivo e do endereço, como quanto-custa-um-sistema}';

    protected $description = 'Cria um texto novo, como rascunho';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $arquivo = rtrim((string) config('blog.pasta'), '/')."/{$slug}.md";

        if (preg_match('/^'.Blog::SLUG.'$/D', $slug) !== 1) {
            $this->error('Use só letras minúsculas sem acento, números e hífen.');

            return self::FAILURE;
        }
        if (file_exists($arquivo)) {
            $this->error("Já existe: {$arquivo}");

            return self::FAILURE;
        }

        is_dir(dirname($arquivo)) || mkdir(dirname($arquivo), 0755, true);
        file_put_contents($arquivo, str_replace('2026-01-01', date('Y-m-d'), (string) file_get_contents(__DIR__.'/../../stubs/_MODELO.md')));
        $this->info("Criado: {$arquivo}");

        return self::SUCCESS;
    }
}
