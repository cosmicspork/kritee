<?php

namespace App\Services\Ledger;

use App\Enums\LedgerRowStatus;
use App\Models\Expense;
use App\Models\Project;
use App\Services\Support\ContentRef;

/**
 * Turns decoded JSONL rows into a per-row verdict without writing anything:
 * validates required fields, resolves the project slug to an existing project
 * (flagging unknowns rather than inventing them), drops zero/negative rows, and
 * computes the `ref` so duplicates — already in the table or repeated within the
 * same file — are caught before the action attempts a write.
 */
final class LedgerImportPlanner
{
    public function __construct(private readonly ContentRef $contentRef) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<PlannedRow>
     */
    public function plan(array $rows, int $userId): array
    {
        $projects = $this->projectsBySlug($rows);

        $prelim = [];
        foreach ($rows as $row) {
            $prelim[] = $this->classify($row, $userId, $projects);
        }

        $existing = $this->existingRefs($prelim);
        $seen = [];
        $planned = [];
        foreach ($prelim as $pr) {
            $isImport = $pr->status === LedgerRowStatus::Import && $pr->ref !== null;

            if ($isImport && (isset($existing[$pr->ref]) || isset($seen[$pr->ref]))) {
                $planned[] = new PlannedRow($pr->line, LedgerRowStatus::Duplicate, $pr->ref, message: 'already imported');

                continue;
            }

            if ($isImport) {
                $seen[$pr->ref] = true;
            }

            $planned[] = $pr;
        }

        return $planned;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, Project>  $projects
     */
    private function classify(array $row, int $userId, array $projects): PlannedRow
    {
        $line = is_int($row['_line'] ?? null) ? $row['_line'] : 0;

        if (isset($row['_error']) && is_string($row['_error'])) {
            return new PlannedRow($line, LedgerRowStatus::Error, message: $row['_error']);
        }

        $date = $row['date'] ?? null;
        $vendor = $row['vendor'] ?? null;
        $project = $row['project'] ?? null;
        $currency = $row['currency'] ?? null;
        $amountRaw = $row['amount'] ?? null;

        if (! is_string($date) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return new PlannedRow($line, LedgerRowStatus::Error, message: 'missing or invalid date');
        }
        if (! is_string($vendor) || trim($vendor) === '') {
            return new PlannedRow($line, LedgerRowStatus::Error, message: 'missing vendor');
        }
        if (! is_string($project) || trim($project) === '') {
            return new PlannedRow($line, LedgerRowStatus::Error, message: 'missing project');
        }
        if ($currency !== 'USD') {
            return new PlannedRow($line, LedgerRowStatus::Error, message: 'unsupported currency (expected USD)');
        }
        if (! is_numeric($amountRaw)) {
            return new PlannedRow($line, LedgerRowStatus::Error, message: 'missing or non-numeric amount');
        }

        $amount = (float) $amountRaw;
        if (abs($amount) < 0.005) {
            return new PlannedRow($line, LedgerRowStatus::SkipZero, message: 'zero amount');
        }
        if ($amount < 0) {
            return new PlannedRow($line, LedgerRowStatus::SkipNegative, message: 'negative amount');
        }

        $resolved = $projects[$project] ?? null;
        if (! $resolved instanceof Project) {
            return new PlannedRow($line, LedgerRowStatus::Error, message: "unknown project slug '{$project}'");
        }

        $amountString = number_format($amount, 2, '.', '');
        $ref = $this->contentRef->compute([$date, $vendor, $project, $amountString]);
        $drift = isset($row['ref']) && is_string($row['ref']) && $row['ref'] !== $ref;
        $note = isset($row['note']) && is_string($row['note']) ? $row['note'] : null;

        $attributes = [
            'user_id' => $userId,
            'amount' => $amountString,
            'incurred_on' => $date,
            'description' => $vendor,
            'vendor' => $vendor,
            'project_id' => $resolved->getKey(),
            'client_id' => $resolved->getAttribute('client_id'),
            'is_billable' => false,
            'notes' => $note,
            'idempotency_key' => $ref,
        ];

        return new PlannedRow($line, LedgerRowStatus::Import, $ref, $attributes, drift: $drift);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, Project>
     */
    private function projectsBySlug(array $rows): array
    {
        $slugs = [];
        foreach ($rows as $row) {
            if (isset($row['project']) && is_string($row['project'])) {
                $slugs[$row['project']] = true;
            }
        }

        if ($slugs === []) {
            return [];
        }

        /** @var array<string, Project> $bySlug */
        $bySlug = Project::query()
            ->whereIn('slug', array_keys($slugs))
            ->get()
            ->keyBy('slug')
            ->all();

        return $bySlug;
    }

    /**
     * @param  list<PlannedRow>  $prelim
     * @return array<string, true>
     */
    private function existingRefs(array $prelim): array
    {
        $refs = [];
        foreach ($prelim as $pr) {
            if ($pr->status === LedgerRowStatus::Import && $pr->ref !== null) {
                $refs[] = $pr->ref;
            }
        }

        if ($refs === []) {
            return [];
        }

        /** @var list<string> $found */
        $found = Expense::query()
            ->whereIn('ref', array_values(array_unique($refs)))
            ->pluck('ref')
            ->all();

        return array_fill_keys($found, true);
    }
}
