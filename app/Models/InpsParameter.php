<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InpsParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'minimale_commercianti_artigiani',
        'aliquota_commercianti',
        'aliquota_commercianti_ridotta',
        'aliquota_commercianti_maggiorata',
        'aliquota_commercianti_maggiorata_ridotta',
        'aliquota_artigiani',
        'aliquota_artigiani_ridotta',
        'aliquota_artigiani_maggiorata',
        'aliquota_artigiani_maggiorata_ridotta',
        'aliquota_gestione_separata',
        'aliquota_gestione_separata_ridotta',
        'contributo_fisso_commercianti',
        'contributo_fisso_commercianti_ridotto',
        'contributo_fisso_artigiani',
        'contributo_fisso_artigiani_ridotto',
        'contributo_maternita_annuo',
        'massimale_commercianti_artigiani',
        'massimale_gestione_separata',
        'soglia_aliquota_maggiorata',
        'diritto_annuale_cciaa',
    ];

    protected $casts = [
        'year' => 'integer',
        'minimale_commercianti_artigiani' => 'decimal:2',
        'aliquota_commercianti' => 'decimal:4',
        'aliquota_commercianti_ridotta' => 'decimal:4',
        'aliquota_commercianti_maggiorata' => 'decimal:4',
        'aliquota_commercianti_maggiorata_ridotta' => 'decimal:4',
        'aliquota_artigiani' => 'decimal:4',
        'aliquota_artigiani_ridotta' => 'decimal:4',
        'aliquota_artigiani_maggiorata' => 'decimal:4',
        'aliquota_artigiani_maggiorata_ridotta' => 'decimal:4',
        'aliquota_gestione_separata' => 'decimal:4',
        'aliquota_gestione_separata_ridotta' => 'decimal:4',
        'contributo_fisso_commercianti' => 'decimal:2',
        'contributo_fisso_commercianti_ridotto' => 'decimal:2',
        'contributo_fisso_artigiani' => 'decimal:2',
        'contributo_fisso_artigiani_ridotto' => 'decimal:2',
        'contributo_maternita_annuo' => 'decimal:2',
        'massimale_commercianti_artigiani' => 'decimal:2',
        'massimale_gestione_separata' => 'decimal:2',
        'soglia_aliquota_maggiorata' => 'decimal:2',
        'diritto_annuale_cciaa' => 'decimal:2',
    ];

    /**
     * Recupera i parametri per un anno specifico
     */
    public static function getForYear(int $year): ?self
    {
        return static::where('year', $year)->first();
    }

    /**
     * Recupera i parametri per l'anno o usa quelli più recenti disponibili
     */
    public static function getForYearOrLatest(int $year): ?self
    {
        // Prova prima l'anno specifico
        $params = static::where('year', $year)->first();
        
        if (!$params) {
            // Se non trova l'anno, usa i parametri più recenti disponibili
            $params = static::where('year', '<=', $year)
                ->orderBy('year', 'desc')
                ->first();
        }
        
        return $params;
    }
}