<?php

use App\Actions\Contracts\Action;
use App\Actions\Contracts\ActionInput;
use App\Actions\Contracts\ActionResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use Symfony\Component\Finder\SplFileInfo;

arch('the Action contract is an interface')
    ->expect(Action::class)
    ->toBeInterface();

arch('ActionInput is a laravel-data DTO')
    ->expect(ActionInput::class)
    ->toExtend(Data::class);

arch('ActionResult is final')
    ->expect(ActionResult::class)
    ->toBeFinal();

test('every action implements the Action contract', function (): void {
    $actions = collect(File::allFiles(app_path('Actions')))
        ->filter(fn (SplFileInfo $file): bool => Str::endsWith($file->getFilename(), '.php'))
        ->reject(fn (SplFileInfo $file): bool => Str::endsWith($file->getFilename(), 'Input.php'))
        ->reject(fn (SplFileInfo $file): bool => Str::contains(
            $file->getRelativePath(),
            ['Contracts', 'Concerns', 'Fortify'],
        ))
        ->map(fn (SplFileInfo $file): string => 'App\\Actions\\'.Str::of($file->getRelativePathname())
            ->before('.php')
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->toString())
        ->filter(fn (string $class): bool => class_exists($class))
        // Supporting laravel-data DTOs (e.g. a value object nested in an Input)
        // may live beside an action without being one.
        ->reject(fn (string $class): bool => is_subclass_of($class, Data::class));

    foreach ($actions as $class) {
        expect(is_subclass_of($class, Action::class))
            ->toBeTrue("{$class} must implement ".Action::class);
    }

    expect($actions->all())->toBeArray();
});

arch('classes extending the base input carry the Input suffix')
    ->expect('App\Actions')
    ->classes()
    ->extending(ActionInput::class)
    ->toHaveSuffix('Input');

test('every Input DTO extends the base ActionInput', function (): void {
    $inputDtos = collect(File::allFiles(app_path('Actions')))
        ->filter(fn (SplFileInfo $file): bool => Str::endsWith($file->getFilename(), 'Input.php'))
        ->reject(fn (SplFileInfo $file): bool => Str::contains(
            $file->getRelativePath(),
            ['Contracts', 'Fortify'],
        ))
        ->map(fn (SplFileInfo $file): string => 'App\\Actions\\'.Str::of($file->getRelativePathname())
            ->before('.php')
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->toString())
        ->filter(fn (string $class): bool => class_exists($class));

    foreach ($inputDtos as $class) {
        expect(is_subclass_of($class, ActionInput::class))
            ->toBeTrue("{$class} must extend ".ActionInput::class);
    }

    expect($inputDtos->all())->toBeArray();
});

arch('controllers do not depend on models')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Models');
