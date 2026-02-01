<?php

namespace Promethys\CheckboxTree\Tests\Feature\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Promethys\CheckboxTree\CheckboxTree;

class TestFormComponent extends Component implements HasForms
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
                        'user_management' => [
                            'label' => 'User Management',
                            'children' => [
                                'create_users' => 'Create Users',
                                'edit_users' => 'Edit Users',
                                'delete_users' => 'Delete Users',
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
