<?php

namespace Promethys\CheckboxTree;

use Filament\Forms\Components\CheckboxList;

class CheckboxTree extends CheckboxList
{
    protected string $view = 'checkbox-tree::checkbox-tree';

    protected bool $isHierarchical = false;

    protected string $parentKey = 'parent_id';

    protected array $hierarchicalOptions = [];

    protected bool $isCollapsible = false;

    protected bool $defaultCollapsed = false;

    /**
     * Enable collapsible/collapsible parent nodes.
     */
    public function collapsible(bool $condition = true, bool $defaultCollapsed = false): static
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
        return $this->isCollapsible;
    }

    /**
     * Check if nodes should be expanded by default.
     */
    public function isDefaultCollapsed(): bool
    {
        return $this->defaultCollapsed;
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
     * Enable search functionality for the checkbox tree.
     */
    public function searchable(bool | string $condition = true): static
    {
        if (is_string($condition)) {
            $this->searchPrompt = $condition;
            $this->isSearchable = true;
        } else {
            $this->isSearchable = $condition;
        }

        return $this;
    }

    /**
     * Check if the checkbox tree is searchable.
     */
    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    /**
     * Get the search prompt/placeholder text.
     */
    public function getSearchPrompt(): string
    {
        return $this->searchPrompt ?? __('Search...');
    }

    /**
     * Enable collapsible/expandable parent nodes.
     */
    public function expandable(bool $condition = true, bool $defaultExpanded = false): static
    {
        $this->isExpandable = $condition;
        $this->defaultExpanded = $defaultExpanded;

        return $this;
    }

    /**
     * Check if the tree is expandable/collapsible.
     */
    public function isExpandable(): bool
    {
        return $this->isExpandable;
    }

    /**
     * Check if nodes should be expanded by default.
     */
    public function isDefaultExpanded(): bool
    {
        return $this->defaultExpanded;
    }

    /**
     * Enable bulk toggle (select all / deselect all) buttons.
     */
    public function bulkToggleable(bool $condition = true): static
    {
        $this->isBulkToggleable = $condition;

        return $this;
    }

    /**
     * Check if bulk toggle is enabled.
     */
    public function isBulkToggleable(): bool
    {
        return $this->isBulkToggleable;
    }

    /**
     * Override relationship method to support hierarchical relationships.
     */
    public function relationship(
        string $name,
        string $titleAttribute,
        ?callable $modifyQueryUsing = null,
    ): static {
        $this->relationshipTitleAttribute = $titleAttribute;
        $this->modifyRelationshipQueryUsing = $modifyQueryUsing;

        return parent::relationship($name, $titleAttribute, $modifyQueryUsing);
    }

    /**
     * Get options from relationship and build hierarchical structure if needed.
     */
    protected function getOptionsFromRelationship(): array
    {
        $relationship = $this->getRelationship();

        if (! $relationship) {
            return [];
        }

        $relationshipQuery = $relationship->getRelated()->query();

        if ($this->modifyRelationshipQueryUsing) {
            $relationshipQuery = $this->evaluate($this->modifyRelationshipQueryUsing, [
                'query' => $relationshipQuery,
            ]) ?? $relationshipQuery;
        }

        $records = $relationshipQuery->get();

        if (! $this->isHierarchical) {
            // Standard flat list
            return $this->convertRecordsToOptions($records);
        }

        // Build hierarchical structure
        return $this->buildTreeFromRecords($records);
    }

    /**
     * Convert records to flat options array.
     */
    protected function convertRecordsToOptions(Collection $records): array
    {
        $titleAttribute = $this->relationshipTitleAttribute;

        return $records->mapWithKeys(function (Model $record) use ($titleAttribute) {
            return [$record->getKey() => $record->getAttribute($titleAttribute)];
        })->toArray();
    }

    /**
     * Build hierarchical tree from eloquent records.
     */
    protected function buildTreeFromRecords(Collection $records): array
    {
        $titleAttribute = $this->relationshipTitleAttribute;
        $parentKey = $this->parentKey;

        // Convert records to array format for tree building
        $items = $records->mapWithKeys(function (Model $record) use ($titleAttribute, $parentKey) {
            return [
                $record->getKey() => [
                    'label' => $record->getAttribute($titleAttribute),
                    $parentKey => $record->getAttribute($parentKey),
                ],
            ];
        })->toArray();

        return $this->buildTree($items);
    }

    /**
     * Get the hierarchical options structure for the view.
     */
    public function getHierarchicalOptions(): array
    {
        if (! empty($this->hierarchicalOptions)) {
            return $this->hierarchicalOptions;
        }

        // Check if using relationship
        if ($this->getRelationship()) {
            $options = $this->getOptionsFromRelationship();
        } else {
            $options = $this->getOptions();
        }

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
