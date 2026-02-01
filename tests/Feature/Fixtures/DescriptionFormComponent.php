<?php

namespace Promethys\CheckboxTree\Tests\Feature\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Promethys\CheckboxTree\CheckboxTree;

class DescriptionFormComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                CheckboxTree::make('permissions')
                    ->label('Permissions')
                    ->options([
                        'admin' => [
                            'label' => 'Administrator',
                            'description' => 'Full system access',
                            'children' => [
                                'manage_users' => [
                                    'label' => 'Manage Users',
                                    'description' => 'Create, edit, and delete users',
                                ],
                                'manage_settings' => 'Manage Settings',
                            ],
                        ],
                    ]),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return <<<'BLADE'
        <div>
            <form wire:submit.prevent="submit">
                {{ $this->form }}
            </form>
        </div>
        BLADE;
    }
}
