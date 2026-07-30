<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Exibe a tela de configurações do usuário.
     */
    public function index()
    {
        $user = auth()->user();
        
        $settings = $user->notification_settings ?? [
            'notify_receivable_created' => true,
            'notify_receivable_updated' => true,
            'notify_receivable_paid' => true,
            'notify_client_created' => true,
            'sound_enabled' => true,
        ];

        return view('pages.configuracoes.index', compact('settings'));
    }

    /**
     * Salva as preferências de notificação do usuário.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $settings = [
            'notify_receivable_created' => $request->has('notify_receivable_created'),
            'notify_receivable_updated' => $request->has('notify_receivable_updated'),
            'notify_receivable_paid' => $request->has('notify_receivable_paid'),
            'notify_client_created' => $request->has('notify_client_created'),
            'sound_enabled' => $request->has('sound_enabled'),
        ];

        $user->update([
            'notification_settings' => $settings
        ]);

        return redirect()->back()->with('success', 'Configurações salvas com sucesso!');
    }
}
