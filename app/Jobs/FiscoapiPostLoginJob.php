<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use App\Models\FiscoapiSession;

class FiscoapiPostLoginJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $id_sessione;

    /**
     * Create a new job instance.
     */
    public function __construct($id_sessione)
    {
        $this->id_sessione = $id_sessione;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Check if post-login has already been executed for this session
        $session = FiscoapiSession::where('id_sessione', $this->id_sessione)->first();
        
        if ($session && !$session->post_login_executed) {
            // Mark as executed first to prevent duplicate jobs
            $session->update(['post_login_executed' => true]);
            
            // Execute the artisan command
            Artisan::call('fiscoapi:post-login', ['id_sessione' => $this->id_sessione]);
        }
    }
} 