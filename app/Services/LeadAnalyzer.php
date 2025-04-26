<?php

namespace App\Services;

class LeadAnalyzer
{
    public static function analyze(array $data): array
    {
        $pageTime = $data['page_time'] ?? 0;
        $scrollTime = $data['scroll_time'] ?? 0;
        $scrollBounce = $data['scroll_bounce'] ?? 0;
        $mouseMovement = $data['mouse_movement'] ?? 0;

        $formTime = [
            'fullname' => $data['form_time_fullname'] ?? 0,
            'email' => $data['form_time_email'] ?? 0,
            'phone' => $data['form_time_phone'] ?? 0,
        ];

        $section = [
            'pagamenti' => $data['section_time_fatture_e_pagamenti'] ?? 0,
            'flussi' => $data['section_time_flussi_di_lavoro'] ?? 0,
            'tasse' => $data['section_time_tasse_e_scadenze'] ?? 0,
            'ai' => $data['section_time_il_ai_automazioni_intelligenti'] ?? 0,
            'team' => $data['section_time_il_nostro_team_e_qui_per_te'] ?? 0,
            'freelance' => $data['section_time_con_noi_essere_freelance'] ?? 0,
            'crescita' => $data['section_time_newo_e_pensato_per_farti_crescere'] ?? 0,
            'scelta' => $data['section_time_newo_e_gia_la_scelta'] ?? 0,
        ];

        $projectType = $data['project_type'] ?? null;

        $interesseFiscale = $section['pagamenti'] + $section['tasse'] + $section['flussi'];
        $interesseCrescita = $section['crescita'] + $section['scelta'] + $section['freelance'];
        $interesseTecnologia = $section['ai'];
        $interesseUmano = $section['team'];

        // 🎯 Profili specializzati
        if ($pageTime < 25 && array_sum($formTime) < 6 && $mouseMovement < 25 && $projectType !== 'Voglio solo delle informazioni') {
            return ['profile' => 'Decisore rapido', 'score' => 90];
        }

        if ($pageTime > 100 && $mouseMovement > 80 && $interesseFiscale > 10 && $interesseCrescita < 5) {
            return ['profile' => 'Fiscale orientato', 'score' => 85];
        }

        if ($pageTime > 100 && $mouseMovement > 80 && $interesseCrescita > 10 && $interesseFiscale < 5) {
            return ['profile' => 'Crescita orientato', 'score' => 85];
        }

        if ($pageTime > 150 && $scrollBounce < 5 && $interesseUmano > 5) {
            return ['profile' => 'Valutatore empatico', 'score' => 80];
        }

        if ($mouseMovement > 100 && array_sum($formTime) < 2) {
            return ['profile' => 'Esploratore superficiale', 'score' => 50];
        }

        if ($mouseMovement > 50 && $scrollBounce > 15 && array_sum($formTime) > 10) {
            return ['profile' => 'Interessato ma confuso', 'score' => 60];
        }

        if ($pageTime < 30 && array_sum($formTime) > 15) {
            return ['profile' => 'Compilatore frettoloso', 'score' => 40];
        }

        if ($interesseFiscale > 5 && $interesseCrescita > 5 && $interesseUmano > 3) {
            return ['profile' => 'Multi-interessato', 'score' => 75];
        }

        if ($interesseTecnologia > 5 && $interesseUmano > 5) {
            return ['profile' => 'Bilanciato tra AI e umano', 'score' => 70];
        }

        // 🔚 Fallback scoring
        $score = 0;
        $score += $pageTime > 100 ? 20 : 10;
        $score += $mouseMovement > 80 ? 15 : ($mouseMovement > 40 ? 10 : 5);
        $score += $scrollBounce > 10 ? 10 : 0;
        $score += array_sum($formTime) > 10 ? 15 : 5;
        $score += $interesseFiscale > 5 ? 10 : 0;
        $score += $interesseCrescita > 5 ? 10 : 0;

        $profile = match(true) {
            $score >= 75 => 'Coinvolto',
            $score >= 50 => 'Potenziale',
            default      => 'Basso interesse',
        };

        return ['profile' => $profile, 'score' => $score];
    }
}