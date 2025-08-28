<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Models\Tax;

class TaxCalculationCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Company $company;
    public array $taxRecords;
    public array $summary;

    /**
     * Create a new message instance.
     */
    public function __construct(Company $company, array $taxRecords)
    {
        $this->company = $company;
        $this->taxRecords = $taxRecords;
        $this->summary = $this->calculateSummary($taxRecords);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Calcolo Tasse Completato - ' . $this->company->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tax-calculation-completed',
            with: [
                'company' => $this->company,
                'taxRecords' => $this->taxRecords,
                'summary' => $this->summary,
                'year' => $this->taxRecords[0]['payment_year'] ?? date('Y'),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Calcola il riepilogo dei totali
     */
    protected function calculateSummary(array $taxRecords): array
    {
        $totaleImposta = collect($taxRecords)
            ->filter(fn($r) => str_contains($r['tax_type'], 'IMPOSTA_SOSTITUTIVA'))
            ->where('payment_status', '!=', Tax::STATUS_CREDIT)
            ->sum('amount');

        $totaleInps = collect($taxRecords)
            ->filter(fn($r) => str_contains($r['tax_type'], 'INPS'))
            ->where('payment_status', '!=', Tax::STATUS_CREDIT)
            ->sum('amount');

        $crediti = collect($taxRecords)
            ->where('payment_status', Tax::STATUS_CREDIT)
            ->sum('amount');

        $scadenze = collect($taxRecords)
            ->where('payment_status', Tax::STATUS_PENDING)
            ->map(fn($r) => [
                'descrizione' => $r['description'],
                'importo' => $r['amount'],
                'scadenza' => $r['due_date']->format('d/m/Y')
            ])
            ->groupBy('scadenza');

        return [
            'totale_imposta' => $totaleImposta,
            'totale_inps' => $totaleInps,
            'totale_da_versare' => $totaleImposta + $totaleInps,
            'crediti' => $crediti,
            'scadenze' => $scadenze
        ];
    }
}