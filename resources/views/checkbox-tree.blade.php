@php
    use Filament\Support\Facades\FilamentAsset;

    $id = $getId();
    $isDisabled = $isDisabled();
    $isBulkToggleable = $isBulkToggleable();
    $isSearchable = $isSearchable();
    $isCollapsible = $isCollapsible();
    $defaultCollapsed = $isDefaultCollapsed();
    $statePath = $getStatePath();
    $hierarchicalOptions = $getHierarchicalOptions();
    $indeterminateItems = $getIndeterminateItems();
    $parentKeys = $getParentKeys();
    $storeParentKeys = $shouldStoreParentKeys();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-ignore
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('checkbox-tree', 'promethys/checkbox-tree') }}"
        x-data="checkboxTreeFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            options: @js($hierarchicalOptions),
            indeterminateItems: @js($indeterminateItems),
            searchable: @js($isSearchable),
            collapsible: @js($isCollapsible),
            defaultCollapsed: @js($defaultCollapsed),
            parentKeys: @js($parentKeys),
            storeParentKeys: @js($storeParentKeys),
        })"
        {{ $attributes->merge($getExtraAttributes(), escape: false)->class([
            'fi-fo-checkbox-tree',
        ]) }}
    >
        @if (! $isDisabled)
            @if ($isSearchable)
                <x-filament::input.wrapper
                    inline-prefix
                    prefix-icon="heroicon-m-magnifying-glass"
                    prefix-icon-alias="forms:components.checkbox-list.search-field"
                    class="mb-4"
                >
                    <x-filament::input
                        inline-prefix
                        :placeholder="$getSearchPrompt()"
                        type="search"
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes(
                                new \Illuminate\View\ComponentAttributeBag([
                                    'x-model.debounce.' . $getSearchDebounce() => 'search',
                                ])
                            )
                        "
                    />
                </x-filament::input.wrapper>
            @endif
        @endif

        @if (! $isDisabled && $isBulkToggleable && count($hierarchicalOptions))
            <div
                x-cloak
                class="mb-2"
                wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.actions"
            >
                <span
                    x-show="! areAllSelected"
                    x-on:click="selectAll()"
                    wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.actions.select-all"
                >
                    {{ $getAction('selectAll') }}
                </span>

                <span
                    x-show="areAllSelected"
                    x-on:click="deselectAll()"
                    wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.actions.deselect-all"
                >
                    {{ $getAction('deselectAll') }}
                </span>
            </div>
        @endif

        <div
            class="space-y-2"
            @if ($isSearchable) x-show="hasVisibleResults()" @endif
        >
            @foreach ($hierarchicalOptions as $key => $option)
                <x-checkbox-tree::tree-item
                    :key="$key"
                    :option="$option"
                    :disabled="$isDisabled"
                    :level="0"
                    :searchable="$isSearchable"
                    :collapsible="$isCollapsible"
                    :is-html-allowed="$isHtmlAllowed()"
                />
            @endforeach
        </div>

        @if ($isSearchable)
            <div
                x-cloak
                x-show="! hasVisibleResults()"
                class="text-sm text-gray-500 dark:text-gray-400"
            >
                {{ $getNoSearchResultsMessage() }}
            </div>
        @endif
    </div>
</x-dynamic-component>
