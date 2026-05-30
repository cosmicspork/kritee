<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Scaffolds an action, its input DTO, and a feature test in the shape the
 * architecture tests already accept, so the compliant path is the default one.
 */
class MakeActionCommand extends GeneratorCommand
{
    protected $name = 'make:action';

    protected $description = 'Create an action, its input DTO, and a feature test';

    protected $type = 'Action';

    public function handle(): ?bool
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->components->error('The name "'.$this->getNameInput().'" is reserved by PHP.');

            return false;
        }

        $action = $this->qualifyClass($this->getNameInput());

        if ($this->files->exists($this->getPath($action))) {
            $this->components->error($this->type.' '.$action.' already exists.');

            return false;
        }

        $this->writeAction($action);
        $this->writeInput($action);
        $this->writeTest($action);

        return null;
    }

    private function writeAction(string $action): void
    {
        $path = $this->getPath($action);

        $this->makeDirectory($path);
        $this->files->put($path, $this->buildClass($action));

        $this->components->info($this->type.' ['.$path.'] created successfully.');
    }

    private function writeInput(string $action): void
    {
        $input = $action.'Input';
        $path = $this->getPath($input);

        $this->makeDirectory($path);

        $stub = $this->files->get($this->resolveStubPath('stubs/action.input.stub'));
        $this->replaceNamespace($stub, $input);
        $stub = $this->replaceClass($stub, $input);

        $this->files->put($path, $stub);

        $this->components->info('Input ['.$path.'] created successfully.');
    }

    private function writeTest(string $action): void
    {
        $input = $action.'Input';
        $relative = Str::of($action)
            ->after($this->rootNamespace().'Actions\\')
            ->replace('\\', '/');

        $path = base_path('tests/Feature/Actions/'.$relative.'Test.php');

        $this->makeDirectory($path);

        $replacements = [
            '{{ namespacedActionClass }}' => $action,
            '{{ namespacedInputClass }}' => $input,
            '{{ actionClass }}' => class_basename($action),
            '{{ inputClass }}' => class_basename($input),
            '{{ actionTitle }}' => Str::of(class_basename($action))->headline()->lower()->toString(),
        ];

        $stub = strtr($this->files->get($this->resolveStubPath('stubs/action.test.stub')), $replacements);

        $this->files->put($path, $stub);

        $this->components->info('Test ['.$path.'] created successfully.');
    }

    protected function getStub(): string
    {
        return $this->resolveStubPath('stubs/action.stub');
    }

    private function resolveStubPath(string $stub): string
    {
        return $this->laravel->basePath(trim($stub, '/'));
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }

    /**
     * @return array<int, array{0: string, 1: int, 2: string}>
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The action name as Domain/ActionName'],
        ];
    }
}
