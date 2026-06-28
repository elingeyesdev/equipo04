<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class ChatController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // GET /chat
    // Vista principal del chat.
    // ──────────────────────────────────────────────────────────────────────────
    public function index(): View
    {
        return view('chat.index');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /chat/authorities
    // Lista de autoridades disponibles para chatear (excluye al usuario actual).
    // ──────────────────────────────────────────────────────────────────────────
    public function authorities(Request $request): JsonResponse
    {
        $apiUser  = (array) $request->session()->get('api_user', []);
        $myCarnet = (string) ($apiUser['carnet'] ?? '');

        $authorities = User::whereIn('role', [User::ROLE_AUTHORITY, User::ROLE_SUPER_ADMIN])
            ->where('carnet', '!=', $myCarnet)
            ->where('is_banned', false)
            ->orderBy('name')
            ->get(['carnet', 'name'])
            ->map(fn($u) => [
                'carnet'   => $u->carnet,
                'name'     => $u->name,
                'initials' => strtoupper(substr($u->name, 0, 1)),
            ]);

        return response()->json($authorities);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /chat/history/{carnet}
    // Últimos 50 mensajes entre el usuario actual y {carnet}.
    // ──────────────────────────────────────────────────────────────────────────
    public function history(Request $request, string $carnet): JsonResponse
    {
        $apiUser  = (array) $request->session()->get('api_user', []);
        $myCarnet = (string) ($apiUser['carnet'] ?? '');

        $messages = ChatMessage::where(function ($q) use ($myCarnet, $carnet) {
                $q->where('sender_carnet', $myCarnet)
                  ->where('receiver_carnet', $carnet);
            })
            ->orWhere(function ($q) use ($myCarnet, $carnet) {
                $q->where('sender_carnet', $carnet)
                  ->where('receiver_carnet', $myCarnet);
            })
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get(['id', 'sender_carnet', 'sender_name', 'receiver_carnet', 'message', 'created_at']);

        return response()->json($messages);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /chat/message
    // Persiste el mensaje y lo emite por Reverb al canal privado.
    // ──────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_carnet' => 'required|string|max:50',
            'message'         => 'required|string|max:2000',
        ]);

        $apiUser = (array) $request->session()->get('api_user', []);

        $msg = ChatMessage::create([
            'sender_carnet'   => (string) ($apiUser['carnet'] ?? 'unknown'),
            'sender_name'     => (string) ($apiUser['name']   ?? 'Autoridad'),
            'receiver_carnet' => $validated['receiver_carnet'],
            'channel'         => ChatMessage::channelFor(
                (string) ($apiUser['carnet'] ?? 'unknown'),
                (string) $validated['receiver_carnet'],
            ),
            'message'         => $validated['message'],
        ]);

        try {
            broadcast(new ChatMessageSent($msg))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Chat broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'id'              => $msg->id,
            'sender_carnet'   => $msg->sender_carnet,
            'sender_name'     => $msg->sender_name,
            'receiver_carnet' => $msg->receiver_carnet,
            'message'         => $msg->message,
            'created_at'      => $msg->created_at?->toISOString(),
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /chat/auth
    // Autenticación de canales privados de Reverb con sesión personalizada.
    // El frontend de Laravel Echo apunta aquí en vez de /broadcasting/auth.
    // ──────────────────────────────────────────────────────────────────────────
    public function broadcastAuth(Request $request): JsonResponse
    {
        $apiUser  = (array) $request->session()->get('api_user', []);
        $myCarnet = (string) ($apiUser['carnet'] ?? '');
        $myRole   = (string) ($apiUser['role']   ?? '');

        // Solo autoridades autenticadas
        if ($myCarnet === '' || !in_array($myRole, ['authority', 'super-admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $channelName = (string) $request->input('channel_name', '');
        $socketId    = (string) $request->input('socket_id', '');

        // Validar que el canal pertenece a este usuario
        // Formato esperado: private-chat.{a}.{b}
        if (!preg_match('/^private-chat\.([^.]+)\.([^.]+)$/', $channelName, $m)) {
            return response()->json(['error' => 'Invalid channel'], 403);
        }

        $a = $m[1];
        $b = $m[2];

        if ($myCarnet !== $a && $myCarnet !== $b) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Generar la firma HMAC que Reverb/Pusher espera
        $secret    = config('broadcasting.connections.reverb.secret');
        $signature = hash_hmac('sha256', $socketId . ':' . $channelName, $secret);
        $auth      = config('broadcasting.connections.reverb.key') . ':' . $signature;

        return response()->json(['auth' => $auth]);
    }
}
