<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Account;
use App\Models\Finance\CardRefund;
use App\Models\Finance\CreditPurchase;
use App\Services\Finance\CardRefundService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class CardRefundController extends Controller
{
    public function __construct(private readonly CardRefundService $refunds) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'account_id' => [
                'required', 'integer',
                Rule::exists('finance_accounts', 'id')
                    ->where(fn ($query) => $query->where('user_id', $user->id)->where('is_active', true)),
            ],
            'received_on' => ['required', 'date_format:Y-m-d'],
            'period_month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:5000'],
            'idempotency_key' => ['required', 'uuid', 'max:80'],
            'reference_credit_purchase_id' => [
                'nullable', 'integer',
                Rule::exists('finance_credit_purchases', 'id')->where(fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('account_id', $request->input('account_id'))),
            ],
        ], [
            'notes.required' => 'Anota la referencia o evidencia de la devolución del banco.',
            'idempotency_key.required' => 'Recarga la página y vuelve a registrar la devolución.',
        ]);

        $account = Account::where('user_id', $user->id)->findOrFail($data['account_id']);
        $originalPurchase = empty($data['reference_credit_purchase_id']) ? null
            : CreditPurchase::where('user_id', $user->id)
                ->where('account_id', $account->id)
                ->findOrFail($data['reference_credit_purchase_id']);

        try {
            $this->refunds->create(
                $user,
                $account,
                Carbon::parse($data['received_on']),
                Carbon::createFromFormat('!Y-m', $data['period_month']),
                (float) $data['amount'],
                trim($data['description']),
                trim($data['notes']),
                $data['idempotency_key'],
                $originalPurchase,
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['card_refund' => $exception->getMessage()]);
        }

        return redirect()->to(route('finance.credits.index').'#card-refunds')
            ->with('success', 'Devolución de tarjeta registrada. Se redujo la deuda sin crear un movimiento de efectivo.');
    }

    public function destroy(Request $request, CardRefund $refund): RedirectResponse
    {
        abort_unless((int) $refund->user_id === (int) $request->user()->id, 403);

        try {
            $this->refunds->delete($refund);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['card_refund' => $exception->getMessage()]);
        }

        return redirect()->to(route('finance.credits.index').'#card-refunds')
            ->with('success', 'Devolución eliminada. Se restituyó el importe a las mensualidades correspondientes.');
    }
}
