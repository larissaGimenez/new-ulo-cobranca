<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Retorna a lista de notificações não lidas em formato JSON.
     */
    public function getUnread()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'created_at' => $notification->created_at->format('d/m/Y H:i')
                ];
            });

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * Limpa (deleta) todas as notificações pendentes do usuário logado.
     */
    public function clearAll()
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificações limpas com sucesso.'
        ]);
    }
}
