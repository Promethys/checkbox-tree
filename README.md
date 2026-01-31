# Filament Checkbox Tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/promethys/checkbox-tree.svg?style=flat-square)](https://packagist.org/packages/promethys/checkbox-tree)
[![Total Downloads](https://img.shields.io/packagist/dt/promethys/checkbox-tree.svg?style=flat-square)](https://packagist.org/packages/promethys/checkbox-tree)

A hierarchical checkbox tree component for Filament v3 forms. Display checkboxes in a parent-child tree structure with automatic state management.

## Architecture & Compatibility

This component extends [Filament's native CheckboxList component](https://filamentphp.com/docs/3.x/forms/fields/checkbox-list), ensuring seamless integration with the Filament ecosystem while adding powerful hierarchical capabilities:

**Preserved CheckboxList Features:**
- All validation methods (`required()`, `rules()`, etc.)
- Bulk actions (`bulkToggleable()`, `selectAllAction()`, `deselectAllAction()`)
- Search functionality (`searchable()`, `searchPrompt()`)
- Disabled state (`disabled()`)
- Relationship handling (`relationship()`)
- State management methods (`default()`, `dehydrateStateUsing()`)
- HTML support (`allowHtml()`)
- Splitting options into columns (`columns()`, `gridDirection()`)
- Full compatibility with Filament's styling system and dark mode

**Enhanced for Hierarchical Use:**
- **Descriptions**: Integrated directly into the options array structure for better organization
- **State Management**: Intelligent parent-child state synchronization with indeterminate states
- **Data Storage**: Configurable leaf-only or full hierarchy storage
- **Tree Operations**: Collapsible sections and parent-child selection logic

This architecture ensures you get all the familiar CheckboxList functionality plus powerful new features specifically designed for hierarchical data structures.

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

## Visual Preview TODO: use a screenshot here

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
- Laravel 10.x+
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

By default, only leaf nodes (items without children) are stored:

```php
['create_users', 'edit_users', 'delete_users']
```

This works seamlessly with:
- JSON database columns
- Pivot tables via Filament relationships
- Simple array storage

To include parent keys in the stored value, use `storeParentKeys()`:

```php
CheckboxTree::make('permissions')
    ->storeParentKeys()
    ->options([...])

// Stored value: ['user_management', 'create_users', 'edit_users', 'delete_users']
```

### Flat Options with parent_id

You can provide flat options with `parent_id` references instead of manually nesting:

```php
CheckboxTree::make('permissions')
    ->hierarchical('parent_id')
    ->options([
        'user_management' => ['label' => 'User Management', 'parent_id' => null],
        'create_users' => ['label' => 'Create Users', 'parent_id' => 'user_management'],
        'edit_users' => ['label' => 'Edit Users', 'parent_id' => 'user_management'],
        'delete_users' => ['label' => 'Delete Users', 'parent_id' => 'user_management'],
        'post_management' => ['label' => 'Post Management', 'parent_id' => null],
        'create_posts' => ['label' => 'Create Posts', 'parent_id' => 'post_management'],
    ])
```

The component automatically builds the tree structure. Items with `parent_id => null` become root items.

The label field must be `label`. Falls back to the array key if `label` is not provided.

### Descriptions

Add description text below checkbox labels:

```php
CheckboxTree::make('permissions')
    ->options([
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full system access',
            'children' => [
                'manage_users' => [
                    'label' => 'Manage Users',
                    'description' => 'Create, edit, and delete users',
                ],
                'manage_settings' => 'Manage Settings',
            ],
        ],
    ])
```

Descriptions are optional and displayed in smaller, muted text below the label.

### HTML Support

By default, Filament escapes any HTML in option labels (native behavior inherited from CheckboxList). If you'd like to allow HTML, you can use the `allowHtml()` method. The plugin supports HTML formatting for both labels and descriptions:

```php
CheckboxTree::make('permissions')
    ->options([
        'admin' => [
            'label' => '<span class="text-blue-500">Administrator</span>',
            'description' => '<span class="text-xs">Full system access</span>',
            'children' => [
                'manage_users' => [
                    'label' => '<span class="text-blue-500">Manage Users</span>',
                    'description' => '<span class="text-xs">Create, edit, and delete users</span>',
                ],
                'manage_settings' => 'Manage Settings',
            ],
        ],
    ])
    ->allowHtml()
```

You can also use instances of `Illuminate\Support\HtmlString` or `Illuminate\Contracts\Support\Htmlable`. This approach provides better security and allows you to render HTML or even markdown:

```php
CheckboxTree::make('permissions')
    ->options([
        'admin' => [
            'label' => new HtmlString('<strong>Administrator</strong>'),
            'description' => str('**Full system** access')->inlineMarkdown()->toHtmlString(),
            'children' => [
                'manage_users' => [
                    'label' => new HtmlString('<strong>Manage Users</strong>'),
                    'description' => str('**Create**, **edit**, and **delete** users')->inlineMarkdown()->toHtmlString(),
                ],
                'manage_settings' => new HtmlString('<strong>Manage Settings</strong>'),
            ],
        ],
    ])
```

> **Security Warning**: Always ensure that HTML content is safe to render. User-generated content should be properly sanitized to prevent XSS attacks.

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

### Store Parent Keys

By default, only leaf nodes are stored in the state. To include parent keys:

```php
CheckboxTree::make('permissions')
    ->storeParentKeys()
    ->options([...])
```

This is useful when you need to know which parent categories were selected, not just the individual items.

## How It Works

1. **Check a parent** - All children become checked, parent shows as checked
2. **Uncheck a parent** - All children become unchecked
3. **Check some children** - Parent shows indeterminate state (dash)
4. **Check all children** - Parent automatically becomes checked
5. **Uncheck all children** - Parent automatically becomes unchecked

### Eloquent Relationships

Build a tree directly from a BelongsToMany relationship with hierarchical records:

```php
// Model: Permission has parent_id column
CheckboxTree::make('permissions')
    ->relationship('permissions', 'name')
    ->hierarchical('parent_id')
```

The component will:
1. Fetch all related records from the pivot table
2. Build a tree structure based on `parent_id`
3. Save selected values back to the pivot table

With query modification:

```php
CheckboxTree::make('permissions')
    ->relationship(
        'permissions',
        'name',
        fn ($query) => $query->where('active', true)
    )
    ->hierarchical('parent_id')
```

## Roadmap

Future versions may include:

- **HasMany relationships** - Support for HasMany in addition to BelongsToMany

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
