<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class MetaDomain extends Model
{
    protected $fillable = ['domain', 'logo_path', 'source_url', 'is_custom'];

    public function getLogoUrlAttribute(): string
    {
        // se non hai ancora scaricato niente, uso l’avatar su dominio
        if (! $this->logo_path) {
            return 'https://ui-avatars.com/api/?format=svg&name=' . urlencode($this->domain);
        }

        // ==== 1) definisci durata del signed URL ====
        $expiresAt = now()->addHours(12);

        // ==== 2) chiave di cache (unica per dominio) ====
        $cacheKey = "meta_domain_signed_url_{$this->domain}";

        // ==== 3) genera o recupera dalla cache ====
        return Cache::remember($cacheKey, $expiresAt, function () use ($expiresAt) {
            return Storage::disk('s3')->temporaryUrl(
                $this->logo_path,
                $expiresAt
            );
        });

    }

    public static function findOrCreateByDomain(string $domain): MetaDomain
    {
        $domain = strtolower($domain);

        return Cache::remember("meta_domain_{$domain}", now()->addDays(10), function () use ($domain) {
            $meta = self::firstOrNew(['domain' => $domain]);

            if ($meta->exists && $meta->logo_path) {
                return $meta;
            }

            $sources = [
                "https://img.logo.dev/{$domain}?token=pk_WUdqxR7FTYmBBy1zyCi1zA&size=128&retina=true&fallback=404",
                "https://img.logo.dev/{$domain}?token=pk_WUdqxR7FTYmBBy1zyCi1zA&size=128&format=png&retina=true&fallback=404",
                // eventualmente aggiungi Brandfetch qui
            ];

            foreach ($sources as $url) {
                try {
                    $response = Http::timeout(5)->get($url);
                    if ($response->successful()) {
                        $extension = Str::contains($url, 'format=png') ? 'png' : 'jpg';
                        $path = "domini/{$domain}/meta/logo.{$extension}";

                        Storage::disk('s3')->put($path, $response->body());

                        $meta->logo_path = $path;
                        $meta->source_url = $url;
                        $meta->save();
                        return $meta;
                    }
                } catch (\Exception $e) {
                    Log::warning("Logo fetch failed for {$domain}: " . $e->getMessage());
                }
            }

            $meta->save(); // fallback vuoto
            return $meta;
        });
    }

}