function c({state:o,options:a,indeterminateItems:f,searchable:u=!1,expandable:g=!1,defaultExpanded:p=!1,bulkToggleable:m=!1}){return{state:o,options:a,indeterminateItems:f,searchable:u,expandable:g,defaultExpanded:p,bulkToggleable:m,searchQuery:"",filteredOptions:a,expandedNodes:{},init(){this.$watch("state",()=>{this.updateIndeterminateStates()}),this.filteredOptions=this.options,this.expandable&&this.initializeExpandedState(this.options)},initializeExpandedState(e,t=null){let n=t!==null?t:this.defaultExpanded;Object.entries(e).forEach(([i,s])=>{s.children&&Object.keys(s.children).length>0&&(this.expandedNodes[i]=n,this.initializeExpandedState(s.children,n))})},toggleExpand(e){this.expandedNodes[e]=!this.expandedNodes[e]},isExpanded(e){return this.expandedNodes[e]!==!1},isChecked(e){return Array.isArray(this.state)&&this.state.includes(e)},isParentChecked(e){let t=this.getChildrenKeys(e);return t.length===0?this.isChecked(e):t.every(n=>this.isChecked(n))},isIndeterminate(e){let t=this.getChildrenKeys(e);if(t.length===0)return!1;let n=t.filter(i=>this.isChecked(i));return n.length>0&&n.length<t.length},toggleParent(e){let t=this.getChildrenKeys(e),n=!this.isParentChecked(e);if(Array.isArray(this.state)||(this.state=[]),n){let i=[e,...t].filter(s=>!this.state.includes(s));this.state=[...this.state,...i]}else{let i=[e,...t];this.state=this.state.filter(s=>!i.includes(s))}this.updateIndeterminateStates()},toggleChild(e){Array.isArray(this.state)||(this.state=[]),this.isChecked(e)?this.state=this.state.filter(n=>n!==e):this.state=[...this.state,e],this.updateParentState(e),this.updateIndeterminateStates()},updateParentState(e){let t=this.findParentKey(e);if(!t)return;let n=this.getChildrenKeys(t),i=n.every(r=>this.isChecked(r)),s=n.some(r=>this.isChecked(r)),h=this.isChecked(t);i&&!h?this.state=[...this.state,t]:!s&&h&&(this.state=this.state.filter(r=>r!==t)),this.updateParentState(t)},updateIndeterminateStates(){this.indeterminateItems=this.calculateIndeterminateItems(this.options)},calculateIndeterminateItems(e){let t=[];return Object.entries(e).forEach(([n,i])=>{if(i.children){let s=this.getChildrenKeysFromOption(i.children),h=s.filter(d=>this.isChecked(d));h.length>0&&h.length<s.length&&t.push(n);let r=this.calculateIndeterminateItems(i.children);t.push(...r)}}),t},getChildrenKeys(e){return this.findChildrenKeysInOptions(this.options,e)},findChildrenKeysInOptions(e,t){for(let[n,i]of Object.entries(e)){if(n===t&&i.children)return this.getChildrenKeysFromOption(i.children);if(i.children){let s=this.findChildrenKeysInOptions(i.children,t);if(s.length>0)return s}}return[]},getChildrenKeysFromOption(e){let t=[];return Object.entries(e).forEach(([n,i])=>{t.push(n),i.children&&t.push(...this.getChildrenKeysFromOption(i.children))}),t},findParentKey(e,t=null,n=null){t=t||this.options;for(let[i,s]of Object.entries(t))if(s.children){if(Object.keys(s.children).includes(e))return i;let h=this.findParentKey(e,s.children,i);if(h)return h}return null},filterOptions(){if(!this.searchQuery||this.searchQuery.trim()===""){this.filteredOptions=this.options;return}let e=this.searchQuery.toLowerCase();this.filteredOptions=this.filterTree(this.options,e)},filterTree(e,t){let n={};return Object.entries(e).forEach(([i,s])=>{let h=typeof s=="string"?s:s.label||"",r=s.children&&Object.keys(s.children).length>0,d=h.toLowerCase().includes(t),l={};r&&(l=this.filterTree(s.children,t)),(d||Object.keys(l).length>0)&&(n[i]=typeof s=="string"?s:{...s,children:l})}),n},renderTreeItem(e,t,n){let i=n*1.5,s=t.children&&Object.keys(t.children).length>0,h=typeof t=="string"?t:t.label||e,r=`
                <div class="filament-forms-checkbox-tree-item">
                    <div class="flex items-center gap-x-2" style="padding-left: ${i}rem;">
            `;return this.expandable&&s?r+=`
                    <button
                        type="button"
                        @click="toggleExpand('${e}')"
                        class="flex items-center justify-center w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg
                            x-show="isExpanded('${e}')"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        <svg
                            x-show="!isExpanded('${e}')"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                `:this.expandable&&(r+='<div class="w-5"></div>'),r+=`
                        <input
                            type="checkbox"
                            value="${e}"
                            id="checkbox-${e}"
                            ${s?`@change="toggleParent('${e}')"
                                 x-bind:checked="isParentChecked('${e}')"
                                 x-bind:indeterminate="isIndeterminate('${e}')"`:`@change="toggleChild('${e}')"
                                 x-bind:checked="isChecked('${e}')"`}
                            class="filament-forms-checkbox-list-component-option-checkbox rounded border-gray-300 text-primary-600 shadow-sm focus:ring focus:ring-primary-500 focus:ring-opacity-50 focus:ring-offset-0 disabled:cursor-not-allowed disabled:opacity-70 dark:border-gray-600 dark:bg-gray-700 dark:checked:border-primary-600 dark:checked:bg-primary-600 dark:focus:ring-primary-600"
                        />
                        <label for="checkbox-${e}" class="text-sm font-medium leading-6 text-gray-950 dark:text-white cursor-pointer ${s?"font-semibold":""}">
                            ${h}
                        </label>
                    </div>
            `,s&&(r+=`<div class="mt-2 space-y-2" x-show="isExpanded('${e}')">`,Object.entries(t.children).forEach(([d,l])=>{r+=this.renderTreeItem(d,l,n+1)}),r+="</div>"),r+="</div>",r},selectAll(){let e=this.getAllKeys(this.options);this.state=e,this.updateIndeterminateStates()},deselectAll(){this.state=[],this.updateIndeterminateStates()},getAllKeys(e){let t=[];return Object.entries(e).forEach(([n,i])=>{t.push(n),i.children&&Object.keys(i.children).length>0&&t.push(...this.getAllKeys(i.children))}),t}}}window.checkboxTreeFormComponent=c;export{c as default};
