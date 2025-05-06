<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light only">
    <title>Hai una nuova fattura da visualizzare: {{ $invoice->invoice_number }}</title>
    <style>

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #fbfbfb !important;
                color: #050505 !important;
            }
        }
        @media only screen and (max-width: 480px) {
            .stack-column {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            }
            .stack-column td {
            display: block;
            width: 100% !important;
            }
        }

        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-NeutralItalic.otf") format("opentype");font-weight:400;font-style:italic}
        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-Median.otf") format("opentype");font-weight:500;font-style:normal}
        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-MedianItalic.otf") format("opentype");font-weight:500;font-style:italic}
        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-Bulky.otf") format("opentype");font-weight:700;font-style:normal}
        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-BulkyItalic.otf") format("opentype");font-weight:700;font-style:italic}
        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-Slim.otf") format("opentype");font-weight:300;font-style:normal}
        @font-face{font-family:PolySansTrial;src:url("https://newo.io/fonts/PolySansTrial-SlimItalic.otf") format("opentype");font-weight:300;font-style:italic}
        
        body, *{font-family:PolySansTrial,'Helvetica Neue',Helvetica,Arial,sans-serif}
      
    </style>
</head>
<body style="margin:0; padding:40px 0; background-color:#fbfbfb; color:#050505; font-family:PolySansTrial,'Helvetica Neue',Helvetica,Arial,sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #fbfbfb;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width: 621px; width:100%; background-color: #ffffff; border-radius: 16px; box-shadow: 0 0 50px rgba(0,0,0,0.1);">
          <tr>
            <td style="padding: 80px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="padding-bottom: 40px;">
                    <img src="{{ $invoice->numbering->logo_base64 }}" alt="Logo" style="max-width: 170px; max-height: 70px;" />
                  </td>
                </tr>
                <tr>
                  <td align="left" style="font-size: 26px; font-weight: 500; color: #050505; padding-bottom: 40px;">
                    Hai una nuova fattura da visualizzare.
                  </td>
                </tr>
                <tr>
                  <td style="font-size: 16px; font-weight: 300; color: #050505; line-height: 1.5; padding-bottom: 40px;">
                    <p style="margin: 0 0 16px 0;">Ciao,</p>
                    <p style="margin: 0 0 16px 0;">
                      Ti giro la fattura relativa al lavoro svolto.<br>
                      Se hai domande o ti serve supporto, scrivimi pure.
                    </p>
                    <p style="margin: 0;">Grazie mille</p>
                  </td>
                </tr>

                <tr>
                  <td style="font-size: 14px; font-weight: 300; color: #050505; line-height: 1.5; padding-bottom: 40px;">
                    <p style="margin: 0 0 8px 0;">Fattura numero: <strong>{{ $invoice->invoice_number }}</strong></p>
                    <p style="margin: 0 0 8px 0;">Importo totale: <strong>{{ number_format($invoice->total, 2, ',', '.') }} EUR</strong></p>
                    <p style="margin: 0;">Da pagare entro: <strong>{{ $invoice->issue_date->format('d/m/Y') }}</strong></p>
                  </td>
                </tr>

                <!-- Pulsanti -->
                <tr>
                  <td align="center" style="padding-bottom: 40px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                      <tr class="stack-column">
                        <td align="center" style="padding-right: 8px;" width="50%">
                          <a href="{{ $url }}" style="
                            display: block;
                            font-size: 22px;
                            font-weight: 300;
                            color: #000000;
                            text-decoration: none;
                            border: 2px solid #000000;
                            border-radius: 7px;
                            padding: 12px 0;
                            width: 100%;
                            text-align: center;
                          ">
                            Visualizza la fattura
                          </a>
                        </td>
                        <td align="center" style="padding-left: 8px;" width="50%">
                          <a href="#" style="
                            display: block;
                            font-size: 22px;
                            font-weight: 300;
                            color: #ffffff;
                            background-color: #ad96ff;
                            text-decoration: none;
                            border-radius: 7px;
                            padding: 12px 0;
                            width: 100%;
                            text-align: center;
                          ">
                            Paga ora
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- Note finali -->
                <tr>
                  <td style="font-size: 12px; font-weight: 300; color: #050505; line-height: 1.5;">
                    <p style="margin: 0;">
                      Quando effettui il bonifico, inserisci il numero della fattura <strong>{{ $invoice->invoice_number }}</strong> o la causale, così possiamo identificarlo subito, senza intoppi.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <!-- Footer -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="max-width: 621px; width: 100%; margin-top: 20px;">
          <tr>
            <td style="text-align: center; font-size: 12px; color: #050505; line-height: 1.5; padding: 16px 24px;">
              Newo ti ha inviato questa email per conto di un cliente che ti ha spedito una fattura. Se vuoi sapere come gestiamo i dati o leggere la nostra privacy policy, <a href="#" style="color: #050505; text-decoration: underline;">clicca qui</a>.<br><br>
              {{ $invoice->company->name }} – P.IVA {{ $invoice->company->piva }}
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>
</html>