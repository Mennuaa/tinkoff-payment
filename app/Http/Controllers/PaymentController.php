<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    private $baseUrl;
    private $terminalKey;
    private $secretKey;

    public function __construct()
    {
        $this->baseUrl = env('TINKOFF_API_URL');
        $this->terminalKey = env('TINKOFF_TERMINAL_KEY');
        $this->secretKey = env('TINKOFF_SECRET_KEY');
    }

    public function create(Request $request)
    {
        // Prepare data for token generation
        $tokenData = [
            'TerminalKey' => $this->terminalKey,
            'Amount' => $request->amount * 100,  // Convert to kopecks if needed
            'OrderId' => $request->orderId,
            'Description' => $request->description,
        ];
    
        // Generate token with the above data
        $token = $this->generateToken($tokenData);
    
        // Make the API request
        $response = Http::post("{$this->baseUrl}Init", array_merge($tokenData, [
            'Token' => $token,
            'DATA' => [
                'Email' => $request->email,
                'Phone' => $request->phone,
            ],
            'Receipt' => [
                'Email' => $request->email,
                'Phone' => $request->phone,
                'Taxation' => 'osn',
                'Items' => [
                    [
                        'Name' => $request->item_name,
                        'Price' => $request->amount * 100,
                        'Quantity' => 1,
                        'Amount' => $request->amount * 100,
                        'Tax' => 'vat10',
                        'Ean13' => $request->ean13,
                    ],
                ],
            ],
        ]));
    
        return $response->json();
    }
    
    public function confirm(Request $request)
    {
        // Prepare data for token generation
        $tokenData = [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $request->paymentId,
        ];
    
        // Include Password for token generation as required by Tinkoff
        $token = $this->generateToken($tokenData);
    
        // Make the API request
        $response = Http::post("{$this->baseUrl}Confirm", [
            'TerminalKey' => $this->terminalKey,
            'PaymentId' => $request->paymentId,
            'Token' => $token,
        ]);
    
        return $response->json();
    }
    public function status($orderId)
    {
        // Prepare data for token generation
        $tokenData = [
            'TerminalKey' => $this->terminalKey,
            'OrderId' => $orderId,
        ];
    
        // Include Password for token generation as required by Tinkoff
        $token = $this->generateToken($tokenData);
    
        // Make the API request
        $response = Http::post("{$this->baseUrl}GetState", [
            'TerminalKey' => $this->terminalKey,
            'OrderId' => $orderId,
            'Token' => $token,
        ]);
    
        return $response->json();
    }
        
    private function generateToken(array $data)
    {
        // Adding Password to the data array
        $data['Password'] = $this->secretKey;
    
        // Sort the array by keys
        ksort($data);
    
        // Concatenate the values of the sorted array
        $tokenString = implode('', array_values($data));
    
        // Return the SHA-256 hash of the token string
        return hash('sha256', $tokenString);
    }
    

}
