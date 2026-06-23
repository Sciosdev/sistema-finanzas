<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Reminder;
use App\Services\Finance\FinanceReminderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReminderController extends Controller
{
    public function __construct(
        private readonly FinanceReminderService $reminders,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status', 'pending');

        $query = Reminder::where('user_id', $user->id)
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('due_date')
            ->orderBy('id');

        if (in_array($status, ['pending', 'done', 'skipped'], true)) {
            $query->where('status', $status);
        }

        return view('finance.reminders.index', [
            'reminders' => $query->get(),
            'status' => $status,
            'types' => FinanceReminderService::TYPES,
            'vehicles' => FinanceReminderService::VEHICLES,
            'recurrences' => FinanceReminderService::RECURRENCES,
            'editReminder' => $request->query('edit')
                ? Reminder::where('user_id', $user->id)->find($request->query('edit'))
                : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Reminder::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]));

        return redirect()
            ->route('finance.reminders.index')
            ->with('success', 'Recordatorio agregado.');
    }

    public function update(Request $request, Reminder $reminder)
    {
        abort_unless($reminder->user_id === $request->user()->id, 403);

        $reminder->update(array_merge($this->validatedData($request), [
            'status' => $request->input('status', $reminder->status),
        ]));

        return redirect()
            ->route('finance.reminders.index', ['status' => $request->input('status', 'pending')])
            ->with('success', 'Recordatorio actualizado.');
    }

    public function complete(Request $request, Reminder $reminder)
    {
        abort_unless($reminder->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'completed_on' => ['nullable', 'date'],
        ]);

        $nextReminder = DB::transaction(fn () => $this->reminders->complete(
            $reminder,
            isset($data['completed_on']) ? Carbon::parse($data['completed_on']) : null
        ));

        return back()->with(
            'success',
            $nextReminder
                ? 'Recordatorio marcado como hecho y se generó el siguiente.'
                : 'Recordatorio marcado como hecho.'
        );
    }

    public function skip(Request $request, Reminder $reminder)
    {
        abort_unless($reminder->user_id === $request->user()->id, 403);

        $reminder->update(['status' => 'skipped']);

        return back()->with('success', 'Recordatorio omitido.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reminder_type' => ['required', Rule::in(array_keys(FinanceReminderService::TYPES))],
            'vehicle_type' => ['nullable', Rule::in(array_keys(FinanceReminderService::VEHICLES))],
            'due_date' => ['required', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'recurrence' => ['required', Rule::in(array_keys(FinanceReminderService::RECURRENCES))],
            'notify_days_before' => ['required', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['pending', 'done', 'skipped'])],
        ]);
    }
}
