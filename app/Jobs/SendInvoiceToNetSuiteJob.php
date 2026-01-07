<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceToNetSuiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $invoice;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Timeout del job (segundos)
     */
    public int $timeout = 120;

    /**
     * Crear nueva instancia del Job
     */
    public function __construct(int $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Ejecutar el Job
     */
    public function handle(): void
    {
        Log::info("Job SendInvoiceToNetSuiteJob iniciado", [
            'invoice' => $this->invoice
        ]);


        Log::info("Job SendInvoiceToNetSuiteJob finalizado", [
            'invoice' => $this->invoice
        ]);
    }
}
