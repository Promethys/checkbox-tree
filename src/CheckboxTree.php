<?php

namespace Promethys\CheckboxTree;

use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Support\Services\RelationshipJoiner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

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
     * Set up a BelongsToMany relationship for the checkbox tree.
     *
     * This overrides the parent's relationship() method to fetch records
     * with their parent_id, enabling hierarchical tree building.
     *
     * @param  string|Closure|null  $name  The relationship name (defaults to field name)
     * @param  string|Closure|null  $titleAttribute  The attribute to use as the label
     * @param  Closure|null  $modifyQueryUsing  Callback to modify the query
     */
    public function relationship(
        string | Closure | null $name = null,
        string | Closure | null $titleAttribute = null,
        ?Closure $modifyQueryUsing = null
    ): static {
        $this->relationship = $name ?? $this->getName();
        $this->relationshipTitleAttribute = $titleAttribute;

        // Enable hierarchical mode automatically when relationship is set
        $this->isHierarchical = true;

        // Set up options from relationship - returns flat array with parent_id for buildTree()
        $this->options(static function (CheckboxTree $component) use ($modifyQueryUsing): array {
            $relationship = Relation::noConstraints(fn () => $component->getRelationship());

            if (! $relationship) {
                return [];
            }

            /** @var \Illuminate\Database\Eloquent\Builder $relationshipQuery */
            $relationshipQuery = app(RelationshipJoiner::class)->prepareQueryForNoConstraints($relationship);

            if ($modifyQueryUsing) {
                $relationshipQuery = $component->evaluate($modifyQueryUsing, [
                    'query' => $relationshipQuery,
                ]) ?? $relationshipQuery;
            }

            $records = $relationshipQuery->get();
            $titleAttribute = $component->getRelationshipTitleAttribute();
            $parentKey = $component->parentKey;
            $keyName = Str::afterLast($relationship->getQualifiedRelatedKeyName(), '.');

            // Build options with parent_id for hierarchical tree building
            $options = [];
            foreach ($records as $record) {
                $key = strval($record->{$keyName});

                // Use custom label callback if provided, otherwise use title attribute
                if ($component->hasOptionLabelFromRecordUsingCallback()) {
                    $label = $component->getOptionLabelFromRecord($record);
                } else {
                    $label = $record->{$titleAttribute};
                }

                $options[$key] = [
                    'label' => $label,
                    $parentKey => $record->{$parentKey} !== null ? strval($record->{$parentKey}) : null,
                ];
            }

            return $options;
        });

        // Set up state hydration from relationship
        $this->loadStateFromRelationshipsUsing(static function (CheckboxTree $component, ?array $state) use ($modifyQueryUsing): void {
            $relationship = $component->getRelationship();

            if (! $relationship) {
                return;
            }

            if ($modifyQueryUsing) {
                $component->evaluate($modifyQueryUsing, [
                    'query' => $relationship->getQuery(),
                ]);
            }

            /** @var Collection $relatedRecords */
            $relatedRecords = $relationship->getResults();

            $component->state(
                $relatedRecords
                    ->pluck($relationship->getRelatedKeyName())
                    ->map(static fn ($key): string => strval($key))
                    ->all(),
            );
        });

        // Set up relationship saving
        $this->saveRelationshipsUsing(static function (CheckboxTree $component, ?array $state) use ($modifyQueryUsing) {
            $relationship = $component->getRelationship();

            if (! $relationship) {
                return;
            }

            if ($modifyQueryUsing) {
                $component->evaluate($modifyQueryUsing, [
                    'query' => $relationship->getQuery(),
                ]);
            }

            // Filter out parent keys if storeParentKeys is false
            if (! $component->shouldStoreParentKeys()) {
                $parentKeys = $component->getParentKeys();
                $state = array_values(array_filter($state ?? [], fn ($key) => ! in_array($key, $parentKeys, true)));
            }

            /** @var Collection $relatedRecords */
            $relatedRecords = $relationship->getResults();

            $recordsToDetach = array_diff(
                $relatedRecords
                    ->pluck($relationship->getRelatedKeyName())
                    ->map(static fn ($key): string => strval($key))
                    ->all(),
                $state ?? [],
            );

            if (count($recordsToDetach) > 0) {
                $relationship->detach($recordsToDetach);
            }

            $pivotData = $component->getPivotData();

            if ($pivotData === []) {
                $relationship->sync($state ?? [], detaching: false);

                return;
            }

            $relationship->syncWithPivotValues($state ?? [], $pivotData, detaching: false);
        });

        // Don't dehydrate - relationship handles persistence
        $this->dehydrated(false);

        return $this;
    }

    /**
     * Get the relationship instance.
     */
    public function getRelationship(): ?BelongsToMany
    {
        $name = $this->getRelationshipName();

        if (blank($name)) {
            return null;
        }

        $model = $this->getModelInstance();

        if (! $model) {
            return null;
        }

        return $model->{$name}();
    }

    /**
     * Get the relationship name.
     */
    public function getRelationshipName(): ?string
    {
        return $this->evaluate($this->relationship);
    }

    /**
     * Get the hierarchical options structure for the view.
     *
     * Handles three formats:
     * 1. Already nested structure with 'children' keys
     * 2. Flat structure with parent_id (when hierarchical() is enabled)
     * 3. Mixed format (both nested and flat items)
     */
    public function getHierarchicalOptions(): array
    {
        if (! empty($this->hierarchicalOptions)) {
            return $this->hierarchicalOptions;
        }

        $options = $this->getOptions();

        // If hierarchical mode is enabled, process flat options
        if ($this->isHierarchical) {
            // Separate already-nested items from flat items
            $nestedItems = [];
            $flatItems = [];

            foreach ($options as $key => $option) {
                if (is_array($option) && isset($option['children'])) {
                    // Already nested, keep as-is
                    $nestedItems[$key] = $option;
                } else {
                    // Flat item, needs to be built into tree
                    $flatItems[$key] = $option;
                }
            }

            // Build tree from flat items and merge with nested items
            $builtTree = ! empty($flatItems) ? $this->buildTree($flatItems) : [];
            $this->hierarchicalOptions = array_merge($nestedItems, $builtTree);

            return $this->hierarchicalOptions;
        }

        // If options are already in hierarchical format, use them directly
        if ($this->hasNestedStructure($options)) {
            $this->hierarchicalOptions = $options;

            return $options;
        }

        // Plain options without hierarchy
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
     *
     * Converts flat options with parent_id references into a nested tree structure.
     * Items with parent_id = null are treated as root items.
     *
     * @param  array  $items  Flat array of options with parent_id references
     * @param  mixed  $parentId  Current parent level (null for root)
     * @return array Nested tree structure
     */
    protected function buildTree(array $items, mixed $parentId = null): array
    {
        $tree = [];

        foreach ($items as $key => $item) {
            // Determine the parent ID for this item
            // Use array_key_exists to distinguish between null and not set
            $itemParentId = null;
            if (is_array($item) && array_key_exists($this->parentKey, $item)) {
                $itemParentId = $item[$this->parentKey];
            }

            // Match items to the current parent level
            if ($itemParentId === $parentId) {
                // Extract label with multiple fallbacks
                $label = $this->extractLabel($item, (string) $key);

                // Recursively build children
                $children = $this->buildTree($items, $key);

                $node = ['label' => $label];

                // Only add children key if there are actually children
                if (! empty($children)) {
                    $node['children'] = $children;
                }

                $tree[$key] = $node;
            }
        }

        return $tree;
    }

    /**
     * Extract label from an option item with multiple fallbacks.
     *
     * Supports various formats:
     * - String: 'Label'
     * - Array with 'label': ['label' => 'Label', 'parent_id' => null]
     * - Array with 'name': ['name' => 'Name', 'parent_id' => null]
     * - Array with 'title': ['title' => 'Title', 'parent_id' => null]
     * - Falls back to key if no label field found
     */
    protected function extractLabel(mixed $item, string $key): string
    {
        if (is_string($item)) {
            return $item;
        }

        if (is_array($item)) {
            return $item['label'] ?? $item['name'] ?? $item['title'] ?? $key;
        }

        return $key;
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
