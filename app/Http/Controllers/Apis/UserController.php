<?php

namespace App\Http\Controllers\Apis;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Typography\FontFactory;

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

                $qr = QrCode::create((string)$user->id)
                    ->setSize(150)
                    ->setMargin(2)
                    ->setForegroundColor(new Color(91, 73, 59));

                $writer = new PngWriter();
                $result = $writer->write($qr);

                $qrPngBinary = $result->getString();
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrPngBinary);

                User::where('id', $userId)->update([
                    'qrImagen' => $qrBase64
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

    public function generateInvitation(Request $request)
    {
        try {
            // --- Usuarios sin invitación pero con QR ---
            $users = User::whereNotNull('qrImagen')
                ->where('id', 88)
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    "status" => true,
                    "msg"    => "No se encontraron invitaciones pendientes por generar."
                ], 200);
            }

            // --- Configuración general ---
            $manager = new ImageManager(new Driver());
            $bgPath  = public_path('imgs/fondo.png');

            if (!is_file($bgPath)) {
                return response()->json([
                    "status" => false,
                    "msg"    => "No se encontró el fondo en public/imgs/fondo.png"
                ], 500);
            }

            // --- Parámetros QR ---
            $QR_SIZE     = 200;
            $QR_X        = 218;
            $QR_Y        = 625;

            // --- Parámetros texto ---
            $NAME_X      = 70;
            $NAME_COLOR  = '#ffffff';
            $FONT_PATH   = storage_path('app/fonts/Baskervville-SemiBold.ttf');

            $previews = [];

            foreach ($users as $user) {
                $NAME_Y      = 345;
                // --- Fondo escalado ---
                $background = $manager->read($bgPath)
                    ->scale(width: 600);

                // Tamaño dinámico de la fuente
                $NAME_SIZE = max(20, min(110, (int) round($background->width() * 0.042)));

                // --- QR desde BD ---
                $qrPngBinary = null;
                if (!empty($user->qrImagen)) {
                    $qrData = $user->qrImagen;
                    if (str_starts_with($qrData, 'data:image')) {
                        [, $b64] = explode(',', $qrData, 2);
                        $qrPngBinary = base64_decode($b64);
                    } else {
                        $qrPngBinary = base64_decode($qrData);
                    }
                }

                // Ajustar QR al tamaño definido
                $qrImage = $manager->read($qrPngBinary);
                if ($qrImage->width() !== $QR_SIZE) {
                    $qrImage = $qrImage->scale(width: $QR_SIZE);
                }

                // Colocar QR
                $background->place($qrImage, 'top-left', $QR_X, $QR_Y);

                // --- Texto de nombres ---
                $allNames = [];

                // Agregar nombre principal si existe y no está vacío
                if (!empty(trim($user->nombres))) {
                    $allNames[] = trim($user->nombres);
                }

                // Agregar invitados separados por coma, limpiando vacíos
                $guests = array_filter(
                    array_map('trim', explode(',', ($user->invitados ?? ''))),
                    fn($g) => $g !== '' // descartar strings vacíos
                );

                // Unir todo
                $allNames = array_merge($allNames, $guests);

                // Limitar a máximo 4 nombres
                if (count($allNames) > 4) {
                    return response()->json([
                        "status" => false,
                        "msg"    => "La invitación del usuario {$user->id} supera el máximo de 4 invitados permitidos.",
                        "user"   => [
                            "id"       => $user->id,
                            "nombres"  => $user->nombres,
                            "invitados" => $user->invitados,
                        ]
                    ], 422);
                }

                foreach ($allNames as $key => $name) {
                    $background->text($name, $NAME_X, $NAME_Y, function ($font) use ($NAME_SIZE, $FONT_PATH, $NAME_COLOR) {
                        $font->size($NAME_SIZE)
                            ->color($NAME_COLOR)
                            ->align('left');
                        if (is_file($FONT_PATH)) {
                            $font->filename($FONT_PATH);
                        }
                    });
                    $NAME_Y += 32;
                }

                // --- Exportar resultado ---
                $finalBinary = $background->toPng();

                $user->imgInvitacion = base64_encode($finalBinary);
                $user->save();
            }
            return response()->json([
                "status"   => true,
                "msg"      => "Invitaciones generadas con éxito.",
            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                "status" => false,
                "msg"    => "Ocurrió un error al generar las invitaciones.",
                "error"  => $ex->getMessage()
            ], 500);
        }
    }

    public function generateInvitationTwo(Request $request)
    {
        try {
            // --- Usuarios sin invitación pero con QR ---
            $users = User::whereNotNull('qrImagen')
                ->get();

            if ($users->isEmpty()) {
                return response()->json([
                    "status" => true,
                    "msg"    => "No se encontraron invitaciones pendientes por generar."
                ], 200);
            }

            // --- Configuración general ---
            $manager = new ImageManager(new Driver());
            $bgPath  = public_path('imgs/fondo2.png');

            if (!is_file($bgPath)) {
                return response()->json([
                    "status" => false,
                    "msg"    => "No se encontró el fondo en public/imgs/fondo2.png"
                ], 500);
            }

            // --- Parámetros QR ---
            $QR_SIZE     = 275;
            $QR_X        = 165;
            $QR_Y        = 400;

            // --- Parámetros texto ---
            $NAME_X      = 180;
            $NAME_COLOR  = '#ffffff';
            $FONT_PATH   = storage_path('app/fonts/Poppins-Medium.ttf');

            $previews = [];

            foreach ($users as $user) {
                $NAME_Y      = 790;
                // --- Fondo escalado ---
                $background = $manager->read($bgPath)
                    ->scale(width: 600);

                // Tamaño dinámico de la fuente
                $NAME_SIZE = max(10, min(110, (int) round($background->width() * 0.033)));

                // --- QR desde BD ---
                $qrPngBinary = null;
                if (!empty($user->qrImagen)) {
                    $qrData = $user->qrImagen;
                    if (str_starts_with($qrData, 'data:image')) {
                        [, $b64] = explode(',', $qrData, 2);
                        $qrPngBinary = base64_decode($b64);
                    } else {
                        $qrPngBinary = base64_decode($qrData);
                    }
                }

                // Ajustar QR al tamaño definido
                $qrImage = $manager->read($qrPngBinary);
                if ($qrImage->width() !== $QR_SIZE) {
                    $qrImage = $qrImage->scale(width: $QR_SIZE);
                }

                // Colocar QR
                $background->place($qrImage, 'top-left', $QR_X, $QR_Y);

                // --- Texto de nombres ---

                $name = ucwords(mb_strtolower($user->nombres));
                $background->text($name, $background->width() / 2, $NAME_Y, function ($font) use ($NAME_SIZE, $FONT_PATH, $NAME_COLOR) {
                    $font->size($NAME_SIZE)
                        ->color($NAME_COLOR)
                        ->align('center');
                    if (is_file($FONT_PATH)) {
                        $font->filename($FONT_PATH);
                    }
                });

                // --- Exportar resultado ---
                $finalBinary = $background->toPng();

                $user->imgInvitacion = base64_encode($finalBinary);
                $user->save();
            }
            return response()->json([
                "status"   => true,
                "msg"      => "Invitaciones generadas con éxito.",
            ], 200);
        } catch (\Throwable $ex) {
            return response()->json([
                "status" => false,
                "msg"    => "Ocurrió un error al generar las invitaciones.",
                "error"  => $ex->getMessage()
            ], 500);
        }
    }

    public function sendMessage()
    {
        try {
            $users = User::whereNotNull('imgInvitacion')->get();

            if ($users->isEmpty()) {
                return response()->json([
                    "status" => true,
                    "msg" => "No se encontraron usuarios."
                ], 200);
            }

            $sends = 0;
            foreach ($users as $user) {
                $userPhone = $user->indicativo . $user->telefono;
                $userImage = $user->imgInvitacion;

                $userMessage = "¡Hola, {$user->nombres}!,

Este sábado nos encontraremos para celebrar la vida; tendremos un encuentro entre amigos y buena música.

Antes de ingresar, debes presentar este código QR al personal del teatro. Recuerda que solo podrán ingresar las personas que están listadas en la imagen.

Te sugiero llegar desde las 5:30 p.m. para que elijas tu lugar con calma y no te pierdas nada.

¡Nos vemos en el teatro!";

                $response = Http::post(env("API_MESSAGE") . 'whatsapp/send-message', [
                    "number"    => $userPhone,
                    "message"   => $userImage,
                    "isImage"   => true,
                    "mimetype"  => "image/png",
                    "filename"  => "foto.jpg",
                    "caption"   => $userMessage
                ]);

                $data = $response->body();

                if ($response->successful()) {
                    $sends++;
                    Log::info("Mensaje enviado con éxito", [
                        "user_id"   => $user->id,
                        "telefono"  => $userPhone,
                        "nombres"   => $user->nombres
                    ]);
                } else {
                    Log::warning("Error al enviar mensaje", [
                        "user_id"   => $user->id,
                        "telefono"  => $userPhone,
                        "response"  => $response->body()
                    ]);
                }

                sleep(rand(3, 8));
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

            $now = Carbon::now()->format('Y-m-d H:i:s');

            User::where('id', $id)->update([
                'checkIn' => $now
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
