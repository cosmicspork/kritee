<?php

namespace App\Console\Commands;

use App\Actions\Invoice\MarkInvoicesOverdue;
use App\Actions\Invoice\MarkInvoicesOverdueInput;
use App\Console\Commands\Concerns\ResolvesActor;
use Illuminate\Console\Command;

class MarkInvoicesOverdueCommand extends Command
{
    use ResolvesActor;

    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Move issued invoices past their due date to overdue.';

    public function handle(): int
    {
        $this->resolveActor();

        $result = app(MarkInvoicesOverdue::class)->execute(new MarkInvoicesOverdueInput);

        if (! $result->success) {
            foreach ($result->errors as $message) {
                $this->error(is_string($message) ? $message : (string) json_encode($message));
            }

            return 1;
        }

        $this->info(sprintf('Marked %d invoice(s) overdue.', count($result->data)));

        return 0;
    }
}
