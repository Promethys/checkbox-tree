# Filament Checkbox Tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/promethys/checkbox-tree.svg?style=flat-square)](https://packagist.org/packages/promethys/checkbox-tree)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/promethys/checkbox-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/promethys/checkbox-tree/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/promethys/checkbox-tree/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/promethys/checkbox-tree/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/promethys/checkbox-tree.svg?style=flat-square)](https://packagist.org/packages/promethys/checkbox-tree)

A powerful Filament form component that provides hierarchical checkbox selection with parent-child relationships, perfect for permission systems, category management, and any nested data structures.

## Features

- 🌳 **Hierarchical Structure** - Unlimited nesting levels with parent-child relationships
- 🎯 **Smart Selection** - Parent checkboxes automatically select/deselect all children
- ⚡ **Indeterminate State** - Visual indication when only some children are selected
- 🔍 **Search & Filter** - Real-time filtering of tree options
- 📁 **Collapsible Sections** - Expand/collapse parent nodes
- ✅ **Bulk Actions** - Select all / Deselect all functionality
- 🔗 **Eloquent Integration** - Works seamlessly with Laravel relationships
- 🌙 **Dark Mode** - Full support for Filament's dark mode
- ♿ **Accessible** - Keyboard navigation and screen reader support

## Requirements

- PHP 8.1 or higher
- Filament 3.x
- Laravel 10.x or 11.x

## Installation

Install the package via composer:

```bash
composer require promethys/checkbox-tree
```

The package will automatically register its service provider.

## Basic Usage

### Simple Nested Structure

```php
use Promethys\CheckboxTree\CheckboxTree;

CheckboxTree::make('permissions')
    ->label('Permissions')
    ->options([
        'user_management' => [
            'label' => 'User Management',
            'children' => [
                'create_users' => 'Create Users',
                'edit_users' => 'Edit Users',
                'delete_users' => 'Delete Users',
            ],
        ],
        'content_management' => [
            'label' => 'Content Management',
            'children' => [
                'create_posts' => 'Create Posts',
                'edit_posts' => 'Edit Posts',
                'publish_posts' => 'Publish Posts',
            ],
        ],
    ])
```

### With Eloquent Relationships

```php
CheckboxTree::make('permissions')
    ->relationship('permissions', 'name')
    ->hierarchical('parent_id')
```

### Building Tree from Flat Data

If you have flat data with parent references, enable hierarchical mode:

```php
CheckboxTree::make('categories')
    ->hierarchical('parent_id')
    ->options([
        1 => ['label' => 'Electronics', 'parent_id' => null],
        2 => ['label' => 'Computers', 'parent_id' => 1],
        3 => ['label' => 'Laptops', 'parent_id' => 2],
        4 => ['label' => 'Desktops', 'parent_id' => 2],
        5 => ['label' => 'Phones', 'parent_id' => 1],
    ])
```

## Advanced Features

### Search Functionality

Add a search input to filter options in real-time:

```php
CheckboxTree::make('permissions')
    ->searchable()
    ->options([...])

// Custom search placeholder
CheckboxTree::make('permissions')
    ->searchable('Search permissions...')
    ->options([...])
```

### Collapsible Sections

Allow users to expand/collapse parent nodes:

```php
CheckboxTree::make('permissions')
    ->expandable()
    ->options([...])

// Expanded by default
CheckboxTree::make('permissions')
    ->expandable(defaultExpanded: true)
    ->options([...])
```

### Bulk Actions

Add "Select all" and "Deselect all" buttons:

```php
CheckboxTree::make('permissions')
    ->bulkToggleable()
    ->options([...])
```

### Complete Example

Combine all features for a full-featured tree:

```php
CheckboxTree::make('permissions')
    ->relationship('permissions', 'name')
    ->hierarchical('parent_id')
    ->searchable('Search permissions...')
    ->expandable(defaultExpanded: false)
    ->bulkToggleable()
```

## Working with Relationships

### BelongsToMany Example

```php
// In your model
public function permissions(): BelongsToMany
{
    return $this->belongsToMany(Permission::class);
}

// In your form
CheckboxTree::make('permissions')
    ->relationship('permissions', 'name')
    ->hierarchical('parent_id')
```

### Custom Query Modification

```php
CheckboxTree::make('permissions')
    ->relationship(
        name: 'permissions',
        titleAttribute: 'name',
        modifyQueryUsing: fn ($query) => $query->where('active', true)
    )
    ->hierarchical('parent_id')
```

## Data Structure

The selected values are stored as a flat array of keys, making it easy to work with:

```php
// Selected: User Management (parent) + all its children
['user_management', 'create_users', 'edit_users', 'delete_users']

// This works seamlessly with BelongsToMany relationships
$record->permissions()->sync($data['permissions']);
```

## Styling

The component automatically inherits your Filament theme colors and adapts to dark mode. The tree structure uses proper indentation and visual hierarchy.

### Indeterminate State

When a parent has some (but not all) children selected, it displays an indeterminate state with a dash icon instead of a checkmark.

## API Reference

### Methods

| Method | Description |
|--------|-------------|
| `hierarchical(string $parentKey = 'parent_id')` | Enable hierarchical mode and specify the parent key field |
| `searchable(bool\|string $condition = true)` | Enable search functionality with optional custom placeholder |
| `expandable(bool $condition = true, bool $defaultExpanded = false)` | Enable collapsible sections |
| `bulkToggleable(bool $condition = true)` | Enable select all / deselect all buttons |
| `relationship(string $name, string $titleAttribute, ?callable $modifyQueryUsing = null)` | Work with Eloquent relationships |

All standard Filament field methods are also available: `label()`, `required()`, `disabled()`, `default()`, `helperText()`, etc.

## Use Cases

Perfect for:
- **Permission Management** - Group permissions by modules with sub-permissions
- **Category Selection** - Hierarchical categories and subcategories
- **Feature Flags** - Grouped feature toggles
- **Organization Structures** - Departments, teams, and sub-teams
- **Menu Management** - Nested menu items
- **Product Categories** - Multi-level product categorization
- **Tag Systems** - Hierarchical tagging

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ilainiriko Tambaza](https://github.com/nirine1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
