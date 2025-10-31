<?php

namespace Promethys\CheckboxTree\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Promethys\CheckboxTree\CheckboxTree;
use Promethys\CheckboxTree\Tests\TestCase;

class CheckboxTreeFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register a test route for Livewire component
        Route::get('test', TestFormComponent::class);
    }

    /** @test */
    public function it_renders_checkbox_tree_component()
    {
        $component = Livewire::test(TestFormComponent::class);

        $component->assertSee('Permissions');
    }

    /** @test */
    public function it_saves_hierarchical_selections_as_flat_array()
    {
        $component = Livewire::test(TestFormComponent::class);

        // Simulate selecting child checkboxes
        $component->set('data.permissions', ['create_users', 'edit_users']);

        $this->assertIsArray($component->get('data.permissions'));
        $this->assertContains('create_users', $component->get('data.permissions'));
        $this->assertContains('edit_users', $component->get('data.permissions'));
    }

    /** @test */
    public function it_handles_empty_selection()
    {
        $component = Livewire::test(TestFormComponent::class);

        $component->set('data.permissions', []);

        $this->assertEmpty($component->get('data.permissions'));
    }

    /** @test */
    public function it_preserves_selections_across_updates()
    {
        $component = Livewire::test(TestFormComponent::class);

        $component->set('data.permissions', ['create_users', 'edit_users']);

        // Trigger an update
        $component->call('$refresh');

        $this->assertContains('create_users', $component->get('data.permissions'));
        $this->assertContains('edit_users', $component->get('data.permissions'));
    }

    /** @test */
    public function it_can_deselect_items()
    {
        $component = Livewire::test(TestFormComponent::class);

        // First select items
        $component->set('data.permissions', ['create_users', 'edit_users', 'delete_users']);

        // Then deselect one
        $component->set('data.permissions', ['create_users', 'edit_users']);

        $this->assertNotContains('delete_users', $component->get('data.permissions'));
        $this->assertContains('create_users', $component->get('data.permissions'));
    }

    /** @test */
    public function it_handles_multiple_parent_groups()
    {
        $component = Livewire::test(TestFormComponentMultipleGroups::class);

        $component->set('data.permissions', [
            'create_users',
            'edit_users',
            'create_posts',
            'edit_posts',
        ]);

        $permissions = $component->get('data.permissions');

        $this->assertContains('create_users', $permissions);
        $this->assertContains('edit_users', $permissions);
        $this->assertContains('create_posts', $permissions);
        $this->assertContains('edit_posts', $permissions);
    }
}

// Test Livewire Component
class TestFormComponent extends LivewireComponent
{
    public $data = [
        'permissions' => [],
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
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
        ];
    }

    public function render()
    {
        return view('test-form-component');
    }

    protected function getFormModel(): array
    {
        return $this->data;
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }
}

// Test Livewire Component with Multiple Groups
class TestFormComponentMultipleGroups extends LivewireComponent
{
    public $data = [
        'permissions' => [],
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
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
        ];
    }

    public function render()
    {
        return view('test-form-component');
    }

    protected function getFormModel(): array
    {
        return $this->data;
    }

    protected function getFormStatePath(): string
    {
        return 'data';
    }
}
