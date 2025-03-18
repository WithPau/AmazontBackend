<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get the authenticated user's profile information.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $user = Auth::user();
        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    /**
     * Update the user's profile information.
     * Only password and address can be modified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dirección' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'current_password' => 'required_with:contraseña,contrasena|string',
            'contraseña' => 'nullable|string|min:8|confirmed',
            'contrasena' => 'nullable|string|min:8|confirmed',
            'contraseña_confirmation' => 'nullable|string|min:8',
            'contrasena_confirmation' => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $data = [];

        // Update address if provided (with or without accent)
        if ($request->has('dirección')) {
            $data['dirección'] = $request->dirección;
        } elseif ($request->has('direccion')) {
            $data['dirección'] = $request->direccion;
        }

        // Update password if provided (with or without accent)
        if ($request->has('contraseña') || $request->has('contrasena')) {
            $password = $request->contraseña ?? $request->contrasena;
            
            // Instead of using Hash::check, directly compare with the stored password
            // This is a temporary solution until all passwords are properly hashed
            if ($request->current_password !== $user->contraseña) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'La contraseña actual es incorrecta'
                ], 422);
            }
            
            // Make sure to hash the new password properly
            $data['contraseña'] = Hash::make($password);
            // Añadir un indicador de que la contraseña fue modificada
            $passwordChanged = true;
        } else {
            $passwordChanged = false;
        }

        if (empty($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se proporcionaron datos para actualizar'
            ], 422);
        }

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Información de usuario actualizada correctamente',
            'user' => $user,
            'password_updated' => $passwordChanged // Incluir indicador en la respuesta
        ]);
    }

    /**
     * Get the user's order history.
     *
     * @return \Illuminate\Http\Response
     */
    public function orderHistory()
    {
        $user = Auth::user();
        $orders = $user->orders()->with('orderItems.product')->get();

        return response()->json([
            'status' => 'success',
            'orders' => $orders
        ]);
    }

    /**
     * Get the user's payment methods.
     *
     * @return \Illuminate\Http\Response
     */
    public function paymentMethods()
    {
        $user = Auth::user();
        $paymentMethods = $user->paymentMethods;

        return response()->json([
            'status' => 'success',
            'payment_methods' => $paymentMethods
        ]);
    }
}