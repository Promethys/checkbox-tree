@php
    use Filament\Support\Facades\FilamentAsset;

    $id = $getId();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $hierarchicalOptions = $getHierarchicalOptions();
    $indeterminateItems = $getIndeterminateItems();
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
        })"
        {{ $attributes->merge($getExtraAttributes(), escape: false)->class([
            'fi-fo-checkbox-tree',
        ]) }}
    >
        <div class="space-y-2">
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
