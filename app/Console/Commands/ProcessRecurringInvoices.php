<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-invoices:process {--dry-run : Show what would be processed without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring invoices and generate new invoices when due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Processing recurring invoices...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
        }

        // Get all recurring invoices ready to generate
        $recurringInvoices = RecurringInvoice::readyToGenerate()->get();
        
        if ($recurringInvoices->isEmpty()) {
            $this->info('No recurring invoices ready to process.');
            return 0;
        }

        $this->info("Found {$recurringInvoices->count()} recurring invoices ready to process:");
        
        $processed = 0;
        $errors = 0;

        foreach ($recurringInvoices as $recurringInvoice) {
            try {
                $this->line("- Processing: {$recurringInvoice->template_name} (ID: {$recurringInvoice->id})");
                
                if (!$isDryRun) {
                    $this->generateInvoiceFromRecurring($recurringInvoice);
                }
                
                $processed++;
                $this->info("  ✓ Generated invoice for {$recurringInvoice->client->name}");
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error processing recurring invoice {$recurringInvoice->id}: {$e->getMessage()}");
            }
        }

        $this->info("\nProcessing complete:");
        $this->line("- Processed: {$processed}");
        if ($errors > 0) {
            $this->error("- Errors: {$errors}");
        }

        return 0;
    }

    /**
     * Generate a new invoice from a recurring invoice
     */
    private function generateInvoiceFromRecurring(RecurringInvoice $recurringInvoice): Invoice
    {
        return DB::transaction(function () use ($recurringInvoice) {
            // Get the next invoice number from the numbering
            $numbering = $recurringInvoice->numbering;
            $nextNumber = $numbering->nextNumber();

            // Create the new invoice
            $invoice = Invoice::create([
                'company_id' => $recurringInvoice->company_id,
                'client_id' => $recurringInvoice->client_id,
                'numbering_id' => $recurringInvoice->numbering_id,
                'payment_method_id' => $recurringInvoice->payment_method_id,
                'recurring_invoice_id' => $recurringInvoice->id,
                'invoice_number' => $nextNumber,
                'issue_date' => now()->toDateString(),
                'fiscal_year' => now()->year,
                'document_type' => 'TD01',
                'subtotal' => $recurringInvoice->subtotal,
                'vat' => $recurringInvoice->vat,
                'total' => $recurringInvoice->total,
                'global_discount' => $recurringInvoice->global_discount,
                'withholding_tax' => $recurringInvoice->withholding_tax,
                'inps_contribution' => $recurringInvoice->inps_contribution,
                'header_notes' => $recurringInvoice->header_notes,
                'footer_notes' => $recurringInvoice->footer_notes,
                'contact_info' => $recurringInvoice->contact_info,
            ]);

            // Copy items from recurring invoice
            foreach ($recurringInvoice->items as $recurringItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $recurringItem->description,
                    'quantity' => $recurringItem->quantity,
                    'unit_price' => $recurringItem->unit_price,
                    'vat_rate' => $recurringItem->vat_rate,
                    'total' => $recurringItem->total,
                ]);
            }

            // Update the recurring invoice
            $recurringInvoice->update([
                'invoices_generated' => $recurringInvoice->invoices_generated + 1,
                'last_generated_at' => now(),
                'next_invoice_date' => $recurringInvoice->calculateNextInvoiceDate(),
            ]);

            // Check if we've reached the maximum number of invoices
            if ($recurringInvoice->max_invoices && 
                $recurringInvoice->invoices_generated >= $recurringInvoice->max_invoices) {
                $recurringInvoice->update(['is_active' => false]);
            }

            // Check if we've passed the end date
            if ($recurringInvoice->end_date && 
                $recurringInvoice->next_invoice_date->isAfter($recurringInvoice->end_date)) {
                $recurringInvoice->update(['is_active' => false]);
            }

            return $invoice;
        });
    }
}
