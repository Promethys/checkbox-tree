@php
    use Filament\Support\Facades\FilamentView;

    $id = $getId();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $hierarchicalOptions = $getHierarchicalOptions();
    $indeterminateItems = $getIndeterminateItems();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="checkboxTreeFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            options: @js($hierarchicalOptions),
            indeterminateItems: @js($indeterminateItems),
        })"
        {{ $attributes->merge($getExtraAttributes(), escape: false)->class([
            'filament-forms-checkbox-tree-component',
        ]) }}
    >
        <div @class([
            'space-y-2',
            'opacity-70' => $isDisabled,
        ])>
            @foreach ($hierarchicalOptions as $key => $option)
                <x-checkbox-tree::tree-item
                    :key="$key"
                    :option="$option"
                    :disabled="$isDisabled"
                    :level="0"
                />
            @endforeach
        </div>
    </div>
</x-dynamic-component>
