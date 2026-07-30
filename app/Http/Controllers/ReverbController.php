<?php

namespace App\Http\Controllers;

use App\Services\ReverbServerManager;
use Illuminate\Http\Request;

class ReverbController extends Controller
{
    protected $manager;

    public function __construct(ReverbServerManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Retorna o status online do Reverb.
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'online' => $this->manager->isOnline()
        ]);
    }

    /**
     * Inicia o Reverb em segundo plano.
     */
    public function start()
    {
        $this->manager->start();

        return response()->json([
            'success' => true,
            'message' => 'Comando de inicialização enviado.'
        ]);
    }

    /**
     * Para o Reverb.
     */
    public function stop()
    {
        $this->manager->stop();

        return response()->json([
            'success' => true,
            'message' => 'Comando de encerramento enviado.'
        ]);
    }
}
