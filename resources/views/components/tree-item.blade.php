@props([
    'key',
    'option',
    'disabled' => false,
    'disabledOptionKeys' => [],
    'level' => 0,
    'searchable' => false,
    'collapsible' => false,
    'isHtmlAllowed' => false,
    'gridDirection' => 'column',
])

@php
    $hasChildren = is_array($option) && isset($option['children']) && !empty($option['children']);
    $label = is_array($option) ? ($option['label'] ?? $key) : $option;
    $description = is_array($option) ? ($option['description'] ?? null) : null;
    $children = $hasChildren ? $option['children'] : [];
    $indent = $level * 1.5;
    $escapedLabel = addslashes($label);

    // Disabled if: parent passed disabled=true (cascade) OR this key is individually disabled
    $isCurrentDisabled = $disabled || in_array((string) $key, $disabledOptionKeys, true);

    $checkboxAttributes = [
        'disabled' => $isCurrentDisabled,
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
    )->class(['mt-1 shrink-0 break-inside-avoid']);
@endphp

<div
    @class([
        'fi-fo-checkbox-tree-item',
        'break-inside-avoid pt-4' => $level === 0 && $gridDirection === 'column',
    ])
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

            <div class="grid text-sm leading-6">
                <span class="fi-fo-checkbox-list-option-label overflow-hidden break-words font-medium text-gray-950 dark:text-white">
                    @if ($isHtmlAllowed)
                        {!! $label !!}
                    @else
                        {{ $label }}
                    @endif
                </span>

                @if ($description)
                    <p
                        class="fi-fo-checkbox-list-option-description text-gray-500 dark:text-gray-400"
                    >
                        @if ($isHtmlAllowed)
                            {!! $description !!}
                        @else
                            {{ $description }}
                        @endif
                    </p>
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
                    :disabled="$isCurrentDisabled"
                    :disabled-option-keys="$disabledOptionKeys"
                    :level="$level + 1"
                    :searchable="$searchable"
                    :collapsible="$collapsible"
                    :is-html-allowed="$isHtmlAllowed"
                    :grid-direction="$gridDirection"
                />
            @endforeach
        </div>
    @endif
</div>
