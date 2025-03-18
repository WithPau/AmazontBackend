<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::with('category');
        
        // Filter by category if provided
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        
        // Filter by price range if provided
        if ($request->has('precio_min')) {
            $query->where('precio', '>=', $request->precio_min);
        }
        
        if ($request->has('precio_max')) {
            $query->where('precio', '<=', $request->precio_max);
        }
        
        // Search by name if provided
        if ($request->has('buscar')) {
            $query->where('nombre', 'like', '%' . $request->buscar . '%')
                  ->orWhere('descricion', 'like', '%' . $request->buscar . '%');
        }
        
        // Sort products
        $sortBy = $request->get('ordenar_por', 'created_at');
        $sortOrder = $request->get('orden', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Paginate results
        $perPage = $request->get('por_pagina', 10);
        $products = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'productos' => $products
        ]);
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'producto' => $product
        ]);
    }

    /**
     * Get featured products for the home page.
     *
     * @return \Illuminate\Http\Response
     */
    public function featured()
    {
        $featuredProducts = Product::where('rebajas', true)
                                  ->orderBy('created_at', 'desc')
                                  ->take(6)
                                  ->get();
        
        return response()->json([
            'status' => 'success',
            'productos_destacados' => $featuredProducts
        ]);
    }

    /**
     * Get products by category.
     *
     * @param  int  $categoryId
     * @return \Illuminate\Http\Response
     */
    public function byCategory($categoryId)
    {
        $products = Product::where('categoria_id', $categoryId)->get();
        
        return response()->json([
            'status' => 'success',
            'productos' => $products
        ]);
    }

    /**
     * Store a newly created product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descricion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'rebajas' => 'boolean',
            'precio_rebajado' => 'nullable|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id_cat',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'producto' => $product
        ], 201);
    }

    /**
     * Update the specified product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'string|max:255',
            'descricion' => 'nullable|string',
            'precio' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'rebajas' => 'boolean',
            'precio_rebajado' => 'nullable|numeric|min:0',
            'categoria_id' => 'exists:categorias,id_cat',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($id);
        $product->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'producto' => $product
        ]);
    }

    /**
     * Remove the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}
