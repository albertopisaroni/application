<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Jobs\SendInvoiceToSdiJob;
use App\Services\InvoiceRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\InvoiceMail;
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
                    $invoice = $this->generateInvoiceFromRecurring($recurringInvoice);
                    $this->info("  → Invoice created in database");
                    
                    // Process invoice after creation (PDF, SDI, Email) - outside transaction
                    $this->processInvoiceAfterCreation($invoice);
                }
                
                $processed++;
                $this->info("  ✓ Generated invoice for {$recurringInvoice->client->name}");
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error processing recurring invoice {$recurringInvoice->id}: {$e->getMessage()}");
                Log::error("Error processing recurring invoice", [
                    'recurring_invoice_id' => $recurringInvoice->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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
            // Get the next invoice number from the numbering (consistent with manual creation)
            $numbering = $recurringInvoice->numbering;
            $nextNumber = ($numbering->prefix ? $numbering->prefix . '-' : '') . $numbering->getNextNumericPart();

            // Create the new invoice (consistent with manual creation)
            $invoice = Invoice::create([
                'company_id' => $recurringInvoice->company_id,
                'client_id' => $recurringInvoice->client_id,
                'numbering_id' => $recurringInvoice->numbering_id,
                'payment_method_id' => $recurringInvoice->payment_method_id ?: 1, // Default to first payment method if null
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
                'save_notes_for_future' => false, // Default for recurring invoices
                'sdi_sent_at' => null,
                'sdi_received_at' => null,
                'sdi_attempt' => 1,
            ]);

            // Increment the invoice number counter (consistent with manual creation)
            $numbering->increment('current_number_invoice');

            // Add payment schedule (required for SDI) - consistent with manual creation
            $invoice->paymentSchedules()->create([
                'due_date' => now()->addDays(30)->toDateString(), // Default 30 days payment term
                'amount' => $invoice->total,
                'type' => 'amount',
                'percent' => null,
            ]);

            // Copy items from recurring invoice
            foreach ($recurringInvoice->items as $recurringItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $recurringItem->description, // Use description as name since recurring items don't have a separate name field
                    'description' => $recurringItem->description,
                    'quantity' => $recurringItem->quantity,
                    'unit_of_measure' => '', // Empty like manual creation
                    'unit_price' => $recurringItem->unit_price,
                    'vat_rate' => $recurringItem->vat_rate,
                    // Note: discount field omitted to use database default (0.00) like manual creation
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

    /**
     * Process invoice after creation: generate PDF, send to SDI, send emails
     * This replicates the same workflow as manual invoice creation
     */
    private function processInvoiceAfterCreation(Invoice $invoice): void
    {
        try {
            // Follow exact same order as manual creation:
            
            // 1) Generate PDF and upload to S3 FIRST (like manual creation)
            $this->info("  → Generating PDF...");
            $this->generateAndStorePdf($invoice);

            // 2) Send to SDI AFTER PDF is ready (like manual creation)  
            $this->info("  → Sending invoice to SDI...");
            SendInvoiceToSdiJob::dispatch($invoice->id);

            // 3) Send emails to client contacts
            $this->info("  → Sending emails...");
            $this->sendInvoiceEmails($invoice);

        } catch (\Exception $e) {
            $this->error("  ✗ Error in post-processing: {$e->getMessage()}");
            Log::error("Error processing recurring invoice {$invoice->id} after creation", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Generate PDF and store it on S3
     */
    private function generateAndStorePdf(Invoice $invoice): void
    {
        try {
            // Reload invoice with all necessary relations
            $invoice = Invoice::with(['items', 'company', 'client', 'numbering'])->find($invoice->id);
            $this->info("    → Invoice reloaded with relations");
            
            // Get invoice items for PDF generation
            $items = $invoice->items->map(function($item) {
                return [
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                    'unit_of_measure' => $item->unit_of_measure,
                ];
            })->toArray();
            $this->info("    → Items prepared: " . count($items));

            // Create renderer and generate PDF (using empty arrays for payments like in manual creation)
            $renderer = new InvoiceRenderer($invoice, $items, [], false, null);
            $this->info("    → Renderer created");
            
            $pdf = $renderer->renderPdf();
            $this->info("    → PDF generated");

            // Generate S3 path
            $companySlug = $invoice->company->slug;
            $year = Carbon::parse($invoice->issue_date)->format('Y');
            $path = "clienti/{$companySlug}/fatture/{$invoice->numbering_id}/{$year}/{$invoice->invoice_number}.pdf";
            $this->info("    → S3 path: {$path}");

            // Encrypt and store on S3
            $encrypted = encrypt($pdf);
            Storage::disk('s3')->put($path, $encrypted);
            $this->info("    → PDF uploaded to S3");

            // Update invoice with PDF info using fresh instance
            Invoice::where('id', $invoice->id)->update([
                'pdf_path' => $path,
                'pdf_url' => config('app.fatture_url')."/{$invoice->uuid}/pdf",
            ]);
            $this->info("    → Invoice updated with PDF info");

            $this->info("  ✓ PDF generated and stored: {$path}");
        } catch (\Exception $e) {
            $this->error("    ✗ PDF generation failed: {$e->getMessage()}");
            Log::error("PDF generation failed for invoice {$invoice->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Send invoice emails to client contacts
     */
    private function sendInvoiceEmails(Invoice $invoice): void
    {
        // Reload invoice to ensure we have the latest PDF URL
        $invoice = Invoice::with(['client.contacts', 'company'])->find($invoice->id);
        
        $recipients = $invoice->client->contacts()
            ->where('receives_invoice_copy', 1)
            ->pluck('email')
            ->toArray();

        if (empty($recipients)) {
            $this->info("  📭 No email contacts configured for client {$invoice->client->name}");
            return;
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new InvoiceMail($invoice, $invoice->company));
                $this->info("  📤 Email sent to: {$email}");
                Log::info("📤 Recurring invoice {$invoice->invoice_number} emailed to: {$email}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to send email to {$email}: {$e->getMessage()}");
                Log::error("Failed to send recurring invoice email", [
                    'invoice_id' => $invoice->id,
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
