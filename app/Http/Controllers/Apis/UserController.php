<?php

namespace App\Http\Controllers\Apis;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    public function generateQrCode(Request $request)
    {
        try {
            $users = User::whereNull('qrImagen')->get();
            if ($users->isEmpty()) {
                return response()->json([
                    "status" => true,
                    "msg" => "Todos los usuarios ya tienen un código QR generado."
                ], 200);
            }

            foreach ($users as $user) {
                $userId = $user->id;
                $qrSvg = QrCode::format('svg')->size(300)->generate("{$userId}");
                $base64Image = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

                User::where('id', $userId)->update([
                    'qrImagen' => $base64Image
                ]);
            }

            return response()->json([
                "status" => true,
                "msg" => "Códigos QR generados y almacenados en base de datos correctamente."
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                "status" => false,
                "msg" => "Ocurrió un error al generar el código QR.",
                "error" => $ex->getMessage()
            ], 500);
        }
    }

    public function getUser($id)
    {
        try {
            if (!$id) {
                return response()->json([
                    "status" => false,
                    "msg" => "El ID del usuario es requerido."
                ], 400);
            }

            $user = User::where('id', $id)->first();
            if (!$user) {
                return response()->json([
                    "status" => false,
                    "msg" => "Usuario no encontrado."
                ], 404);
            }

            return response()->json([
                "status" => true,
                "msg" => "Usuario encontrado correctamente.",
                "user" => $user
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                "status" => false,
                "msg" => "Ocurrió un error al obtener el usuario.",
                "error" => $ex->getMessage()
            ], 500);
        }
    }

    public function checkIn(Request $request)
    {
        try {
            $id = $request->input('id');
            if (!$id) {
                return response()->json([
                    "status" => false,
                    "msg" => "El ID del usuario es requerido."
                ], 400);
            }

            $user = User::where('id', $id)->first();
            if (!$user) {
                return response()->json([
                    "status" => false,
                    "msg" => "Usuario no encontrado."
                ], 404);
            }

            User::where('id', $id)->update([
                'checkIn' => now()
            ]);

            return response()->json([
                "status" => true,
                "msg" => "Check-in realizado correctamente."
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                "status" => false,
                "msg" => "Ocurrió un error al realizar el check-in.",
                "error" => $ex->getMessage()
            ], 500);
        }
    }
}
