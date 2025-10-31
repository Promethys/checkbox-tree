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
