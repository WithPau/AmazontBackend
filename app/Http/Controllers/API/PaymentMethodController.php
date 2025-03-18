<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Store a newly created payment method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|string|in:credit_card,paypal,bank_transfer',
            'card_number' => 'required_if:tipo,credit_card|nullable|string|max:16',
            'card_holder_name' => 'required_if:tipo,credit_card|nullable|string|max:255',
            'expiration_date' => 'required_if:tipo,credit_card|nullable|string|max:7',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        // If this is set as default, unset all other defaults
        if ($request->is_default) {
            $user->paymentMethods()->update(['is_default' => false]);
        }

        $paymentMethod = $user->paymentMethods()->create([
            'tipo' => $request->tipo,
            'card_number' => $request->card_number,
            'card_holder_name' => $request->card_holder_name,
            'expiration_date' => $request->expiration_date,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment method added successfully',
            'payment_method' => $paymentMethod,
        ], 201);
    }

    /**
     * Update the specified payment method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'string|in:credit_card,paypal,bank_transfer',
            'card_number' => 'nullable|string|max:16',
            'card_holder_name' => 'nullable|string|max:255',
            'expiration_date' => 'nullable|string|max:7',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $paymentMethod = $user->paymentMethods()->findOrFail($id);
        
        // If this is set as default, unset all other defaults
        if ($request->is_default) {
            $user->paymentMethods()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $paymentMethod->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Payment method updated successfully',
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Remove the specified payment method.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $paymentMethod = $user->paymentMethods()->findOrFail($id);
        $paymentMethod->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment method deleted successfully',
        ]);
    }
}