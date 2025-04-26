<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'registered',
        'contacted',
        'email', 
        'phone', 
        'fullname', 
        'name', 
        'surname', 
        'user_id', 
        'piva', 
        'cf', 
        'residenza', 
        'indirizzo', 
        'provincia', 
        'cap', 
        'birth_date', 
        'birth_place_code', 
        'gender', 
        'document_front', 
        'document_back', 
        'signed_at', 
        'step', 
        'step_history', 
        'company_name', 
        'company_address', 
        'company_cf',
        'project_type',
        'location',
        'label',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'ab_variant',
        'page_time',
        'scroll_time',
        'scroll_bounce',
        'mouse_movement',
        'form_time_fullname',
        'form_time_email',
        'form_time_phone',
        'form_autofill_fullname',
        'form_autofill_email',
        'form_autofill_phone',
        'section_time_fatture_e_pagamenti',
        'section_time_flussi_di_lavoro',
        'section_time_tasse_e_scadenze',
        'section_time_il_ai_automazioni_intelligenti',
        'section_time_il_nostro_team_e_qui_per_te',
        'section_time_con_noi_essere_freelance',
        'section_time_newo_e_pensato_per_farti_crescere',
        'section_time_newo_e_gia_la_scelta',
        'behavior_profile',
        'behavior_score',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    protected static function booted()
    {
        static::creating(function ($registration) {
            $registration->uuid = (string) Str::uuid();
        });
    }
}