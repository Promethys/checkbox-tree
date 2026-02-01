<?php

use Livewire\Livewire;
use Promethys\CheckboxTree\Tests\Feature\Fixtures\BulkToggleableFormComponent;
use Promethys\CheckboxTree\Tests\Feature\Fixtures\CollapsibleFormComponent;
use Promethys\CheckboxTree\Tests\Feature\Fixtures\DescriptionFormComponent;
use Promethys\CheckboxTree\Tests\Feature\Fixtures\SearchableFormComponent;
use Promethys\CheckboxTree\Tests\Feature\Fixtures\TestFormComponent;
use Promethys\CheckboxTree\Tests\Feature\Fixtures\TestFormComponentMultipleGroups;

// ==========================================
// Basic Rendering
// ==========================================

it('renders checkbox tree component', function () {
    Livewire::test(TestFormComponent::class)
        ->assertSee('Permissions')
        ->assertSee('User Management')
        ->assertSee('Create Users')
        ->assertSee('Edit Users')
        ->assertSee('Delete Users');
});

it('renders with multiple parent groups', function () {
    Livewire::test(TestFormComponentMultipleGroups::class)
        ->assertSee('User Management')
        ->assertSee('Content Management')
        ->assertSee('Create Users')
        ->assertSee('Create Posts');
});

// ==========================================
// State Management
// ==========================================

it('saves hierarchical selections as flat array', function () {
    $component = Livewire::test(TestFormComponent::class);

    $component->set('data.permissions', ['create_users', 'edit_users']);

    expect($component->get('data.permissions'))
        ->toBeArray()
        ->toContain('create_users')
        ->toContain('edit_users');
});

it('handles empty selection', function () {
    $component = Livewire::test(TestFormComponent::class);

    $component->set('data.permissions', []);

    expect($component->get('data.permissions'))->toBeEmpty();
});

it('preserves selections across updates', function () {
    $component = Livewire::test(TestFormComponent::class);

    $component->set('data.permissions', ['create_users', 'edit_users']);
    $component->call('$refresh');

    expect($component->get('data.permissions'))
        ->toContain('create_users')
        ->toContain('edit_users');
});

it('can deselect items', function () {
    $component = Livewire::test(TestFormComponent::class);

    $component->set('data.permissions', ['create_users', 'edit_users', 'delete_users']);
    $component->set('data.permissions', ['create_users', 'edit_users']);

    expect($component->get('data.permissions'))
        ->not->toContain('delete_users')
        ->toContain('create_users');
});

it('handles multiple parent groups selections', function () {
    $component = Livewire::test(TestFormComponentMultipleGroups::class);

    $component->set('data.permissions', [
        'create_users',
        'edit_users',
        'create_posts',
        'edit_posts',
    ]);

    $permissions = $component->get('data.permissions');

    expect($permissions)
        ->toContain('create_users')
        ->toContain('edit_users')
        ->toContain('create_posts')
        ->toContain('edit_posts');
});

// ==========================================
// Feature Rendering
// ==========================================

it('renders collapsible checkbox tree', function () {
    Livewire::test(CollapsibleFormComponent::class)
        ->assertSee('User Management')
        ->assertSee('Create Users')
        ->assertSeeHtml('toggleCollapsed');
});

it('renders searchable checkbox tree', function () {
    Livewire::test(SearchableFormComponent::class)
        ->assertSee('User Management')
        ->assertSeeHtml('type="search"')
        ->assertSeeHtml('Search permissions...');
});

it('renders bulk toggleable checkbox tree', function () {
    Livewire::test(BulkToggleableFormComponent::class)
        ->assertSee('User Management')
        ->assertSeeHtml('selectAll()')
        ->assertSeeHtml('deselectAll()');
});

it('renders descriptions', function () {
    Livewire::test(DescriptionFormComponent::class)
        ->assertSee('Administrator')
        ->assertSee('Full system access')
        ->assertSee('Manage Users')
        ->assertSee('Create, edit, and delete users');
});
