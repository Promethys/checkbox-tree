<?php

namespace Promethys\CheckboxTree\Tests\Feature\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Promethys\CheckboxTree\CheckboxTree;

class TestFormComponentMultipleGroups extends Component implements HasForms
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
                            ],
                        ],
                        'content_management' => [
                            'label' => 'Content Management',
                            'children' => [
                                'create_posts' => 'Create Posts',
                                'edit_posts' => 'Edit Posts',
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
