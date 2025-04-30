<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('wizard-step.{uuid}', function ($user, $uuid) {
    return true; // oppure verifica se l'utente ha accesso
});