<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\OrganizationRole;
use App\Mail\InvoiceOverdueMail;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\OfficeNotification;
use App\Models\OrganizationUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    protected $signature = 'legal:send-invoice-reminders';

    protected $description = 'Šalje e-mail opomene za dospjele račune';

    /** @var list<int> */
    private array $offsets = [1, 7, 14];

    public function handle(): int
    {
        $sent = 0;

        Invoice::query()
            ->with(['party', 'matter.organization'])
            ->where('status', '!=', InvoiceStatus::Paid)
            ->whereDate('due_date', '<', today())
            ->orderBy('id')
            ->each(function (Invoice $invoice) use (&$sent): void {
                $invoice->refreshPaymentStatus();
                if ($invoice->status === InvoiceStatus::Paid) {
                    return;
                }

                $pending = [];
                foreach ($this->offsets as $offset) {
                    if ($invoice->due_date->copy()->addDays($offset)->gt(today())) {
                        continue;
                    }
                    if (InvoiceReminder::query()->where('invoice_id', $invoice->id)->where('days_after_due', $offset)->exists()) {
                        continue;
                    }
                    $pending[] = $offset;
                }

                if ($pending === []) {
                    return;
                }

                $email = $invoice->party?->email;
                if ($email === null || $email === '') {
                    return;
                }

                $offset = max($pending);
                Mail::to($email)->send(new InvoiceOverdueMail($invoice, $offset));
                foreach ($pending as $day) {
                    InvoiceReminder::query()->create([
                        'invoice_id' => $invoice->id,
                        'days_after_due' => $day,
                        'sent_at' => now(),
                    ]);
                }
                $this->notifyOffice($invoice, $offset);
                $sent++;
            });

        $this->info('Poslano opomena: '.$sent);

        return self::SUCCESS;
    }

    private function notifyOffice(Invoice $invoice, int $offset): void
    {
        $members = OrganizationUser::query()
            ->where('organization_id', $invoice->organization_id)
            ->whereIn('role', [OrganizationRole::Owner, OrganizationRole::Secretary])
            ->get();

        foreach ($members as $member) {
            OfficeNotification::query()->create([
                'organization_id' => $invoice->organization_id,
                'user_id' => $member->user_id,
                'title' => 'Opomena za račun '.$invoice->number,
                'body' => 'Račun je dospio prije '.$offset.' dana. Opomena je poslana kupcu.',
            ]);
        }
    }
}
