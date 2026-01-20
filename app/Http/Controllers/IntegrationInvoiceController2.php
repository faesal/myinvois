<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
class IntegrationInvoiceController2 extends Controller
{


    /**
     * Store invoice from MySyncTax integration (with tax + customer output)
     */

    public function invoice(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');

        $blockedTin = [
            'EI00000000010',
            'EI00000000020',
            'EI00000000030',
            'EI00000000040'
        ];

        if (in_array($tin, $blockedTin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'This TIN No. is not allowed for Normal Invoice'
            ], 422);
        }

        $model = new \App\Models\eInvoisModel();
        $result = $model->processInvoice($payload, "NORMAL");
    
        return response()->json($result, 201);
    }

    public function invoice_general(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');

        $allowedTin = [
            'EI00000000010',
            'EI00000000020',
            'EI00000000030',
            'EI00000000040'
        ];

        if (!in_array($tin, $allowedTin)) {
            return response()->json([
                'status' => 'error',
                'message' => 'TIN No. is not valid for General Invoice'
            ], 422);
        }



        $model = new \App\Models\eInvoisModel();
        $result = $model->processInvoice($payload, "GENERAL");
    
        return response()->json($result, 201);

        
    }

    public function invoice_general_note(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');


        $model = new \App\Models\eInvoisModel();
        $result = $model->processNote($payload, "general");
    
        return response()->json($result, 201);

        
    }


    public function invoice_general_selfbill(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $tin = data_get($payload, 'customer.tin_no');


        $model = new \App\Models\eInvoisModel();
        $result = $model->processNote($payload, "selfbill");
    
        return response()->json($result, 201);

        
    }


    public function note(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        $model = new \App\Models\eInvoisModel();
        return $model->processNote($payload, 'normal');
    }

    public function selfBillNote(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        $model = new \App\Models\eInvoisModel();
        return $model->processNote($payload, 'selfbill');
    }

    public function generalselfBillNote(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        $model = new \App\Models\eInvoisModel();
        return $model->processNote($payload, 'selfbill_general');
    }


}
