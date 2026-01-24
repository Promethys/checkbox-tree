# Filament Checkbox Tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/promethys/checkbox-tree.svg?style=flat-square)](https://packagist.org/packages/promethys/checkbox-tree)
[![Total Downloads](https://img.shields.io/packagist/dt/promethys/checkbox-tree.svg?style=flat-square)](https://packagist.org/packages/promethys/checkbox-tree)

A hierarchical checkbox tree component for Filament v3 forms. Display checkboxes in a parent-child tree structure with automatic state management.

## Features

- **Hierarchical Structure** - Display checkboxes in unlimited nested levels
- **Parent-Child Control** - Checking a parent automatically selects all children
- **Indeterminate States** - Visual indication when only some children are selected
- **Collapsible Sections** - Expand/collapse parent nodes with smooth animations
- **Search** - Filter tree items by keyword, shows parents when children match
- **Bulk Actions** - Select all / Deselect all buttons with customizable labels
- **Native Filament Styling** - Uses Filament's checkbox component, works with custom themes
- **Dark Mode Support** - Fully compatible with Filament's dark mode
- **Flat Array Storage** - Stores selections as a simple array, compatible with JSON columns and relationships

## Visual Preview

```
[x] User Management              (all children selected)
    [x] Create Users
    [x] Edit Users
    [x] Delete Users

[-] Content Management           (some children selected - indeterminate)
    [x] Create Posts
    [x] Edit Posts
    [ ] Delete Posts

[ ] Analytics                    (no children selected)
    [ ] View Reports
    [ ] Export Data
```

## Use Cases

- Permission management with grouped permissions
- Category/subcategory selection
- Feature flags with hierarchical options
- Department/team hierarchies
- Any multi-level checkbox selection

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
- Filament 3.x

## Installation

Install via Composer:

```bash
composer require promethys/checkbox-tree
```

Publish Filament assets (required):

```bash
php artisan filament:assets
```

Clear caches:

```bash
php artisan optimize:clear
```

## Usage

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

### Stored Data Format

Selections are stored as a flat array:

```php
['user_management', 'create_users', 'edit_users', 'delete_users']
```

This works seamlessly with:
- JSON database columns
- Pivot tables via Filament relationships
- Simple array storage

### Multi-Level Nesting

The component supports unlimited nesting depth:

```php
CheckboxTree::make('categories')
    ->options([
        'electronics' => [
            'label' => 'Electronics',
            'children' => [
                'computers' => [
                    'label' => 'Computers',
                    'children' => [
                        'laptops' => 'Laptops',
                        'desktops' => 'Desktops',
                    ],
                ],
                'phones' => 'Mobile Phones',
            ],
        ],
    ])
```

### With Validation

Standard Filament validation works:

```php
CheckboxTree::make('permissions')
    ->required()
    ->options([...])
```

### Disabled State

```php
CheckboxTree::make('permissions')
    ->disabled()
    ->options([...])
```

### Collapsible Sections

Enable collapsible parent nodes:

```php
CheckboxTree::make('permissions')
    ->collapsible()
    ->options([...])
```

Start with all sections collapsed:

```php
CheckboxTree::make('permissions')
    ->collapsible(defaultCollapsed: true)
    ->options([...])
```

### Search

Enable search to filter tree items:

```php
CheckboxTree::make('permissions')
    ->searchable()
    ->options([...])
```

Customize the search placeholder:

```php
CheckboxTree::make('permissions')
    ->searchable()
    ->searchPrompt('Search permissions...')
    ->options([...])
```

When searching, parent nodes are shown if any of their children match the search term.

### Bulk Actions

Enable "Select all / Deselect all" buttons:

```php
CheckboxTree::make('permissions')
    ->bulkToggleable()
    ->options([...])
```

Customize the action labels:

```php
CheckboxTree::make('technologies')
    ->bulkToggleable()
    ->selectAllAction(
        fn ($action) => $action->label('Select all technologies')
    )
    ->deselectAllAction(
        fn ($action) => $action->label('Clear selection')
    )
    ->options([...])
```

## How It Works

1. **Check a parent** - All children become checked, parent shows as checked
2. **Uncheck a parent** - All children become unchecked
3. **Check some children** - Parent shows indeterminate state (dash)
4. **Check all children** - Parent automatically becomes checked
5. **Uncheck all children** - Parent automatically becomes unchecked

## Roadmap

Future versions will include:

- **Eloquent relationships** - Build tree from database models with `parent_id`
- **Store options** - Choose whether to include parent keys in stored value

## Development

```bash
# Clone the repository
git clone https://github.com/promethys/checkbox-tree.git

# Install dependencies
composer install
npm install

# Build assets
npm run build

# Run tests
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [LICENSE](LICENSE.md) for more information.
