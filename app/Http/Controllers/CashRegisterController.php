<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\CashCut;
use App\Models\Sale;
use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Mail\CashCutReportMail;
use Illuminate\Support\Facades\Mail;

class CashRegisterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $registers = CashRegister::whereHas('branch', function ($query) use ($user) {
            $query->where('business_id', $user->business_id);
        })
        ->with(['openedBy', 'movements', 'cuts' => function($q) {
            $q->latest()->limit(5); // Trae los últimos 5 cortes para historial
        }])
        ->latest()
        ->get();

        return Inertia::render('CashRegisters/Index', [
            'registers' => $registers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        CashRegister::create([
            'branch_id' => $user->branch_id ?? 1,
            'opened_by' => $user->id, // <--- Pasamos el ID del usuario creador
            'status'    => 'closed',
        ]);

        return redirect()->back()->with('message', 'Caja creada con éxito');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // Abrir turno
    public function openShift(Request $request, CashRegister $cashRegister)
    {
        $validated = $request->validate([
            'opening_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $cashRegister->update([
            'opened_by'      => Auth::id(),
            'closed_by'      => null,
            'opened_at'      => now(), // <--- Guarda la hora exacta de apertura
            'closed_at'      => null,
            'opening_amount' => $validated['opening_amount'],
            'closing_amount' => 0,
            'status'         => 'open',
        ]);

        return redirect()->back();
    }

    // Registrar Ingreso / Egreso de efectivo
    public function storeMovement(Request $request, CashRegister $cashRegister)
    {
        $validated = $request->validate([
            'type'        => ['required', 'in:income,expense'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $cashRegister->movements()->create([
            'user_id'     => Auth::id(),
            'type'        => $validated['type'],
            'amount'      => $validated['amount'],
            'description' => $validated['description'],
        ]);

        return redirect()->back();
    }

    // Cerrar turno y generar Corte de Caja (CashCut)
    public function closeShift(Request $request, CashRegister $cashRegister)
    {
        $validated = $request->validate([
            'real_cash'     => ['required', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:1000'],
            'email_to_send' => ['nullable', 'email'],
        ]);

        // 1. Obtener el último corte de esta caja para saber desde dónde contar
        $lastCut = CashCut::where('cash_register_id', $cashRegister->id)
            ->latest()
            ->first();

        // 2. Traer las ventas de esta caja creadas después del último corte (o todas si es el primero)
        $salesQuery = Sale::where('cash_register_id', $cashRegister->id)
            ->where('status', 'completed');

        if ($lastCut) {
            $salesQuery->where('id', '>', $lastCut->id); // O por created_at si prefieres, pero por ID evita líos de Zona Horaria
        }

        $sales = $salesQuery->get();

        // 3. Filtrar montos (Asegúrate que en la BD el valor sea 'cash' o 'efectivo')
        $cashSales     = $sales->whereIn('payment_method', ['cash', 'efectivo'])->sum('paid');
        $cardSales     = $sales->whereIn('payment_method', ['card', 'tarjeta'])->sum('paid');
        $transferSales = $sales->whereIn('payment_method', ['transfer', 'transferencia'])->sum('paid');

        // Si no usas la columna 'paid', cámbialo por 'total'
        $totalSales = $cashSales + $cardSales + $transferSales;

        // 4. Movimientos de la sesión
        $movementsQuery = CashMovement::where('cash_register_id', $cashRegister->id);
        if ($lastCut) {
            $movementsQuery->where('created_at', '>', $lastCut->created_at);
        }
        $movements = $movementsQuery->get();

        $incomes  = $movements->where('type', 'income')->sum('amount');
        $expenses = $movements->where('type', 'expense')->sum('amount');

        // 5. Cálculos Finales
        $expectedCash     = $cashRegister->opening_amount + $cashSales + $incomes - $expenses;
        $realCash         = $validated['real_cash'];
        $differenceAmount = $realCash - $expectedCash;

        // 6. Guardar Corte
        $cashCut = CashCut::create([
            'cash_register_id'  => $cashRegister->id,
            'user_id'           => Auth::id(),
            'opening_amount'    => $cashRegister->opening_amount,
            'total_sales'       => $totalSales,
            'cash_sales'        => $cashSales,
            'card_sales'        => $cardSales,
            'transfer_sales'    => $transferSales,
            'total_expenses'    => $expenses,
            'total_incomes'     => $incomes,
            'expected_cash'     => $expectedCash,
            'real_cash'         => $realCash,
            'difference_amount' => $differenceAmount,
            'notes'             => $validated['notes'] ?? null,
        ]);

        // Enviar Email y cerrar la caja
        $recipientEmail = $validated['email_to_send'] ?? Auth::user()->email;
        Mail::to($recipientEmail)->queue(new CashCutReportMail($cashCut));

        $cashRegister->update([
            'status'         => 'closed',
            'closing_amount' => $realCash,
            'closed_by'      => Auth::id(),
            'closed_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Corte de caja realizado con éxito.');
    }
}
