<?php

namespace App\Http\Controllers;
use App\Http\Requests;
use Cartalyst\Stripe\Exception\CardErrorException;
use Cartalyst\Stripe\Exception\MissingParameterException;
use Cartalyst\Stripe\Exception\UnauthorizedException;
use Illuminate\Http\Request;
use Validator;
use URL;
use Session;
use Redirect;
use Input;
use App\User;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Stripe\Error\Card;

class AddMoneyController extends Controller
{
    /**
     * AddMoneyController constructor.
     */
    public function __construct()
    {
        $this->user = new User;
    }


    /**
     * Show the application paywith stripe.
     *
     * @return \Illuminate\Http\Response
     */
    public function payWithStripe()
    {
        return view('paywithstripe');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postPaymentWithStripe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_no' => 'required',
            'ccExpiryMonth' => 'required',
            'ccExpiryYear' => 'required',
            'cvvNumber' => 'required',
            'amount' => 'required',
        ]);

        $input = $request->all();
        if ($validator->passes()) {
            $input = array_except($input,array('_token'));
            $stripe = Stripe::make(env('STRIPE_SECRET'));
            try {
                $token = $stripe->tokens()->create([
                    'card' => [
                        'number'    => $request->get('card_no'),
                        'exp_month' => $request->get('ccExpiryMonth'),
                        'exp_year'  => $request->get('ccExpiryYear'),
                        'cvc'       => $request->get('cvvNumber'),
                    ],
                ]);
                if (!isset($token['id'])) {
                    \Session::put('error','The Stripe Token was not generated correctly');
                    return redirect()->route('addmoney.paywithstripe');
                }
                $charge = $stripe->charges()->create([
                    'card' => $token['id'],
                    'currency' => 'USD',
                    'amount'   => $request->get('amount'),
                    'description' => 'Add in wallet',
                ]);
                if($charge['status'] == 'succeeded') {
                    /**
                     * Write Here Your Database insert logic.
                     */
                    \Session::put('success','Money add successfully in wallet');
                    return redirect()->route('addmoney.paywithstripe');
                } else {
                    \Session::put('error','Money not add in wallet!!');
                    return redirect()->route('addmoney.paywithstripe');
                }
            } catch (Exception $e) {
                \Session::put('error',$e->getMessage());
                return redirect()->route('addmoney.paywithstripe');
            } catch(CardErrorException $e) {
                \Session::put('error',$e->getMessage());
                return redirect()->route('addmoney.paywithstripe');
            } catch(MissingParameterException $e) {
                \Session::put('error',$e->getMessage());
                return redirect()->route('addmoney.paywithstripe');
            } /*catch (UnauthorizedException $e) {
                \Session::put('error',$e->getMessage());
                return redirect()->route('addmoney.paywithstripe');
            }*/
        }
        \Session::put('error','All fields are required!!');
        return redirect()->route('addmoney.paywithstripe');
    }
}
