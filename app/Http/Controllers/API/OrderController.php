<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Create a new order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id_prod',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        
        // Calculate total
        $total = 0;
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $price = $product->on_sale ? $product->sale_price : $product->price;
            $total += $price * $item['quantity'];
        }

        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total' => $total,
                'payment_method_id' => $request->payment_method_id,
            ]);

            // Create order items
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $product->on_sale ? $product->sale_price : $product->price;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ]);
                
                // Update stock
                $product->update([
                    'stock' => $product->stock - $item['quantity'],
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'order' => $order->load('orderItems.product'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with('orderItems.product')
            ->where('user_id', $user->id)
            ->findOrFail($id);
            
        return response()->json([
            'status' => 'success',
            'order' => $order
        ]);
    }
}