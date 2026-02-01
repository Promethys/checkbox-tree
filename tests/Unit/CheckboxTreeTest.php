<?php

use Promethys\CheckboxTree\CheckboxTree;

// ==========================================
// Helpers
// ==========================================

function getPrivateProperty(object $object, string $property): mixed
{
    $reflection = new ReflectionClass($object);
    $reflectionProperty = $reflection->getProperty($property);
    $reflectionProperty->setAccessible(true);

    return $reflectionProperty->getValue($object);
}

function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}

// ==========================================
// Instantiation & Configuration
// ==========================================

it('can create checkbox tree instance', function () {
    $field = CheckboxTree::make('permissions');

    expect($field)->toBeInstanceOf(CheckboxTree::class);
});

it('can enable hierarchical mode', function () {
    $field = CheckboxTree::make('permissions')
        ->hierarchical();

    expect(getPrivateProperty($field, 'isHierarchical'))->toBeTrue();
});

it('can set custom parent key', function () {
    $field = CheckboxTree::make('permissions')
        ->hierarchical('custom_parent_id');

    expect(getPrivateProperty($field, 'parentKey'))->toBe('custom_parent_id');
});

// ==========================================
// Collapsible Configuration
// ==========================================

it('is not collapsible by default', function () {
    $field = CheckboxTree::make('permissions');

    expect($field->isCollapsible())->toBeFalse();
});

it('can enable collapsible mode', function () {
    $field = CheckboxTree::make('permissions')
        ->collapsible();

    expect($field->isCollapsible())->toBeTrue();
});

it('is not default collapsed by default', function () {
    $field = CheckboxTree::make('permissions')
        ->collapsible();

    expect($field->isDefaultCollapsed())->toBeFalse();
});

it('can set default collapsed', function () {
    $field = CheckboxTree::make('permissions')
        ->collapsible(defaultCollapsed: true);

    expect($field->isCollapsible())->toBeTrue();
    expect($field->isDefaultCollapsed())->toBeTrue();
});

// ==========================================
// storeParentKeys Configuration
// ==========================================

it('does not store parent keys by default', function () {
    $field = CheckboxTree::make('permissions');

    expect($field->shouldStoreParentKeys())->toBeFalse();
});

it('can enable storeParentKeys', function () {
    $field = CheckboxTree::make('permissions')
        ->storeParentKeys();

    expect($field->shouldStoreParentKeys())->toBeTrue();
});

// ==========================================
// Hierarchical Options (nested structure)
// ==========================================

it('recognizes nested structure in options', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'user_management' => [
                'label' => 'User Management',
                'children' => [
                    'create_users' => 'Create Users',
                    'edit_users' => 'Edit Users',
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options)
        ->toHaveKey('user_management')
        ->and($options['user_management'])->toHaveKey('children');
});

it('flattens hierarchical keys', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'parent' => [
                'label' => 'Parent',
                'children' => [
                    'child1' => 'Child 1',
                    'child2' => [
                        'label' => 'Child 2',
                        'children' => [
                            'grandchild' => 'Grandchild',
                        ],
                    ],
                ],
            ],
        ]);

    $keys = invokePrivateMethod($field, 'flattenKeys', [
        $field->getHierarchicalOptions(),
    ]);

    expect($keys)
        ->toContain('parent')
        ->toContain('child1')
        ->toContain('child2')
        ->toContain('grandchild');
});

it('gets children keys for parent', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'user_management' => [
                'label' => 'User Management',
                'children' => [
                    'create_users' => 'Create Users',
                    'edit_users' => 'Edit Users',
                    'delete_users' => 'Delete Users',
                ],
            ],
        ]);

    $children = $field->getChildrenKeys('user_management');

    expect($children)
        ->toHaveCount(3)
        ->toContain('create_users')
        ->toContain('edit_users')
        ->toContain('delete_users');
});

it('handles multi level nesting', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'root' => [
                'label' => 'Root',
                'children' => [
                    'level1' => [
                        'label' => 'Level 1',
                        'children' => [
                            'level2' => [
                                'label' => 'Level 2',
                                'children' => [
                                    'level3' => 'Level 3',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    $children = $field->getChildrenKeys('root');

    expect($children)
        ->toContain('level1')
        ->toContain('level2')
        ->toContain('level3');
});

it('returns empty array for non existent parent', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'parent' => [
                'label' => 'Parent',
                'children' => [
                    'child' => 'Child',
                ],
            ],
        ]);

    expect($field->getChildrenKeys('non_existent'))->toBeEmpty();
});

it('returns empty array for leaf node getChildrenKeys', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'parent' => [
                'label' => 'Parent',
                'children' => [
                    'child' => 'Child',
                ],
            ],
        ]);

    expect($field->getChildrenKeys('child'))->toBeEmpty();
});

it('handles empty options', function () {
    $field = CheckboxTree::make('permissions')
        ->options([]);

    expect($field->getHierarchicalOptions())->toBeEmpty();
});

// ==========================================
// Indeterminate State (via private method)
// ==========================================

it('calculates indeterminate state correctly', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'user_management' => [
                'label' => 'User Management',
                'children' => [
                    'create_users' => 'Create Users',
                    'edit_users' => 'Edit Users',
                    'delete_users' => 'Delete Users',
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();
    $selected = ['create_users', 'edit_users']; // Some but not all

    $indeterminate = invokePrivateMethod($field, 'calculateIndeterminateStates', [$options, $selected]);

    expect($indeterminate)->toContain('user_management');
});

it('does not mark as indeterminate when all children selected', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'user_management' => [
                'label' => 'User Management',
                'children' => [
                    'create_users' => 'Create Users',
                    'edit_users' => 'Edit Users',
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();
    $selected = ['create_users', 'edit_users', 'user_management'];

    $indeterminate = invokePrivateMethod($field, 'calculateIndeterminateStates', [$options, $selected]);

    expect($indeterminate)->not->toContain('user_management');
});

it('does not mark as indeterminate when no children selected', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'user_management' => [
                'label' => 'User Management',
                'children' => [
                    'create_users' => 'Create Users',
                    'edit_users' => 'Edit Users',
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();
    $selected = [];

    $indeterminate = invokePrivateMethod($field, 'calculateIndeterminateStates', [$options, $selected]);

    expect($indeterminate)->not->toContain('user_management');
});

it('calculates indeterminate state for multi-level nesting', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'root' => [
                'label' => 'Root',
                'children' => [
                    'mid' => [
                        'label' => 'Mid',
                        'children' => [
                            'leaf1' => 'Leaf 1',
                            'leaf2' => 'Leaf 2',
                        ],
                    ],
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();
    // Only one grandchild selected
    $selected = ['leaf1'];

    $indeterminate = invokePrivateMethod($field, 'calculateIndeterminateStates', [$options, $selected]);

    expect($indeterminate)
        ->toContain('root')
        ->toContain('mid');
});

// ==========================================
// Parent Keys
// ==========================================

it('correctly identifies parent keys from nested options', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'user_management' => [
                'label' => 'User Management',
                'children' => [
                    'create_users' => 'Create Users',
                    'edit_users' => 'Edit Users',
                ],
            ],
            'standalone' => [
                'label' => 'Standalone',
                'children' => [],
            ],
        ]);

    $parentKeys = $field->getParentKeys();

    expect($parentKeys)
        ->toContain('user_management')
        ->not->toContain('create_users')
        ->not->toContain('edit_users');
});

it('identifies parent keys at multiple nesting levels', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'root' => [
                'label' => 'Root',
                'children' => [
                    'mid' => [
                        'label' => 'Mid',
                        'children' => [
                            'leaf' => 'Leaf',
                        ],
                    ],
                    'other_leaf' => 'Other Leaf',
                ],
            ],
        ]);

    $parentKeys = $field->getParentKeys();

    expect($parentKeys)
        ->toContain('root')
        ->toContain('mid')
        ->not->toContain('leaf')
        ->not->toContain('other_leaf');
});

// ==========================================
// Flat Options with parent_id
// ==========================================

it('builds tree from flat options', function () {
    $field = CheckboxTree::make('permissions')
        ->hierarchical('parent_id')
        ->options([
            'parent' => ['label' => 'Parent', 'parent_id' => null],
            'child1' => ['label' => 'Child 1', 'parent_id' => 'parent'],
            'child2' => ['label' => 'Child 2', 'parent_id' => 'parent'],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options)
        ->toHaveKey('parent')
        ->and($options['parent'])->toHaveKey('children')
        ->and($options['parent']['children'])->toHaveKey('child1')
        ->and($options['parent']['children'])->toHaveKey('child2');
});

it('builds multi level tree from flat options', function () {
    $field = CheckboxTree::make('categories')
        ->hierarchical('parent_id')
        ->options([
            'root' => ['label' => 'Root', 'parent_id' => null],
            'level1' => ['label' => 'Level 1', 'parent_id' => 'root'],
            'level2' => ['label' => 'Level 2', 'parent_id' => 'level1'],
            'level3' => ['label' => 'Level 3', 'parent_id' => 'level2'],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options)
        ->toHaveKey('root')
        ->and($options['root']['children'])->toHaveKey('level1')
        ->and($options['root']['children']['level1']['children'])->toHaveKey('level2')
        ->and($options['root']['children']['level1']['children']['level2']['children'])->toHaveKey('level3');
});

it('handles multiple root items in flat options', function () {
    $field = CheckboxTree::make('permissions')
        ->hierarchical('parent_id')
        ->options([
            'users' => ['label' => 'User Management', 'parent_id' => null],
            'create_users' => ['label' => 'Create Users', 'parent_id' => 'users'],
            'posts' => ['label' => 'Post Management', 'parent_id' => null],
            'create_posts' => ['label' => 'Create Posts', 'parent_id' => 'posts'],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options)
        ->toHaveKey('users')
        ->toHaveKey('posts')
        ->and($options['users']['children'])->toHaveKey('create_users')
        ->and($options['posts']['children'])->toHaveKey('create_posts');
});

it('uses key as label when no label field', function () {
    $field = CheckboxTree::make('items')
        ->hierarchical('parent_id')
        ->options([
            'parent_item' => ['parent_id' => null],
            'child_item' => ['parent_id' => 'parent_item'],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options['parent_item']['label'])->toBe('parent_item');
    expect($options['parent_item']['children']['child_item']['label'])->toBe('child_item');
});

it('handles items without children in flat format', function () {
    $field = CheckboxTree::make('items')
        ->hierarchical('parent_id')
        ->options([
            'standalone' => ['label' => 'Standalone Item', 'parent_id' => null],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options)
        ->toHaveKey('standalone')
        ->and($options['standalone'])->not->toHaveKey('children');
});

it('correctly identifies parent keys from flat options', function () {
    $field = CheckboxTree::make('permissions')
        ->hierarchical('parent_id')
        ->options([
            'parent1' => ['label' => 'Parent 1', 'parent_id' => null],
            'child1' => ['label' => 'Child 1', 'parent_id' => 'parent1'],
            'parent2' => ['label' => 'Parent 2', 'parent_id' => null],
        ]);

    $parentKeys = $field->getParentKeys();

    expect($parentKeys)
        ->toContain('parent1')
        ->not->toContain('child1')
        ->not->toContain('parent2'); // No children = not a parent
});

it('calculates children keys from flat options', function () {
    $field = CheckboxTree::make('categories')
        ->hierarchical('parent_id')
        ->options([
            'root' => ['label' => 'Root', 'parent_id' => null],
            'child1' => ['label' => 'Child 1', 'parent_id' => 'root'],
            'child2' => ['label' => 'Child 2', 'parent_id' => 'root'],
            'grandchild' => ['label' => 'Grandchild', 'parent_id' => 'child1'],
        ]);

    $children = $field->getChildrenKeys('root');

    expect($children)
        ->toContain('child1')
        ->toContain('child2')
        ->toContain('grandchild');
});

it('handles mixed nested and flat options', function () {
    $field = CheckboxTree::make('permissions')
        ->hierarchical('parent_id')
        ->options([
            'users' => ['label' => 'User Management', 'parent_id' => null],
            'create_users' => ['label' => 'Create Users', 'parent_id' => 'users'],
            'posts' => [
                'label' => 'Post Management',
                'children' => [
                    'create_posts' => 'Create Posts',
                    'edit_posts' => 'Edit Posts',
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();

    // Nested items preserved
    expect($options)
        ->toHaveKey('posts')
        ->and($options['posts']['children'])->toHaveKey('create_posts');

    // Flat items converted to tree
    expect($options)
        ->toHaveKey('users')
        ->and($options['users']['children'])->toHaveKey('create_users');
});

it('handles custom parent key name', function () {
    $field = CheckboxTree::make('categories')
        ->hierarchical('category_parent')
        ->options([
            'electronics' => ['label' => 'Electronics', 'category_parent' => null],
            'phones' => ['label' => 'Phones', 'category_parent' => 'electronics'],
            'laptops' => ['label' => 'Laptops', 'category_parent' => 'electronics'],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options)
        ->toHaveKey('electronics')
        ->and($options['electronics']['children'])->toHaveKey('phones')
        ->and($options['electronics']['children'])->toHaveKey('laptops');
});

it('preserves label priority over other fields', function () {
    $field = CheckboxTree::make('categories')
        ->hierarchical('parent_id')
        ->options([
            'item' => [
                'label' => 'Primary Label',
                'name' => 'Secondary Name',
                'title' => 'Tertiary Title',
                'parent_id' => null,
            ],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options['item']['label'])->toBe('Primary Label');
});

// ==========================================
// Relationship Support
// ==========================================

it('can set relationship name', function () {
    $field = CheckboxTree::make('permissions')
        ->relationship('permissions', 'name');

    expect($field->getRelationshipName())->toBe('permissions');
});

it('uses field name as relationship name when not specified', function () {
    $field = CheckboxTree::make('permissions')
        ->relationship(titleAttribute: 'name');

    expect($field->getRelationshipName())->toBe('permissions');
});

it('enables hierarchical mode when relationship is set', function () {
    $field = CheckboxTree::make('permissions')
        ->relationship('permissions', 'name');

    expect(getPrivateProperty($field, 'isHierarchical'))->toBeTrue();
});

it('can set relationship title attribute', function () {
    $field = CheckboxTree::make('permissions')
        ->relationship('permissions', 'display_name');

    expect($field->getRelationshipTitleAttribute())->toBe('display_name');
});

it('returns null relationship when not set', function () {
    $field = CheckboxTree::make('permissions');

    expect($field->getRelationshipName())->toBeNull();
});

it('can chain relationship with hierarchical', function () {
    $field = CheckboxTree::make('permissions')
        ->relationship('permissions', 'name')
        ->hierarchical('category_id');

    expect($field->getRelationshipName())->toBe('permissions');
    expect(getPrivateProperty($field, 'parentKey'))->toBe('category_id');
});

it('can chain relationship with other options', function () {
    $field = CheckboxTree::make('permissions')
        ->relationship('permissions', 'name')
        ->searchable()
        ->collapsible()
        ->bulkToggleable()
        ->storeParentKeys();

    expect($field->getRelationshipName())->toBe('permissions');
    expect($field->isSearchable())->toBeTrue();
    expect($field->isCollapsible())->toBeTrue();
    expect($field->isBulkToggleable())->toBeTrue();
    expect($field->shouldStoreParentKeys())->toBeTrue();
});

// ==========================================
// Description Support
// ==========================================

it('preserves description in hierarchical options', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'admin' => [
                'label' => 'Administrator',
                'description' => 'Full system access',
                'children' => [
                    'manage_users' => [
                        'label' => 'Manage Users',
                        'description' => 'Create, edit, delete users',
                    ],
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options['admin']['description'])->toBe('Full system access');
    expect($options['admin']['children']['manage_users']['description'])->toBe('Create, edit, delete users');
});

it('handles options without description', function () {
    $field = CheckboxTree::make('permissions')
        ->options([
            'admin' => [
                'label' => 'Administrator',
                'children' => [
                    'manage_users' => 'Manage Users',
                ],
            ],
        ]);

    $options = $field->getHierarchicalOptions();

    expect($options['admin'])->not->toHaveKey('description');
});
