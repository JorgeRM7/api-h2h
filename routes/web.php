<?php

use Illuminate\Support\Facades\Route;
use App\Services\NetSuiteRestService;
use App\Models\H2hDocument;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test-db', function () {

    $invoice = 6761;
    // $documents = H2hDocument::where('invoice', $invoice)->get();
    //
    $documents = H2hDocument::select([
        'id',
        'category_id',
        'netsuite_id',
        'filename'
    ])
    ->where('invoice', $invoice)
    ->where('category_id', 1)
    ->orderBy('id')
    ->limit(4)
    ->get();

    $windowsBasePath = '\\\\54.218.15.205\\H2H\\Outbound';
    $filePath = $windowsBasePath . '\\' . $document->filename;

    return response()->json($documents);

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
                'unique_idenfier' => $document->invoice,
                'internalid_netsuite' => $document->netsuite_id,
                'prefijo_archivo' => 'COMP',
                'filename' => $document->filename,
                'srv_data' => '08_TG-0010',
                'files' => [
                    'txt' => null,
                    'pdf' => $document->filepath ?? '',
                    'xml' => null,
                ],
            ],
        ],
    ];

    return response()->json($payload);

});

Route::get('/netsuite/gastos-full', function (NetSuiteRestService $netsuite) {

    $endpoint = config('services.netsuite.script_payment');

    $data = [
        'ubicacion' => 1718,
        'fechaini'  => '05/06/2024',
        'fechafin'  => '20/06/2024',
    ];

    // $response = $netsuite->request($endpoint, 'POST', $data);

    // return response()->json($response);
});
