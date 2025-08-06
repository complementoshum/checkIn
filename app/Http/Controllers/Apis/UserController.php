<?php

namespace App\Http\Controllers\Apis;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Http;

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
                $qrSvg = QrCode::format('svg')->size(150)->margin(2)->generate($userId);
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

    public function sendMessage()
    {
        try {
            $users = User::whereNotNull('qrImagen')->get();
            
            if ($users->isEmpty()) {
                return response()->json([
                    "status" => true,
                    "msg" => "No se encontraron usuarios."
                ], 200);
            }

            $sends = 0;
            foreach ($users as $user) {
                $userPhone = "57" . $user->telefono;
                $userImage = str_replace('data:image/svg+xml;base64,', '', $user->qrImagen);

                $userMessage = "Este es tu qr de ingreso al envento";

                $response = Http::post(env("API_MESSAGE") . 'whatsapp/send-message', [
                    "number"    => $userPhone,
                    "message"   => $userImage,
                    "isImage"   => true,
                    "mimetype"  => "image/svg+xml",
                    "filename"  => "foto.jpg",
                    "caption"   => $userMessage
                ]);

                $data = $response->body();

                if ($response->successful()) {
                    $sends++;
                }
            }

            return response()->json([
                "status" => true,
                "msg" => "Mensajes enviados con exito: " . $sends . " de " . count($users)
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                "status" => false,
                "msg" => "Ocurrió un error al enviar mensajes.",
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

    public function checkIn($id)
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
