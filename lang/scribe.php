<?php

return [
    "labels" => [
        "search" => "Cerca",
        "base_url" => "URL base",
    ],

    "auth" => [
        "none" => "Questa API non richiede autenticazione.",
        "instruction" => [
            "query" => <<<TEXT
                Per autenticare le richieste, includi un parametro di query **`:parameterName`** nella richiesta.
                TEXT,
            "body" => <<<TEXT
                Per autenticare le richieste, includi un parametro **`:parameterName`** nel corpo della richiesta.
                TEXT,
            "query_or_body" => <<<TEXT
                Per autenticare le richieste, includi un parametro **`:parameterName`** nella query string o nel corpo della richiesta.
                TEXT,
            "bearer" => <<<TEXT
                Per autenticare le richieste, includi un'intestazione **`Authorization`** con valore **`"Bearer :placeholder"`**.
                TEXT,
            "basic" => <<<TEXT
                Per autenticare le richieste, includi un'intestazione **`Authorization`** nel formato **`"Basic {credentials}"`**. 
                Il valore di `{credentials}` deve contenere il tuo username/id e password uniti da due punti (:) 
                e codificati in base64.
                TEXT,
            "header" => <<<TEXT
                Per autenticare le richieste, includi un'intestazione **`:parameterName`** con valore **`":placeholder"`**.
                TEXT,
        ],
        "details" => <<<TEXT
            Tutti gli endpoint che richiedono autenticazione sono contrassegnati con il badge `richiede autenticazione` nella documentazione sottostante.
            TEXT,
    ],

    "headings" => [
        "introduction" => "Introduzione",
        "auth" => "Autenticazione delle richieste",
    ],

    "endpoint" => [
        "request" => "Richiesta",
        "headers" => "Intestazioni",
        "url_parameters" => "Parametri URL",
        "body_parameters" => "Parametri nel corpo",
        "query_parameters" => "Parametri nella query",
        "response" => "Risposta",
        "response_fields" => "Campi della risposta",
        "example_request" => "Richiesta di esempio",
        "example_response" => "Risposta di esempio",
        "responses" => [
            "binary" => "Dati binari",
            "empty" => "Risposta vuota",
        ],
    ],

    "try_it_out" => [
        "open" => "Prova subito ⚡",
        "cancel" => "Annulla 🛑",
        "send" => "Invia richiesta 💥",
        "loading" => "⏱ Invio in corso...",
        "received_response" => "Risposta ricevuta",
        "request_failed" => "La richiesta è fallita con errore",
        "error_help" => <<<TEXT
            Suggerimento: Verifica di essere connesso correttamente alla rete.
            Se sei il responsabile di questa API, assicurati che sia attiva e che CORS sia abilitato.
            Controlla la console degli strumenti di sviluppo per ulteriori dettagli.
            TEXT,
    ],

    "links" => [
        "postman" => "Visualizza la collection Postman",
        "openapi" => "Visualizza la specifica OpenAPI",
    ],
];