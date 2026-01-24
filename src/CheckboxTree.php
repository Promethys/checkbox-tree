<?php

namespace Promethys\CheckboxTree;

use Filament\Forms\Components\CheckboxList;

class CheckboxTree extends CheckboxList
{
    protected string $view = 'checkbox-tree::checkbox-tree';

    protected bool $isHierarchical = false;

    protected string $parentKey = 'parent_id';

    protected array $hierarchicalOptions = [];

    protected bool | \Closure $isCollapsible = false;

    protected bool | \Closure $defaultCollapsed = false;

    protected bool | \Closure $storeParentKeys = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function (?array $state): ?array {
            if ($state === null) {
                return null;
            }

            if ($this->shouldStoreParentKeys()) {
                return $state;
            }

            // Filter out parent keys, keep only leaf nodes
            $parentKeys = $this->getParentKeys();

            return array_values(array_filter($state, fn ($key) => ! in_array($key, $parentKeys, true)));
        });
    }

    /**
     * Control whether parent keys are stored in the state.
     * Default is false (only leaf nodes are stored).
     */
    public function storeParentKeys(bool | \Closure $condition = true): static
    {
        $this->storeParentKeys = $condition;

        return $this;
    }

    /**
     * Check if parent keys should be stored in the state.
     */
    public function shouldStoreParentKeys(): bool
    {
        return (bool) $this->evaluate($this->storeParentKeys);
    }

    /**
     * Enable collapsible/collapsible parent nodes.
     */
    public function collapsible(bool | \Closure $condition = true, bool | \Closure $defaultCollapsed = false): static
    {
        $this->isCollapsible = $condition;
        $this->defaultCollapsed = $defaultCollapsed;

        return $this;
    }

    /**
     * Check if the tree is collapsible.
     */
    public function isCollapsible(): bool
    {
        return (bool) $this->evaluate($this->isCollapsible);
    }

    /**
     * Check if nodes should be expanded by default.
     */
    public function isDefaultCollapsed(): bool
    {
        return (bool) $this->evaluate($this->defaultCollapsed);
    }

    /**
     * Get all parent keys (items that have children).
     */
    public function getParentKeys(): array
    {
        return $this->collectParentKeys($this->getHierarchicalOptions());
    }

    /**
     * Recursively collect all parent keys.
     */
    protected function collectParentKeys(array $options): array
    {
        $parentKeys = [];

        foreach ($options as $key => $option) {
            if (is_array($option) && isset($option['children']) && ! empty($option['children'])) {
                $parentKeys[] = $key;
                $parentKeys = array_merge($parentKeys, $this->collectParentKeys($option['children']));
            }
        }

        return $parentKeys;
    }

    /**
     * Enable hierarchical mode and optionally specify the parent key field name.
     */
    public function hierarchical(string $parentKey = 'parent_id'): static
    {
        $this->isHierarchical = true;
        $this->parentKey = $parentKey;

        return $this;
    }

    /**
     * Get the hierarchical options structure for the view.
     */
    public function getHierarchicalOptions(): array
    {
        if (! empty($this->hierarchicalOptions)) {
            return $this->hierarchicalOptions;
        }

        $options = $this->getOptions();

        // If options are already in hierarchical format, use them directly
        if ($this->hasNestedStructure($options)) {
            $this->hierarchicalOptions = $options;

            return $options;
        }

        // Otherwise, build tree from flat options
        if ($this->isHierarchical) {
            $this->hierarchicalOptions = $this->buildTree($options);

            return $this->hierarchicalOptions;
        }

        return $options;
    }

    /**
     * Check if options array has nested 'children' structure.
     */
    protected function hasNestedStructure(array $options): bool
    {
        foreach ($options as $option) {
            if (is_array($option) && isset($option['children'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a tree structure from flat options array.
     */
    protected function buildTree(array $items, $parentId = null): array
    {
        $tree = [];

        foreach ($items as $key => $item) {
            // Handle both array items and simple key-value pairs
            $itemParentId = is_array($item) ? ($item[$this->parentKey] ?? null) : null;

            if ($itemParentId === $parentId) {
                $label = is_array($item) ? ($item['label'] ?? $item['name'] ?? $key) : $item;

                $node = [
                    'label' => $label,
                    'children' => $this->buildTree($items, $key),
                ];

                // Only add children key if there are actually children
                if (empty($node['children'])) {
                    unset($node['children']);
                }

                $tree[$key] = $node;
            }
        }

        return $tree;
    }

    /**
     * Get the state with hierarchical selection information.
     */
    public function getState(): mixed
    {
        $state = parent::getState();

        // Ensure we always return an array
        if (! is_array($state)) {
            $state = $state ? [$state] : [];
        }

        return $state;
    }

    /**
     * Calculate which items should be in indeterminate state.
     */
    public function getIndeterminateItems(): array
    {
        $selected = $this->getState() ?? [];
        $options = $this->getHierarchicalOptions();

        return $this->calculateIndeterminateStates($options, $selected);
    }

    /**
     * Recursively calculate indeterminate states for parent items.
     */
    protected function calculateIndeterminateStates(array $options, array $selected): array
    {
        $indeterminate = [];

        foreach ($options as $key => $option) {
            if (is_array($option) && isset($option['children'])) {
                $children = $this->flattenKeys($option['children']);
                $selectedChildren = array_intersect($children, $selected);

                // Parent is indeterminate if some (but not all) children are selected
                $childCount = count($children);
                $selectedCount = count($selectedChildren);

                if ($selectedCount > 0 && $selectedCount < $childCount) {
                    $indeterminate[] = $key;
                }

                // Recursively check children
                $childIndeterminate = $this->calculateIndeterminateStates($option['children'], $selected);
                $indeterminate = array_merge($indeterminate, $childIndeterminate);
            }
        }

        return $indeterminate;
    }

    /**
     * Flatten a hierarchical structure to get all keys.
     */
    protected function flattenKeys(array $options): array
    {
        $keys = [];

        foreach ($options as $key => $option) {
            $keys[] = $key;

            if (is_array($option) && isset($option['children'])) {
                $keys = array_merge($keys, $this->flattenKeys($option['children']));
            }
        }

        return $keys;
    }

    /**
     * Get all children keys for a given parent key.
     */
    public function getChildrenKeys(string $parentKey): array
    {
        $options = $this->getHierarchicalOptions();

        return $this->findChildrenKeys($options, $parentKey);
    }

    /**
     * Recursively find all children keys for a parent.
     */
    protected function findChildrenKeys(array $options, string $parentKey): array
    {
        foreach ($options as $key => $option) {
            if ($key === $parentKey && is_array($option) && isset($option['children'])) {
                return $this->flattenKeys($option['children']);
            }

            if (is_array($option) && isset($option['children'])) {
                $found = $this->findChildrenKeys($option['children'], $parentKey);
                if (! empty($found)) {
                    return $found;
                }
            }
        }

        return [];
    }
}
