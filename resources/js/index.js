export default function checkboxTreeFormComponent({
    state,
    options,
    indeterminateItems,
    searchable = false,
    collapsible = false,
    defaultCollapsed = false,
    parentKeys = [],
    storeParentKeys = false
}) {
    return {
        state: state,
        options: options,
        indeterminateItems: indeterminateItems,
        areAllSelected: false,
        searchable: searchable,
        search: '',
        collapsible: collapsible,
        defaultCollapsed: defaultCollapsed,
        collapsedItems: [],
        parentKeys: parentKeys,
        storeParentKeys: storeParentKeys,

        init() {
            // Watch for external state changes
            this.$watch('state', () => {
                this.updateIndeterminateStates()
                this.updateAreAllSelected()
            })

            // Initialize areAllSelected
            this.updateAreAllSelected()

            // Initialize collapsed state
            if (this.collapsible) {
                this.collapsedItems = this.defaultCollapsed ? [...this.parentKeys] : []
            }
        },

        /**
         * Filter state to remove parent keys if storeParentKeys is false
         */
        filterState(keys) {
            if (this.storeParentKeys) {
                return keys
            }
            return keys.filter(key => !this.parentKeys.includes(key))
        },

        /**
         * Check if an item is collapsed
         */
        isCollapsed(key) {
            if (!this.collapsible) {
                return false
            }
            return this.collapsedItems.includes(key)
        },

        /**
         * Toggle collapsed state for an item
         */
        toggleCollapsed(key) {
            if (!this.collapsible) {
                return
            }

            if (this.collapsedItems.includes(key)) {
                this.collapsedItems = this.collapsedItems.filter(k => k !== key)
            } else {
                this.collapsedItems = [...this.collapsedItems, key]
            }
        },

        /**
         * Expand all parent items
         */
        expandAll() {
            this.collapsedItems = []
        },

        /**
         * Collapse all parent items
         */
        collapseAll() {
            this.collapsedItems = [...this.parentKeys]
        },

        /**
         * Check if an item should be visible based on search
         * An item is visible if:
         * - Search is empty
         * - Its label matches the search
         * - Any of its descendants match the search
         */
        isItemVisible(key, label) {
            if (!this.searchable || !this.search || this.search.trim() === '') {
                return true
            }

            const searchLower = this.search.toLowerCase()
            const labelLower = (label || '').toLowerCase()

            // Check if this item's label matches
            if (labelLower.includes(searchLower)) {
                return true
            }

            // Check if any descendant matches
            const option = this.findOptionByKey(key)
            if (option && option.children) {
                return this.hasMatchingDescendant(option.children, searchLower)
            }

            return false
        },

        /**
         * Check if any descendant of an option matches the search
         */
        hasMatchingDescendant(children, searchLower) {
            for (const [key, child] of Object.entries(children)) {
                const childLabel = (typeof child === 'string' ? child : (child.label || '')).toLowerCase()

                if (childLabel.includes(searchLower)) {
                    return true
                }

                if (child.children && this.hasMatchingDescendant(child.children, searchLower)) {
                    return true
                }
            }

            return false
        },

        /**
         * Find an option by its key in the tree
         */
        findOptionByKey(targetKey, options = null) {
            options = options || this.options

            for (const [key, option] of Object.entries(options)) {
                if (key === targetKey) {
                    return option
                }

                if (option.children) {
                    const found = this.findOptionByKey(targetKey, option.children)
                    if (found) {
                        return found
                    }
                }
            }

            return null
        },

        /**
         * Check if there are any visible results for the current search
         */
        hasVisibleResults() {
            if (!this.searchable || !this.search || this.search.trim() === '') {
                return true
            }

            const searchLower = this.search.toLowerCase()
            return this.hasAnyMatch(this.options, searchLower)
        },

        /**
         * Check if any option in the tree matches the search
         */
        hasAnyMatch(options, searchLower) {
            for (const [key, option] of Object.entries(options)) {
                const label = (typeof option === 'string' ? option : (option.label || '')).toLowerCase()

                if (label.includes(searchLower)) {
                    return true
                }

                if (option.children && this.hasAnyMatch(option.children, searchLower)) {
                    return true
                }
            }

            return false
        },

        /**
         * Select all options in the tree
         */
        selectAll() {
            this.state = this.filterState(this.getAllKeys(this.options))
            this.updateIndeterminateStates()
            this.updateAreAllSelected()
        },

        /**
         * Deselect all options in the tree
         */
        deselectAll() {
            this.state = []
            this.updateIndeterminateStates()
            this.updateAreAllSelected()
        },

        /**
         * Update the areAllSelected flag
         */
        updateAreAllSelected() {
            const allKeys = this.getAllKeys(this.options)
            this.areAllSelected = allKeys.length > 0 && allKeys.every(key => this.isChecked(key))
        },

        /**
         * Get all keys from the options tree (recursive)
         */
        getAllKeys(options) {
            const keys = []

            Object.entries(options).forEach(([key, option]) => {
                keys.push(key)

                if (option.children && Object.keys(option.children).length > 0) {
                    keys.push(...this.getAllKeys(option.children))
                }
            })

            return keys
        },

        /**
         * Check if a specific key is checked
         */
        isChecked(key) {
            return Array.isArray(this.state) && this.state.includes(key)
        },

        /**
         * Check if a parent is fully checked (all children selected)
         */
        isParentChecked(parentKey) {
            const childrenKeys = this.getChildrenKeys(parentKey)

            if (childrenKeys.length === 0) {
                return this.isChecked(parentKey)
            }

            // Parent is checked if all children are checked
            return childrenKeys.every(key => this.isChecked(key))
        },

        /**
         * Check if a parent is in indeterminate state
         */
        isIndeterminate(parentKey) {
            const childrenKeys = this.getChildrenKeys(parentKey)

            if (childrenKeys.length === 0) {
                return false
            }

            const checkedChildren = childrenKeys.filter(key => this.isChecked(key))

            // Indeterminate if some (but not all) children are checked
            return checkedChildren.length > 0 && checkedChildren.length < childrenKeys.length
        },

        /**
         * Toggle a parent checkbox (and all its children)
         */
        toggleParent(parentKey) {
            const childrenKeys = this.getChildrenKeys(parentKey)
            const shouldCheck = !this.isParentChecked(parentKey)

            // Ensure state is an array
            if (!Array.isArray(this.state)) {
                this.state = []
            }

            if (shouldCheck) {
                // Add parent and all children (filtered based on storeParentKeys)
                const keysToAdd = this.filterState([parentKey, ...childrenKeys]).filter(
                    key => !this.state.includes(key)
                )
                this.state = [...this.state, ...keysToAdd]
            } else {
                // Remove parent and all children
                const keysToRemove = [parentKey, ...childrenKeys]
                this.state = this.state.filter(key => !keysToRemove.includes(key))
            }

            this.updateIndeterminateStates()
        },

        /**
         * Toggle a child checkbox (and update parent state)
         */
        toggleChild(childKey) {
            // Ensure state is an array
            if (!Array.isArray(this.state)) {
                this.state = []
            }

            const isCurrentlyChecked = this.isChecked(childKey)

            if (isCurrentlyChecked) {
                // Remove the child
                this.state = this.state.filter(key => key !== childKey)
            } else {
                // Add the child
                this.state = [...this.state, childKey]
            }

            // Check if we need to update parent
            this.updateParentState(childKey)
            this.updateIndeterminateStates()
        },

        /**
         * Update parent state based on children
         */
        updateParentState(childKey) {
            const parentKey = this.findParentKey(childKey)

            if (!parentKey) {
                return
            }

            const childrenKeys = this.getChildrenKeys(parentKey)
            const allChildrenChecked = childrenKeys.every(key => this.isChecked(key))
            const someChildrenChecked = childrenKeys.some(key => this.isChecked(key))
            const isParentCurrentlyChecked = this.isChecked(parentKey)

            if (allChildrenChecked && !isParentCurrentlyChecked) {
                // All children checked, add parent only if storeParentKeys is true
                if (this.storeParentKeys) {
                    this.state = [...this.state, parentKey]
                }
            } else if (!someChildrenChecked && isParentCurrentlyChecked) {
                // No children checked, remove parent
                this.state = this.state.filter(key => key !== parentKey)
            }

            // Recursively update grandparent
            this.updateParentState(parentKey)
        },

        /**
         * Update the indeterminate states array
         */
        updateIndeterminateStates() {
            this.indeterminateItems = this.calculateIndeterminateItems(this.options)
        },

        /**
         * Calculate which items should be indeterminate
         */
        calculateIndeterminateItems(options) {
            const indeterminate = []

            Object.entries(options).forEach(([key, option]) => {
                if (option.children) {
                    const childrenKeys = this.getChildrenKeysFromOption(option.children)
                    const checkedChildren = childrenKeys.filter(k => this.isChecked(k))

                    if (checkedChildren.length > 0 && checkedChildren.length < childrenKeys.length) {
                        indeterminate.push(key)
                    }

                    // Recursively check children
                    const childIndeterminate = this.calculateIndeterminateItems(option.children)
                    indeterminate.push(...childIndeterminate)
                }
            })

            return indeterminate
        },

        /**
         * Get all children keys for a parent key
         */
        getChildrenKeys(parentKey) {
            return this.findChildrenKeysInOptions(this.options, parentKey)
        },

        /**
         * Find children keys in options tree
         */
        findChildrenKeysInOptions(options, parentKey) {
            for (const [key, option] of Object.entries(options)) {
                if (key === parentKey && option.children) {
                    return this.getChildrenKeysFromOption(option.children)
                }

                if (option.children) {
                    const found = this.findChildrenKeysInOptions(option.children, parentKey)
                    if (found.length > 0) {
                        return found
                    }
                }
            }

            return []
        },

        /**
         * Get all keys from a children object (recursive)
         */
        getChildrenKeysFromOption(children) {
            const keys = []

            Object.entries(children).forEach(([key, child]) => {
                keys.push(key)

                if (child.children) {
                    keys.push(...this.getChildrenKeysFromOption(child.children))
                }
            })

            return keys
        },

        /**
         * Find the parent key for a given child key
         */
        findParentKey(childKey, options = null, parentKey = null) {
            options = options || this.options

            for (const [key, option] of Object.entries(options)) {
                if (option.children) {
                    // Check if childKey is a direct child
                    if (Object.keys(option.children).includes(childKey)) {
                        return key
                    }

                    // Recursively search in children
                    const found = this.findParentKey(childKey, option.children, key)
                    if (found) {
                        return found
                    }
                }
            }

            return null
        }
    }
}

// Register the component globally
window.checkboxTreeFormComponent = checkboxTreeFormComponent
