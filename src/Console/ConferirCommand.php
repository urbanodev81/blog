<?php

namespace UrbanoDev\Blog\Console;

use Illuminate\Console\Command;
use UrbanoDev\Blog\Blog;

class ConferirCommand extends Command
{
    protected $signature = 'blog:conferir';

    protected $description = 'Lista os textos e diz o que há de errado em cada arquivo';

    public function handle(Blog $blog): int
    {
        $erros = $blog->erros();
        foreach ($erros as $arquivo => $problemas) {
            $this->error($arquivo);
            foreach ($problemas as $problema) {
                $this->line("  - {$problema}");
            }
        }
        $this->info(count($blog->publicados()).' publicado(s), '.count($erros).' com erro.');

        return $erros === [] ? self::SUCCESS : self::FAILURE;
    }
}
