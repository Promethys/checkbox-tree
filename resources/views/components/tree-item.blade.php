@props([
    'key',
    'option',
    'disabled' => false,
    'level' => 0,
    'searchable' => false,
    'collapsible' => false,
])

@php
    $hasChildren = is_array($option) && isset($option['children']) && !empty($option['children']);
    $label = is_array($option) ? ($option['label'] ?? $key) : $option;
    $description = is_array($option) ? ($option['description'] ?? null) : null;
    $children = $hasChildren ? $option['children'] : [];
    $indent = $level * 1.5;
    $escapedLabel = addslashes($label);

    $checkboxAttributes = [
        'disabled' => $disabled,
        'value' => $key,
    ];

    if ($hasChildren) {
        $checkboxAttributes['x-on:change'] = "toggleParent('{$key}')";
        $checkboxAttributes['x-bind:checked'] = "isParentChecked('{$key}')";
        $checkboxAttributes['x-bind:indeterminate'] = "isIndeterminate('{$key}')";
    } else {
        $checkboxAttributes['x-on:change'] = "toggleChild('{$key}')";
        $checkboxAttributes['x-bind:checked'] = "isChecked('{$key}')";
    }

    $checkboxAttributeBag = \Filament\Support\prepare_inherited_attributes(
        new \Illuminate\View\ComponentAttributeBag($checkboxAttributes)
    )->class(['mt-1 shrink-0']);
@endphp

<div
    class="fi-fo-checkbox-tree-item"
    @if ($searchable) x-show="isItemVisible('{{ $key }}', '{{ $escapedLabel }}')" @endif
>
    <div class="flex gap-x-1" style="padding-left: {{ $indent }}rem;">
        @if ($collapsible && $hasChildren)
            <button
                type="button"
                x-on:click="toggleCollapsed('{{ $key }}')"
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
            >
                <x-filament::icon
                    x-show="! isCollapsed('{{ $key }}')"
                    icon="heroicon-m-chevron-down"
                    class="h-4 w-4"
                />
                <x-filament::icon
                    x-show="isCollapsed('{{ $key }}')"
                    x-cloak
                    icon="heroicon-m-chevron-right"
                    class="h-4 w-4"
                />
            </button>
        @elseif ($collapsible)
            {{-- Spacer for alignment when item has no children --}}
            <div class="w-6 shrink-0"></div>
        @endif

        <label class="flex gap-x-3">
            <x-filament::input.checkbox :attributes="$checkboxAttributeBag" />

            <div class="flex flex-col">
                <span @class([
                    'text-sm leading-6',
                    'font-medium text-gray-950 dark:text-white' => ! $disabled,
                    'text-gray-500 dark:text-gray-400' => $disabled,
                ])>
                    {{ $label }}
                </span>

                @if ($description)
                    <span @class([
                        'text-xs leading-5',
                        'text-gray-500 dark:text-gray-400' => ! $disabled,
                        'text-gray-400 dark:text-gray-500' => $disabled,
                    ])>
                        {{ $description }}
                    </span>
                @endif
            </div>
        </label>
    </div>

    @if($hasChildren)
        <div
            class="mt-2 space-y-2"
            @if ($collapsible) x-show="! isCollapsed('{{ $key }}')" x-collapse @endif
        >
            @foreach($children as $childKey => $childOption)
                <x-checkbox-tree::tree-item
                    :key="$childKey"
                    :option="$childOption"
                    :disabled="$disabled"
                    :level="$level + 1"
                    :searchable="$searchable"
                    :collapsible="$collapsible"
                />
            @endforeach
        </div>
    @endif
</div>
