<?php

namespace Promethys\CheckboxTree\Tests\Unit;

use Promethys\CheckboxTree\CheckboxTree;
use Promethys\CheckboxTree\Tests\TestCase;

class CheckboxTreeTest extends TestCase
{
    /** @test */
    public function it_can_create_checkbox_tree_instance()
    {
        $field = CheckboxTree::make('permissions');

        $this->assertInstanceOf(CheckboxTree::class, $field);
    }

    /** @test */
    public function it_can_enable_hierarchical_mode()
    {
        $field = CheckboxTree::make('permissions')
            ->hierarchical();

        $this->assertTrue($this->getPrivateProperty($field, 'isHierarchical'));
    }

    /** @test */
    public function it_can_set_custom_parent_key()
    {
        $field = CheckboxTree::make('permissions')
            ->hierarchical('custom_parent_id');

        $this->assertEquals('custom_parent_id', $this->getPrivateProperty($field, 'parentKey'));
    }

    /** @test */
    public function it_recognizes_nested_structure_in_options()
    {
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

        $this->assertArrayHasKey('user_management', $options);
        $this->assertArrayHasKey('children', $options['user_management']);
    }

    /** @test */
    public function it_builds_tree_from_flat_options()
    {
        $field = CheckboxTree::make('permissions')
            ->hierarchical('parent_id')
            ->options([
                'parent' => ['label' => 'Parent', 'parent_id' => null],
                'child1' => ['label' => 'Child 1', 'parent_id' => 'parent'],
                'child2' => ['label' => 'Child 2', 'parent_id' => 'parent'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertArrayHasKey('parent', $options);
        $this->assertArrayHasKey('children', $options['parent']);
        $this->assertArrayHasKey('child1', $options['parent']['children']);
        $this->assertArrayHasKey('child2', $options['parent']['children']);
    }

    /** @test */
    public function it_flattens_hierarchical_keys()
    {
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

        $keys = $this->invokePrivateMethod($field, 'flattenKeys', [
            $field->getHierarchicalOptions(),
        ]);

        $this->assertContains('parent', $keys);
        $this->assertContains('child1', $keys);
        $this->assertContains('child2', $keys);
        $this->assertContains('grandchild', $keys);
    }

    /** @test */
    public function it_gets_children_keys_for_parent()
    {
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

        $this->assertCount(3, $children);
        $this->assertContains('create_users', $children);
        $this->assertContains('edit_users', $children);
        $this->assertContains('delete_users', $children);
    }

    /** @test */
    public function it_calculates_indeterminate_state_correctly()
    {
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
            ])
            ->default(['create_users', 'edit_users']); // Only some children selected

        $field->state(['create_users', 'edit_users']);
        $indeterminate = $field->getIndeterminateItems();

        $this->assertContains('user_management', $indeterminate);
    }

    /** @test */
    public function it_does_not_mark_as_indeterminate_when_all_children_selected()
    {
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

        $field->state(['create_users', 'edit_users', 'user_management']);
        $indeterminate = $field->getIndeterminateItems();

        $this->assertNotContains('user_management', $indeterminate);
    }

    /** @test */
    public function it_does_not_mark_as_indeterminate_when_no_children_selected()
    {
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

        $field->state([]);
        $indeterminate = $field->getIndeterminateItems();

        $this->assertNotContains('user_management', $indeterminate);
    }

    /** @test */
    public function it_handles_multi_level_nesting()
    {
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

        $this->assertContains('level1', $children);
        $this->assertContains('level2', $children);
        $this->assertContains('level3', $children);
    }

    /** @test */
    public function it_returns_empty_array_for_non_existent_parent()
    {
        $field = CheckboxTree::make('permissions')
            ->options([
                'parent' => [
                    'label' => 'Parent',
                    'children' => [
                        'child' => 'Child',
                    ],
                ],
            ]);

        $children = $field->getChildrenKeys('non_existent');

        $this->assertEmpty($children);
    }

    /** @test */
    public function it_ensures_state_is_always_array()
    {
        $field = CheckboxTree::make('permissions');

        $state = $field->getState();

        $this->assertIsArray($state);
    }

    // ==========================================
    // Flat Options with parent_id Tests
    // ==========================================

    /** @test */
    public function it_builds_multi_level_tree_from_flat_options()
    {
        $field = CheckboxTree::make('categories')
            ->hierarchical('parent_id')
            ->options([
                'root' => ['label' => 'Root', 'parent_id' => null],
                'level1' => ['label' => 'Level 1', 'parent_id' => 'root'],
                'level2' => ['label' => 'Level 2', 'parent_id' => 'level1'],
                'level3' => ['label' => 'Level 3', 'parent_id' => 'level2'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertArrayHasKey('root', $options);
        $this->assertArrayHasKey('level1', $options['root']['children']);
        $this->assertArrayHasKey('level2', $options['root']['children']['level1']['children']);
        $this->assertArrayHasKey('level3', $options['root']['children']['level1']['children']['level2']['children']);
    }

    /** @test */
    public function it_handles_multiple_root_items_in_flat_options()
    {
        $field = CheckboxTree::make('permissions')
            ->hierarchical('parent_id')
            ->options([
                'users' => ['label' => 'User Management', 'parent_id' => null],
                'create_users' => ['label' => 'Create Users', 'parent_id' => 'users'],
                'posts' => ['label' => 'Post Management', 'parent_id' => null],
                'create_posts' => ['label' => 'Create Posts', 'parent_id' => 'posts'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertArrayHasKey('users', $options);
        $this->assertArrayHasKey('posts', $options);
        $this->assertArrayHasKey('create_users', $options['users']['children']);
        $this->assertArrayHasKey('create_posts', $options['posts']['children']);
    }

    /** @test */
    public function it_supports_name_field_as_label_fallback()
    {
        $field = CheckboxTree::make('categories')
            ->hierarchical('parent_id')
            ->options([
                'cat1' => ['name' => 'Category One', 'parent_id' => null],
                'cat2' => ['name' => 'Category Two', 'parent_id' => 'cat1'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertEquals('Category One', $options['cat1']['label']);
        $this->assertEquals('Category Two', $options['cat1']['children']['cat2']['label']);
    }

    /** @test */
    public function it_supports_title_field_as_label_fallback()
    {
        $field = CheckboxTree::make('categories')
            ->hierarchical('parent_id')
            ->options([
                'cat1' => ['title' => 'Category One', 'parent_id' => null],
                'cat2' => ['title' => 'Category Two', 'parent_id' => 'cat1'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertEquals('Category One', $options['cat1']['label']);
        $this->assertEquals('Category Two', $options['cat1']['children']['cat2']['label']);
    }

    /** @test */
    public function it_uses_key_as_label_when_no_label_field()
    {
        $field = CheckboxTree::make('items')
            ->hierarchical('parent_id')
            ->options([
                'parent_item' => ['parent_id' => null],
                'child_item' => ['parent_id' => 'parent_item'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertEquals('parent_item', $options['parent_item']['label']);
        $this->assertEquals('child_item', $options['parent_item']['children']['child_item']['label']);
    }

    /** @test */
    public function it_handles_items_without_children_in_flat_format()
    {
        $field = CheckboxTree::make('items')
            ->hierarchical('parent_id')
            ->options([
                'standalone' => ['label' => 'Standalone Item', 'parent_id' => null],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertArrayHasKey('standalone', $options);
        $this->assertArrayNotHasKey('children', $options['standalone']);
    }

    /** @test */
    public function it_correctly_identifies_parent_keys_from_flat_options()
    {
        $field = CheckboxTree::make('permissions')
            ->hierarchical('parent_id')
            ->options([
                'parent1' => ['label' => 'Parent 1', 'parent_id' => null],
                'child1' => ['label' => 'Child 1', 'parent_id' => 'parent1'],
                'parent2' => ['label' => 'Parent 2', 'parent_id' => null],
            ]);

        $parentKeys = $field->getParentKeys();

        $this->assertContains('parent1', $parentKeys);
        $this->assertNotContains('child1', $parentKeys);
        $this->assertNotContains('parent2', $parentKeys); // No children
    }

    /** @test */
    public function it_calculates_children_keys_from_flat_options()
    {
        $field = CheckboxTree::make('categories')
            ->hierarchical('parent_id')
            ->options([
                'root' => ['label' => 'Root', 'parent_id' => null],
                'child1' => ['label' => 'Child 1', 'parent_id' => 'root'],
                'child2' => ['label' => 'Child 2', 'parent_id' => 'root'],
                'grandchild' => ['label' => 'Grandchild', 'parent_id' => 'child1'],
            ]);

        $children = $field->getChildrenKeys('root');

        $this->assertContains('child1', $children);
        $this->assertContains('child2', $children);
        $this->assertContains('grandchild', $children);
    }

    /** @test */
    public function it_handles_mixed_nested_and_flat_options()
    {
        $field = CheckboxTree::make('permissions')
            ->hierarchical('parent_id')
            ->options([
                // Flat format with parent_id
                'users' => ['label' => 'User Management', 'parent_id' => null],
                'create_users' => ['label' => 'Create Users', 'parent_id' => 'users'],

                // Already nested format
                'posts' => [
                    'label' => 'Post Management',
                    'children' => [
                        'create_posts' => 'Create Posts',
                        'edit_posts' => 'Edit Posts',
                    ],
                ],
            ]);

        $options = $field->getHierarchicalOptions();

        // Nested items should be preserved
        $this->assertArrayHasKey('posts', $options);
        $this->assertArrayHasKey('create_posts', $options['posts']['children']);

        // Flat items should be converted to tree
        $this->assertArrayHasKey('users', $options);
        $this->assertArrayHasKey('create_users', $options['users']['children']);
    }

    /** @test */
    public function it_handles_custom_parent_key_name()
    {
        $field = CheckboxTree::make('categories')
            ->hierarchical('category_parent')
            ->options([
                'electronics' => ['label' => 'Electronics', 'category_parent' => null],
                'phones' => ['label' => 'Phones', 'category_parent' => 'electronics'],
                'laptops' => ['label' => 'Laptops', 'category_parent' => 'electronics'],
            ]);

        $options = $field->getHierarchicalOptions();

        $this->assertArrayHasKey('electronics', $options);
        $this->assertArrayHasKey('phones', $options['electronics']['children']);
        $this->assertArrayHasKey('laptops', $options['electronics']['children']);
    }

    /** @test */
    public function it_preserves_label_priority_over_name_and_title()
    {
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

        $this->assertEquals('Primary Label', $options['item']['label']);
    }

    /**
     * Helper method to access private/protected properties
     */
    protected function getPrivateProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * Helper method to invoke private/protected methods
     */
    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
