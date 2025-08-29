<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ChatGptF24ParserService;

class ShowF24Prompt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'f24:show-prompt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostra il prompt inviato a ChatGPT per il parsing F24';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $parser = new ChatGptF24ParserService();
        $prompt = $parser->getPromptForDebug();
        
        $this->info("🤖 Prompt inviato a ChatGPT per il parsing F24:");
        $this->info("=" . str_repeat("=", 78));
        $this->line($prompt);
        $this->info("=" . str_repeat("=", 78));
        
        return 0;
    }
}
