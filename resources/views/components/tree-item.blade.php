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
    $indent = $level * 1.5;

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
    )->class(['mt-1']);
@endphp

<div class="fi-fo-checkbox-tree-item">
    <label class="flex gap-x-3" style="padding-left: {{ $indent }}rem;">
        <x-filament::input.checkbox :attributes="$checkboxAttributeBag" />

        <span @class([
            'text-sm leading-6',
            'font-medium text-gray-950 dark:text-white' => ! $disabled,
            'text-gray-500 dark:text-gray-400' => $disabled,
        ])>
            {{ $label }}
        </span>
    </label>

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
