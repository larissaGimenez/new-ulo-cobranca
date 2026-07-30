<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Events\NotificationReceived;

class NotificationService
{
    /**
     * Envia uma notificação aos usuários conforme suas configurações de notificação.
     */
    public static function send(string $type, string $title, string $message): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $settings = $user->notification_settings ?? [
                'notify_receivable_created' => true,
                'notify_receivable_updated' => true,
                'notify_receivable_paid' => true,
                'notify_client_created' => true,
                'sound_enabled' => true,
            ];

            // Mapeamento dos tipos para as chaves de configuração
            $key = match ($type) {
                'receivable_created' => 'notify_receivable_created',
                'receivable_updated' => 'notify_receivable_updated',
                'receivable_paid' => 'notify_receivable_paid',
                'client_created' => 'notify_client_created',
                default => null,
            };

            // Se o tipo for mapeado e o usuário tiver desativado, pula
            if ($key && !($settings[$key] ?? false)) {
                continue;
            }

            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
            ]);

            // Dispara a transmissão em tempo real via WebSocket (Laravel Reverb)
            broadcast(new NotificationReceived($notification));
        }
    }
}
