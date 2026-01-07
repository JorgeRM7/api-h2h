<?php

use Illuminate\Support\Facades\Route;
use App\Services\NetSuiteRestService;
use App\Models\H2hDocument;
use GuzzleHttp\Client;
use App\Jobs\SendInvoiceToNetSuiteJob;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dispatch-invoice-job/{invoice}', function ($invoice) {

    SendInvoiceToNetSuiteJob::dispatch((int)$invoice);

    return response()->json([
        'status' => 'success',
        'message' => "Job disparado para la factura: {$invoice}",
        'timestamp' => now()->toDateTimeString()
    ]);
});


Route::get('/send-invoices-netsuite', function ( NetSuiteRestService $netsuite ) {

    $invoice = 6759;

    $DOCUMENT_DOPU = H2hDocument::select([
        'id',
        'category_id',
        'netsuite_id',
        'filename'
    ])
    ->where('invoice', $invoice)
    ->where('category_id', 1)
    ->orderByDesc('id')
    ->first();

    $DOCUMENT_COMP = H2hDocument::select([
        'id',
        'category_id',
        'netsuite_id',
        'filename'
    ])
    ->where('invoice', $invoice)
    ->where('category_id', 5)
    ->orderByDesc('id')
    ->first();

    $pdfUrl = "https://servicios-go.com/h2h/download/{$invoice}";

    $client = new Client([
        'verify' => false,
        'timeout' => 60,
    ]);
    $response = $client->get($pdfUrl);

    if ($response->getStatusCode() !== 200) {
        abort(500, 'No se pudo descargar el PDF remoto');
    }
    $pdfBase64 = base64_encode($response->getBody()->getContents());


    $payload = [
        'success' => true,
        'response' => [
            'action' => 'Update',
            'update_data_register' => [
                'ctrl' => [
                    'codigo'  => [],
                    'estatus' => [],
                ],
                'content' => '',
                'unique_idenfier' => $DOCUMENT_DOPU->filename,
                'internalid_netsuite' => $DOCUMENT_DOPU->netsuite_id,
                'prefijo_archivo' => 'COMP',
                'filename' => $DOCUMENT_COMP->filename,
                'srv_data' => '08_TG-0010',
                'files' => [
                    'txt' => null,
                    'pdf' => $pdfBase64 ?? '',
                    'xml' => null,
                ],
            ],
        ],
    ];

    $endpoint = config('services.netsuite.script_payment');
    $netsuiteResponse = $netsuite->request($endpoint, 'POST', $payload);

    return response()->json([
        // 'sent_payload' => $payload,
        'netsuite_response' => $netsuiteResponse,
    ]);

});

