export default function checkboxTreeFormComponent({ state, options, indeterminateItems }) {
    return {
        state: state,
        options: options,
        indeterminateItems: indeterminateItems,

        init() {
            // Watch for external state changes
            this.$watch('state', () => {
                this.updateIndeterminateStates()
            })
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
                // Add parent and all children
                const keysToAdd = [parentKey, ...childrenKeys].filter(
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
                // All children checked, add parent
                this.state = [...this.state, parentKey]
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
