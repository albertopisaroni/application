<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InpsParameter;

class InpsParametersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parameters = [
            // 2020
            [
                'year' => 2020,
                'minimale_commercianti_artigiani' => 15953.00,
                // COMMERCIANTI
                'aliquota_commercianti' => 0.2409, // 24.09%
                'aliquota_commercianti_ridotta' => 0.1566, // 15.66% (24.09% × 65%)
                // ARTIGIANI  
                'aliquota_artigiani' => 0.24, // 24%
                'aliquota_artigiani_ridotta' => 0.156, // 15.6% (24% × 65%)
                // GESTIONE SEPARATA (stima, da verificare)
                'aliquota_gestione_separata' => 0.2607,
                'aliquota_gestione_separata_ridotta' => 0.1695,
                // CONTRIBUTI FISSI (stima proporzionale)
                'contributo_fisso_commercianti' => 3900.00, // stima
                'contributo_fisso_commercianti_ridotto' => 2535.00, // 65%
                'contributo_fisso_artigiani' => 3800.00, // stima
                'contributo_fisso_artigiani_ridotto' => 2470.00, // 65%
                'contributo_maternita_annuo' => 61.49,
                'massimale_commercianti_artigiani' => 85000.00, // stima
                'massimale_gestione_separata' => 110000.00, // stima
                'diritto_annuale_cciaa' => 50.00, // stima
            ],
            
            // 2021
            [
                'year' => 2021,
                'minimale_commercianti_artigiani' => 16243.00,
                // COMMERCIANTI
                'aliquota_commercianti' => 0.2409, // 24.09%
                'aliquota_commercianti_ridotta' => 0.1566, // 15.66%
                // ARTIGIANI
                'aliquota_artigiani' => 0.24, // 24%
                'aliquota_artigiani_ridotta' => 0.156, // 15.6%
                // GESTIONE SEPARATA
                'aliquota_gestione_separata' => 0.2607,
                'aliquota_gestione_separata_ridotta' => 0.1695,
                // CONTRIBUTI FISSI (stima)
                'contributo_fisso_commercianti' => 4000.00,
                'contributo_fisso_commercianti_ridotto' => 2600.00,
                'contributo_fisso_artigiani' => 3900.00,
                'contributo_fisso_artigiani_ridotto' => 2535.00,
                'contributo_maternita_annuo' => 61.49,
                'massimale_commercianti_artigiani' => 86000.00,
                'massimale_gestione_separata' => 112000.00,
                'diritto_annuale_cciaa' => 50.50,
            ],
            
            // 2022
            [
                'year' => 2022,
                'minimale_commercianti_artigiani' => 16243.00,
                // COMMERCIANTI
                'aliquota_commercianti' => 0.2448, // 24.48%
                'aliquota_commercianti_ridotta' => 0.1591, // 15.91%
                // ARTIGIANI
                'aliquota_artigiani' => 0.24, // 24%
                'aliquota_artigiani_ridotta' => 0.156, // 15.6%
                // GESTIONE SEPARATA
                'aliquota_gestione_separata' => 0.2607,
                'aliquota_gestione_separata_ridotta' => 0.1695,
                // CONTRIBUTI FISSI (stima)
                'contributo_fisso_commercianti' => 4200.00,
                'contributo_fisso_commercianti_ridotto' => 2730.00,
                'contributo_fisso_artigiani' => 4100.00,
                'contributo_fisso_artigiani_ridotto' => 2665.00,
                'contributo_maternita_annuo' => 61.49,
                'massimale_commercianti_artigiani' => 87500.00,
                'massimale_gestione_separata' => 115000.00,
                'diritto_annuale_cciaa' => 51.00,
            ],
            
            // 2023
            [
                'year' => 2023,
                'minimale_commercianti_artigiani' => 17504.00,
                // COMMERCIANTI
                'aliquota_commercianti' => 0.2448, // 24.48%
                'aliquota_commercianti_ridotta' => 0.1591, // 15.91%
                // ARTIGIANI
                'aliquota_artigiani' => 0.24, // 24%
                'aliquota_artigiani_ridotta' => 0.156, // 15.6%
                // GESTIONE SEPARATA
                'aliquota_gestione_separata' => 0.2607,
                'aliquota_gestione_separata_ridotta' => 0.1695,
                // CONTRIBUTI FISSI (stima)
                'contributo_fisso_commercianti' => 4350.00,
                'contributo_fisso_commercianti_ridotto' => 2827.50,
                'contributo_fisso_artigiani' => 4250.00,
                'contributo_fisso_artigiani_ridotto' => 2762.50,
                'contributo_maternita_annuo' => 61.49,
                'massimale_commercianti_artigiani' => 89000.00,
                'massimale_gestione_separata' => 118000.00,
                'diritto_annuale_cciaa' => 52.00,
            ],
            
            // 2024
            [
                'year' => 2024,
                'minimale_commercianti_artigiani' => 18415.00,
                // COMMERCIANTI
                'aliquota_commercianti' => 0.2448, // 24.48%
                'aliquota_commercianti_ridotta' => 0.1591, // 15.91%
                // ARTIGIANI
                'aliquota_artigiani' => 0.24, // 24%
                'aliquota_artigiani_ridotta' => 0.156, // 15.6%
                // GESTIONE SEPARATA
                'aliquota_gestione_separata' => 0.2607,
                'aliquota_gestione_separata_ridotta' => 0.1695,
                // CONTRIBUTI FISSI (corretti per 2024)
                'contributo_fisso_commercianti' => 4507.39, // 18415 × 24.48%
                'contributo_fisso_commercianti_ridotto' => 2929.80, // 65%
                'contributo_fisso_artigiani' => 4419.60, // 18415 × 24%
                'contributo_fisso_artigiani_ridotto' => 2872.74, // 65%
                'contributo_maternita_annuo' => 61.49,
                'massimale_commercianti_artigiani' => 113520.00, // Ufficiale 2024
                'massimale_gestione_separata' => 118000.00,
                'diritto_annuale_cciaa' => 53.21,
                // NUOVI PARAMETRI DETTAGLIATI
                'addizionale_ivs_percentuale' => 0.0009, // 0.09%
                'massimale_reddituale' => 113520.00, // Stesso del massimale INPS
                'maggiorazione_oltre_massimale' => 0.01, // 1% extra
                'calcolo_trimestrale_attivo' => true,
            ],
            
            // 2025 (valori ufficiali)
            [
                'year' => 2025,
                'minimale_commercianti_artigiani' => 18555.00, // Corretto dal codice esistente
                // COMMERCIANTI
                'aliquota_commercianti' => 0.2448, // 24.48%
                'aliquota_commercianti_ridotta' => 0.1591, // 15.91%
                'aliquota_commercianti_maggiorata' => 0.2548, // 25.48%
                'aliquota_commercianti_maggiorata_ridotta' => 0.1656, // 16.56%
                // ARTIGIANI
                'aliquota_artigiani' => 0.24, // 24%
                'aliquota_artigiani_ridotta' => 0.156, // 15.6%
                'aliquota_artigiani_maggiorata' => 0.25, // 25%
                'aliquota_artigiani_maggiorata_ridotta' => 0.1625, // 16.25%
                // GESTIONE SEPARATA
                'aliquota_gestione_separata' => 0.2607, // 26.07%
                'aliquota_gestione_separata_ridotta' => 0.1695, // 16.95%
                // CONTRIBUTI FISSI (valori ufficiali dal codice)
                'contributo_fisso_commercianti' => 4549.70,
                'contributo_fisso_commercianti_ridotto' => 2957.31,
                'contributo_fisso_artigiani' => 4460.64,
                'contributo_fisso_artigiani_ridotto' => 2899.42,
                'contributo_maternita_annuo' => 89.28, // Aggiornato per il 2025
                'massimale_commercianti_artigiani' => 115000.00, // Aggiornato
                'massimale_gestione_separata' => 120000.00,
                'soglia_aliquota_maggiorata' => 55448.00,
                'diritto_annuale_cciaa' => 53.21,
                // NUOVI PARAMETRI DETTAGLIATI 2025
                'addizionale_ivs_percentuale' => 0.0009, // 0.09%
                'massimale_reddituale' => 115000.00, // Aggiornato 2025
                'maggiorazione_oltre_massimale' => 0.01, // 1% extra
                'calcolo_trimestrale_attivo' => true,
            ],
        ];

        foreach ($parameters as $param) {
            InpsParameter::updateOrCreate(
                ['year' => $param['year']],
                $param
            );
        }
    }
}