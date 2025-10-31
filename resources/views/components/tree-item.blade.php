@props([
    'key',
    'option',
    'disabled' => false,
    'level' => 0,
])

@php
    $hasChildren = is_array($option) && isset($option['children']);
    $label = is_array($option) ? ($option['label'] ?? $key) : $option;
    $children = $hasChildren ? $option['children'] : [];
    $indent = $level * 1.5; // 1.5rem per level
@endphp

<div class="filament-forms-checkbox-tree-item">
    <div
        class="flex items-center gap-x-3"
        style="padding-left: {{ $indent }}rem;"
    >
        <input
            type="checkbox"
            value="{{ $key }}"
            id="checkbox-{{ $key }}"
            @if($hasChildren)
                x-on:change="toggleParent('{{ $key }}')"
                x-bind:checked="isParentChecked('{{ $key }}')"
                x-bind:indeterminate="isIndeterminate('{{ $key }}')"
            @else
                x-on:change="toggleChild('{{ $key }}')"
                x-bind:checked="isChecked('{{ $key }}')"
            @endif
            @disabled($disabled)
            class="filament-forms-checkbox-list-component-option-checkbox rounded border-gray-300 text-primary-600 shadow-sm focus:ring focus:ring-primary-500 focus:ring-opacity-50 focus:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-600 dark:bg-gray-700 dark:checked:border-primary-600 dark:checked:bg-primary-600 dark:focus:ring-primary-600"
        />

        <label
            for="checkbox-{{ $key }}"
            @class([
                'text-sm font-medium leading-6',
                'text-gray-950 dark:text-white' => ! $disabled,
                'text-gray-500 dark:text-gray-400' => $disabled,
                'cursor-pointer' => ! $disabled,
                'cursor-not-allowed' => $disabled,
                'font-semibold' => $hasChildren,
            ])
        >
            {{ $label }}
        </label>
    </div>

    @if($hasChildren)
        <div class="mt-2 space-y-2">
            @foreach($children as $childKey => $childOption)
                <x-checkbox-tree::tree-item
                    :key="$childKey"
                    :option="$childOption"
                    :disabled="$disabled"
                    :level="$level + 1"
                />
            @endforeach
        </div>
    @endif
</div>
