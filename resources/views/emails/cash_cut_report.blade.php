@component('mail::message')
# Corte de Caja #{{ $cashCut->id }}

**Caja:** Terminal #{{ $cashCut->cash_register_id }}
**Atendido por:** {{ $cashCut->user->name ?? 'N/A' }}
**Fecha:** {{ $cashCut->created_at->format('d/m/Y H:i A') }}

---

### Resumen de Ventas
- **Ventas en Efectivo:** ${{ number_format($cashCut->cash_sales, 2) }}
- **Ventas con Tarjeta:** ${{ number_format($cashCut->card_sales, 2) }}
- **Transferencias:** ${{ number_format($cashCut->transfer_sales, 2) }}
- **Total Ventas:** ${{ number_format($cashCut->total_sales, 2) }}

### Control de Efectivo
- **Fondo Inicial:** ${{ number_format($cashCut->opening_amount, 2) }}
- **Ingresos / Entradas:** ${{ number_format($cashCut->total_incomes, 2) }}
- **Egresos / Retiros:** ${{ number_format($cashCut->total_expenses, 2) }}
- **Efectivo Esperado:** ${{ number_format($cashCut->expected_cash, 2) }}
- **Efectivo Real (Contado):** ${{ number_format($cashCut->real_cash, 2) }}

@if($cashCut->difference_amount != 0)
**Diferencia:**
<span style="color: {{ $cashCut->difference_amount < 0 ? 'red' : 'green' }}; font-weight: bold;">
    ${{ number_format($cashCut->difference_amount, 2) }} ({{ $cashCut->difference_amount < 0 ? 'Faltante' : 'Sobrante' }})
</span>
@else
**Diferencia:** $0.00 (Cuadre perfecto)
@endif

@if($cashCut->notes)
**Observaciones:** {{ $cashCut->notes }}
@endif

Gracias,<br>
{{ config('app.name') }}
@endcomponent
