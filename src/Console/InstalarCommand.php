<?php

namespace UrbanoDev\Blog\Console;

use Illuminate\Console\Command;

class InstalarCommand extends Command
{
    protected $signature = 'blog:instalar';

    protected $description = 'Cria a pasta dos textos, com o passo a passo e o modelo';

    public function handle(): int
    {
        $pasta = (string) config('blog.pasta');
        is_dir($pasta) || mkdir($pasta, 0755, true);

        foreach (['_LEIA.md', '_MODELO.md'] as $arquivo) {
            if (! file_exists("{$pasta}/{$arquivo}")) {
                copy(__DIR__."/../../stubs/{$arquivo}", "{$pasta}/{$arquivo}");
            }
        }
        $this->info("Pronto. Comece por {$pasta}/_LEIA.md");

        return self::SUCCESS;
    }
}
