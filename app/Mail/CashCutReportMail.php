<?php

namespace App\Mail;

use App\Models\CashCut;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CashCutReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cashCut; // Declarada como pública

    /**
     * Create a new message instance.
     */
    public function __construct(CashCut $cashCut)
    {
        // Cargar las relaciones necesarias para el informe
        $this->cashCut = $cashCut->load(['cashRegister', 'user']);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Reporte de Corte de Caja #{$this->cashCut->id} - " . now()->format('d/m/Y'))
                    ->markdown('emails.cash_cut_report')
                    ->with([
                        'cashCut' => $this->cashCut, // Pasamos la variable explícitamente a la vista
                    ]);
    }
}
