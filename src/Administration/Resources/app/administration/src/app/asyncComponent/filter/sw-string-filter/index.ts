import type { PropType } from 'vue';
import template from './sw-string-filter.html.twig';
import './sw-string-filter.scss';

const { Criteria } = Shopware.Data;

export type PropCriteriaFilterType = PropType<'contains' | 'equals' | 'equalsAny' | 'prefix' | 'suffix'>;

export const criteriaFilterTypes = [
    'contains',
    'equals',
    'equalsAny',
    'prefix',
    'suffix',
];

/**
 * @private
 */
export default Shopware.Component.wrapComponentConfig({
    template,

    compatConfig: Shopware.compatConfig,

    props: {
        filter: {
            type: Object,
            required: true,
        },
        active: {
            type: Boolean,
            required: true,
        },
        criteriaFilterType: {
            type: String as PropCriteriaFilterType,
            required: false,
            default: 'contains',
            validValues: criteriaFilterTypes,
            validator(value: string): boolean {
                return criteriaFilterTypes.includes(value);
            },
        },
        criteriaFilterTypeEditable: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    data() {
        return {
            filterType: this.criteriaFilterType,
            filterTypeOptions: criteriaFilterTypes,
        };
    },

    methods: {
        updateFilter(newValue: string): void {
            if (!newValue || typeof this.filter.property !== 'string') {
                this.resetFilter();

                return;
            }

            let filterType = this.criteriaFilterTypeEditable ? this.filterType : this.criteriaFilterType;

            let filterValue : string|string[] = newValue;

            if(filterType === 'equalsAny') {
                filterValue = filterValue.split(' ').map(e => e.trim());
            }

            const filterCriteria = [
                Criteria[filterType](this.filter.property, filterValue),
            ];

            this.$emit('filter-update', this.filter.name, filterCriteria, newValue);
        },

        onFilterTypeChanged(newFilterType: string) {
            this.filterType = newFilterType;
            this.updateFilter(this.filter.value);
        },

        resetFilter(): void {
            this.$emit('filter-reset', this.filter.name);
        },
    },
});
