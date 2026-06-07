<?php

namespace App\Console\Commands;

use App\Actions\Ledger\ImportLedger;
use App\Actions\Ledger\ImportLedgerInput;
use App\Actors\Contracts\Actor;
use App\Actors\UserActor;
use App\Enums\LedgerRowStatus;
use App\Models\User;
use App\Services\Ledger\PlannedRow;
use Illuminate\Console\Command;

class LedgerImportCommand extends Command
{
    protected $signature = 'ledger:import
        {path? : Path to a JSONL expenses file (omit to read stdin)}
        {--file= : Alternative way to pass the file path}
        {--user= : User id or email to record expenses as (defaults to the only user)}
        {--dry-run : Validate and report without writing}';

    protected $description = 'Import a JSONL expense ledger into kritee.';

    public function handle(): int
    {
        $path = $this->argument('path') ?? $this->option('file');

        $raw = $this->readInput(is_string($path) ? $path : null);
        if ($raw === null) {
            return 2;
        }

        $user = $this->resolveUser();
        if (! $user instanceof User) {
            return 2;
        }

        app()->instance(Actor::class, new UserActor($user));

        $result = app(ImportLedger::class)->execute(ImportLedgerInput::from([
            'user_id' => $user->getKey(),
            'rows' => $this->parseRows($raw),
            'dry_run' => (bool) $this->option('dry-run'),
        ]));

        if (! $result->success) {
            foreach ($result->errors as $message) {
                $this->error(is_string($message) ? $message : (string) json_encode($message));
            }

            return 1;
        }

        return $this->report($result->data);
    }

    private function readInput(?string $path): ?string
    {
        if ($path !== null) {
            if (! is_file($path) || ! is_readable($path)) {
                $this->error("Cannot read file: {$path}");

                return null;
            }

            return (string) file_get_contents($path);
        }

        if (stream_isatty(STDIN)) {
            $this->error('No input: pass a file path / --file, or pipe JSONL on stdin.');

            return null;
        }

        return (string) file_get_contents('php://stdin');
    }

    private function resolveUser(): ?User
    {
        $ref = $this->option('user');

        if (is_string($ref) && $ref !== '') {
            $user = User::query()->where('id', $ref)->orWhere('email', $ref)->first();

            if (! $user instanceof User) {
                $this->error("No user matches: {$ref}");

                return null;
            }

            return $user;
        }

        $count = User::query()->count();
        if ($count !== 1) {
            $this->error("Specify --user (found {$count} users).");

            return null;
        }

        return User::query()->firstOrFail();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseRows(string $raw): array
    {
        $rows = [];
        $lineNo = 0;

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $lineNo++;
            if (trim($line) === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (! is_array($decoded)) {
                $rows[] = ['_line' => $lineNo, '_error' => 'invalid JSON'];

                continue;
            }

            $decoded['_line'] = $lineNo;
            $rows[] = $decoded;
        }

        return $rows;
    }

    private function report(mixed $data): int
    {
        if (! is_array($data)) {
            return 1;
        }

        /** @var list<PlannedRow> $planned */
        $planned = $data['planned'] ?? [];
        /** @var list<array{line: int, message: string}> $writeErrors */
        $writeErrors = $data['write_errors'] ?? [];
        $imported = is_int($data['imported'] ?? null) ? $data['imported'] : 0;
        $dryRun = (bool) ($data['dry_run'] ?? false);

        $counts = [];
        $errors = [];
        $drift = [];
        foreach ($planned as $row) {
            $counts[$row->status->value] = ($counts[$row->status->value] ?? 0) + 1;
            if ($row->status === LedgerRowStatus::Error) {
                $errors[] = "  line {$row->line}: {$row->message}";
            }
            if ($row->drift) {
                $drift[] = "  line {$row->line}: emitted ref disagrees with recomputed ref";
            }
        }

        $this->line(($dryRun ? '[dry-run] ' : '').'Ledger import summary:');
        $this->line("  imported:           {$imported}");
        $this->line('  duplicates:         '.($counts[LedgerRowStatus::Duplicate->value] ?? 0));
        $this->line('  skipped (zero):     '.($counts[LedgerRowStatus::SkipZero->value] ?? 0));
        $this->line('  skipped (negative): '.($counts[LedgerRowStatus::SkipNegative->value] ?? 0));
        $this->line('  errors:             '.($counts[LedgerRowStatus::Error->value] ?? 0));

        foreach ($drift as $d) {
            $this->warn($d);
        }
        foreach ($errors as $e) {
            $this->error($e);
        }
        foreach ($writeErrors as $we) {
            $this->error("  line {$we['line']}: write failed: {$we['message']}");
        }

        return ($errors !== [] || $drift !== [] || $writeErrors !== []) ? 1 : 0;
    }
}
