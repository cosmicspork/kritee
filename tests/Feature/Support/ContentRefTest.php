<?php

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use App\Services\Support\ContentRef;

test('the recipe matches the documented vector', function () {
    // Fixed known-good vector pinning the content-hash recipe. The ref is a stable
    // dedup key, so this hash must not change unintentionally.
    $ref = app(ContentRef::class)->compute(['2020-01-01', 'Acme Cloud', 'alpha', '9.99']);

    expect($ref)->toBe('df72886ff5e39d2c29b0c76dabee5460908b58517e23a38670e40dd4c95a2731');
});

test('the recipe is deterministic and order-sensitive', function () {
    $a = app(ContentRef::class)->compute(['2020-01-01', 'Acme Cloud', 'alpha', '9.99']);
    $b = app(ContentRef::class)->compute(['2020-01-01', 'Acme Cloud', 'alpha', '9.99']);
    $c = app(ContentRef::class)->compute(['2020-01-01', 'alpha', 'Acme Cloud', '9.99']);

    expect($a)->toBe($b)->and($a)->not->toBe($c);
});

test('the trait fills ref on save from the model fields', function () {
    $project = Project::factory()->create(['slug' => 'alpha']);
    $expense = Expense::factory()->create([
        'user_id' => User::factory(),
        'project_id' => $project->getKey(),
        'vendor' => 'Acme Cloud',
        'amount' => '9.99',
        'incurred_on' => '2020-01-01',
    ]);

    expect($expense->ref)->toBe(
        app(ContentRef::class)->compute(['2020-01-01', 'Acme Cloud', 'alpha', '9.99'])
    );
});
