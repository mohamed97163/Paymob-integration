<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class PaymentController extends Controller
{
    public function getToken() {
        $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => "YOUR_API_KEY"
        ]);
        // return $response->object()->token;
        return new JsonResponse(['data'=>$response->object()->token]);
    }

    public function createOrder(Request $request,$tokens) {
        //this function takes last step token and send new order to paymob dashboard

        // $amount = new Checkoutshow; here you add your checkout controller
        // $total = $amount->totalProductAmount(); total amount function from checkout controller
        // here we add example for test only
        $items = [
            [
                "name"=> $request->event_name,
                "amount_cents"=> $request->total_price,
            ],

        ];

        $data = [
            "auth_token" =>   $tokens,
            "delivery_needed" =>"false",
            "amount_cents"=> $request->total_price,
            "currency"=> "EGP",
            "items"=> $items,
        ];

        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', $data);

        return new JsonResponse([
            'id' => $response->object()->id,
            'amount_cents' => $response->object()->amount_cents,
        ]);
    }

    public function getPaymentToken($order_id, $price, $integration_id, $token ,$eventID)
    {
        $state = State::find(Auth::user()->state_id);
        //all data we fill is required for paymob
        $billingData = [
            "apartment" => 'NA', //example $dataa->appartment
            "email" => Auth::user()->email, //example $dataa->email
            "floor" => '5',
            "first_name" => Auth::user()->nameAR,
            "street" => "NA",
            "building" => "NA",
            "phone_number" =>  Auth::user()->phone,
            "shipping_method" => "NA",
            "postal_code" => "NA",
            "city" => 'NA',
            "country" => "NA",
            "last_name" => Auth::user()->nameEN,
            "state" => $state->state_name,
        ];
        $data = [
            "auth_token" => $token,
            "amount_cents" => $price,
            "expiration" => 3600,
            "order_id" => $order_id, // this order id created by paymob
            "billing_data" => $billingData,
            "currency" => "EGP",
            "integration_id" => $integration_id,
        ];
        $payment = Payment::create([
            'total_price' => $price,
            'transaction_id' => $order_id,
            'event_id' => $eventID,
            'transaction_status' => 'pending',
        ]);
        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', $data);
        return new JsonResponse(['data'=>$response->object()->token]);
    }
    public function callback(Request $request)
    {

        //this call back function its return the data from paymob and we show the full response and we checked if hmac is correct means successfull payment
        $data = $request->all();
        ksort($data);
        $hmac = $data['hmac'];

        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',

        ];

        $connectedString = '';
        foreach ($data as $key => $element) {
            if(in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = 'YOUR_SECRET_KEY';
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac) {
            //this below data used to get the last order created by the customer and check if its exists to
            $status = $data['success'];

             if ( $status == "true" ) {


                 $payment = Payment::where('transaction_id',$request->order)->first();
                 $payment->update([
                     'transaction_status' => 'finished',
                 ]);
                 return view('success');

             }
             else {
                 abort(404);
             }

        }
        else {
            abort(404);
        }
    }
    public function checkVerifyPaymentStatus($transaction_id)
    {
        $payment = Payment::where('transaction_id',$transaction_id)->first();
        if ($payment->transaction_status == "finished")
        {
            return response()->json(['status' => true, 'message' => 'Payment successful']);
        }
        else {
            return response()->json(['status' => false, 'message' => 'Payment failed']);
        }

    }
    public function index()  {
        return view('checkout');
    }

}
