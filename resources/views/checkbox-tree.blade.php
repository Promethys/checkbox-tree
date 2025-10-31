@php
    use Filament\Support\Facades\FilamentView;

    $id = $getId();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $hierarchicalOptions = $getHierarchicalOptions();
    $indeterminateItems = $getIndeterminateItems();
    $isSearchable = $isSearchable();
    $searchPrompt = $getSearchPrompt();
    $isExpandable = $isExpandable();
    $defaultExpanded = $isDefaultExpanded();
    $isBulkToggleable = $isBulkToggleable();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="checkboxTreeFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            options: @js($hierarchicalOptions),
            indeterminateItems: @js($indeterminateItems),
            searchable: @js($isSearchable),
            expandable: @js($isExpandable),
            defaultExpanded: @js($defaultExpanded),
            bulkToggleable: @js($isBulkToggleable),
        })"
        {{ $attributes->merge($getExtraAttributes(), escape: false)->class([
            'filament-forms-checkbox-tree-component',
        ]) }}
    >
        @if ($isSearchable)
            <div class="mb-3">
                <input
                    type="text"
                    x-model="searchQuery"
                    x-on:input="filterOptions"
                    placeholder="{{ $searchPrompt }}"
                    @disabled($isDisabled)
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-600 disabled:opacity-70"
                />
            </div>
        @endif

        @if ($isBulkToggleable)
            <div class="mb-3 flex gap-2">
                <button
                    type="button"
                    x-on:click="selectAll"
                    @disabled($isDisabled)
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 disabled:opacity-70"
                >
                    {{ __('Select all') }}
                </button>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <button
                    type="button"
                    x-on:click="deselectAll"
                    @disabled($isDisabled)
                    class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 disabled:opacity-70"
                >
                    {{ __('Deselect all') }}
                </button>
            </div>
        @endif

        <div @class([
            'space-y-2',
            'opacity-70' => $isDisabled,
        ])>
            <template x-for="(option, key) in filteredOptions" :key="key">
                <div x-html="renderTreeItem(key, option, 0)"></div>
            </template>
        </div>
    </div>
</x-dynamic-component>
