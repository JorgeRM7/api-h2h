<?php

namespace App\Jobs;

use App\Models\H2hDocument;
use App\Services\NetSuiteRestService;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Exception;

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
    // public function handle(): void
    // {
    //     Log::info("Job SendInvoiceToNetSuiteJob iniciado", [
    //         'invoice' => $this->invoice
    //     ]);


    //     Log::info("Job SendInvoiceToNetSuiteJob finalizado", [
    //         'invoice' => $this->invoice
    //     ]);
    // }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->invoice)];
    }

    public function handle(NetSuiteRestService $netsuite): void
    {
        Log::channel('netsuite')->info("Iniciando envío de factura", ['invoice' => $this->invoice]);

        try {
            $documentDopu = H2hDocument::where('invoice', $this->invoice)
                ->where('category_id', 1)
                ->orderByDesc('id')
                ->first();

            $documentComp = H2hDocument::where('invoice', $this->invoice)
                ->where('category_id', 5)
                ->orderByDesc('id')
                ->first();

            if (!$documentDopu || !$documentComp) {
                throw new Exception("Documentos incompletos para la factura {$this->invoice} (DOPU o COMP no encontrados)");
            }

            $pdfUrl = "https://servicios-go.com/h2h/download/{$this->invoice}";
            $client = new Client(['verify' => false, 'timeout' => 60]);
            $response = $client->get($pdfUrl);

            if ($response->getStatusCode() !== 200) {
                throw new Exception("No se pudo descargar el PDF para la factura {$this->invoice}");
            }

            $pdfBase64 = base64_encode($response->getBody()->getContents());

            // 3. Preparar Payload
            $payload = [
                'success' => true,
                'response' => [
                    'action' => 'Update',
                    'update_data_register' => [
                        'ctrl' => ['codigo'  => [], 'estatus' => []],
                        'content' => '',
                        'unique_idenfier' => $documentDopu->filename,
                        'internalid_netsuite' => $documentDopu->netsuite_id,
                        'prefijo_archivo' => 'COMP',
                        'filename' => $documentComp->filename,
                        'srv_data' => '08_TG-0010',
                        'files' => [
                            'txt' => null,
                            'pdf' => $pdfBase64,
                            'xml' => null,
                        ],
                    ],
                ],
            ];

            $endpoint = config('services.netsuite.script_payment');
            $netsuiteResponse = $netsuite->request($endpoint, 'POST', $payload);

            // Log::info("Envío exitoso a NetSuite", [
            //     'invoice' => $this->invoice,
            //     'netsuite_response' => $netsuiteResponse
            // ]);

            Log::channel('netsuite')->info("Envío exitoso a NetSuite", ['invoice' => $this->invoice, 'netsuite_response' => $netsuiteResponse]);

        } catch (Exception $e) {
            // Log::error("Error en SendInvoiceToNetSuiteJob", [
            //     'invoice' => $this->invoice,
            //     'error' => $e->getMessage()
            // ]);

            Log::channel('netsuite')->info("Error en SendInvoiceToNetSuiteJob", ['invoice' => $this->invoice, 'error' => $e->getMessage()]);

            // Reintentar el job si es un error de conexión
            if ($this->attempts() < $this->tries) {
                $this->release(30);
            }
        }
    }
}
