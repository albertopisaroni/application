<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Webklex\IMAP\Facades\Client;
use Purifier;

class EmailList extends Component
{
    use WithPagination;

    public $emailAccountId;
    public $perPage = 30;
    public $currentFolder = 'INBOX';
    public $folders = [];
    public $search = '';
    public $openedEmail = null;
    public $page = 1;

    public function openEmail($uid)
    {
        $account = currentCompany()->emailAccounts()->findOrFail($this->emailAccountId);
        $emailCacheKey = "emails:{$account->id}:{$this->currentFolder}:uid:{$uid}";
        $page = $this->page ?: 1;
        $pageCacheKey = "emails:{$account->id}:{$this->currentFolder}:page:{$page}:search:" . md5($this->search);

        $client = Client::make([
            'host' => $account->imap_host,
            'port' => $account->imap_port,
            'encryption' => $account->imap_encryption,
            'validate_cert' => true,
            'username' => $account->imap_username,
            'password' => $account->imap_password,
            'protocol' => 'imap',
        ]);

        $client->connect();
        $folder = $client->getFolder($this->currentFolder);
        $message = $folder->query()->uid($uid)->get()->first();

        $wasSeen = $message->hasFlag('Seen');

        if (!$wasSeen) {
            $message->setFlag('Seen');

            // 🔥 Invalida cache della lista (così si aggiorna il grassetto)
            Cache::forget($pageCacheKey);
        }

        // Carica o crea la cache dell’email aperta
        $cached = Cache::get($emailCacheKey);

        if ($cached) {
            $cached['seen'] = true;
            $this->openedEmail = $cached;
            return;
        }

        $parsed = [
            'subject' => $message->getSubject()?->first() ?? '(Nessun oggetto)',
            'from' => $message->getFrom()[0]->mail ?? '',
            'date' => optional($message->getDate()?->get())->format('d/m/Y H:i'),
            'body' => Purifier::clean(
                $message->getHTMLBody() ?? nl2br(e($message->getTextBody())),
                [
                    'HTML.SafeIframe' => true,
                    'URI.SafeIframeRegexp' => '%^(https?:)?//(www.youtube.com/embed/|player.vimeo.com/video/)%',
                    'Attr.AllowedFrameTargets' => ['_blank'],
                ]
            ),
            'seen' => true,
        ];

        Cache::forever($emailCacheKey, $parsed);
        $this->openedEmail = $parsed;
    }

    
    protected $updatesQueryString = ['page', 'search', 'currentFolder'];

    public function mount($emailAccountId)
    {
        $this->emailAccountId = $emailAccountId;
    }

    public function render()
    {
        $account = currentCompany()->emailAccounts()->findOrFail($this->emailAccountId);
        $page = $this->page ?? 1;
        $uidCacheKey = "emails:{$account->id}:uidnext:{$this->currentFolder}";
        $messagesCacheKey = "emails:{$account->id}:{$this->currentFolder}:page:{$page}";

        // Connessione IMAP
        $client = Client::make([
            'host'          => $account->imap_host,
            'port'          => $account->imap_port,
            'encryption'    => $account->imap_encryption,
            'validate_cert' => true,
            'username'      => $account->imap_username,
            'password'      => $account->imap_password,
            'protocol'      => 'imap',
        ]);

        $client->connect();

        // 📂 Carica tutte le cartelle disponibili
        $this->folders = $client->getFolders()->map(fn($f) => (object)['name' => $f->full_name])->toArray();

        // 📁 Cartella attuale
        $folder = $client->getFolder($this->currentFolder);
        $status = (object) $folder->examine();
        $uidNext = $status->uidnext ?? null;

        // Invalida cache se necessario
        if (Cache::get($uidCacheKey) !== $uidNext) {
            $cachedKeys = Cache::get("emails:{$account->id}:{$this->currentFolder}:cached_pages", []);
            foreach ($cachedKeys as $key) {
                Cache::forget($key);
            }
            Cache::forget("emails:{$account->id}:{$this->currentFolder}:cached_pages");
            Cache::put($uidCacheKey, $uidNext, now()->addMinutes(5));
        }

        // Scarica o recupera da cache
        $rawMessages = Cache::remember($messagesCacheKey . ':search:' . md5($this->search), now()->addSeconds(30), function () use ($folder, $page) {
            if (!empty($this->search)) {
                // Cerco per subject
                $subjectResults = $folder->messages()
                    ->subject($this->search)
                    ->setFetchBody(false)
                    ->limit(100) // Filtro un po’ di risultati per evitare troppi UID
                    ->get();
        
                // Cerco per mittente
                $fromResults = $folder->messages()
                    ->from($this->search)
                    ->setFetchBody(false)
                    ->limit(100)
                    ->get();
        
                // Unisco i risultati e rimuovo duplicati per UID
                $merged = $subjectResults
                    ->merge($fromResults)
                    ->unique(fn($msg) => $msg->getUid())
                    ->values();
        
                // Simula la paginazione
                return $merged->slice(($page - 1) * 30, 30)
                    ->map(function ($msg) {
                        return [
                            'subject' => $msg->getSubject()?->first() ?? '(Nessun oggetto)',
                            'from' => $msg->getFrom()[0]->mail ?? '',
                            'date' => optional($msg->getDate()?->get())->format('d/m/Y H:i'),
                            'uid' => $msg->getUid(),
                            'seen' => $msg->hasFlag('Seen'),
                        ];
                    })->toArray();
            }
        
            // Nessuna ricerca: mostra messaggi normali
            return $folder->messages()
                ->all()
                ->setFetchBody(false)
                ->limit(30, ($page - 1) * 30)
                ->get()
                ->map(function ($msg) {
                    return [
                        'subject' => $msg->getSubject()?->first() ?? '(Nessun oggetto)',
                        'from' => $msg->getFrom()[0]->mail ?? '',
                        'date' => optional($msg->getDate()?->get())->format('d/m/Y H:i'),
                        'uid' => $msg->getUid(),
                        'seen' => $msg->hasFlag('Seen'),
                    ];
                })->toArray();
        });

        // Tracciamento chiavi cache
        Cache::put("emails:{$account->id}:{$this->currentFolder}:cached_pages", array_unique([
            ...Cache::get("emails:{$account->id}:{$this->currentFolder}:cached_pages", []),
            $messagesCacheKey
        ]), now()->addMinutes(5));

        // Fake paginator
        $total = $folder->examine()['exists'] ?? count($rawMessages);
        $paginator = new LengthAwarePaginator(
            $rawMessages,
            $total,
            $this->perPage,
            $page,
            ['path' => \Request::url(), 'query' => ['page' => $page]]
        );

        return view('livewire.email-list', [
            'messages' => $paginator,
            'folders' => $this->folders,
            'currentFolder' => $this->currentFolder,
        ]);
    }
}