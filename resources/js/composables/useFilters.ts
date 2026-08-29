import { useDebounceFn } from '@vueuse/core'
import { computed, reactive, ref, watch } from 'vue'

type FilterValue = string | number | boolean | null

function isFilterActive(key: string, value: FilterValue): boolean {
  if (value === null || value === undefined) {
    return false
  }

  if (typeof value === 'boolean') {
    return value
  }

  if (typeof value === 'string') {
    return key === 'search' ? value !== '' : value !== '' && value !== 'all'
  }

  return true
}

export function useFilters<T extends Record<string, FilterValue>>({
  initialState,
  applyFilters,
}: {
  initialState: T
  applyFilters: () => void
}) {
  const filters = reactive({ ...initialState }) as T
  const hasSearch = Object.prototype.hasOwnProperty.call(initialState, 'search')
  const otherFilterKeys = (Object.keys(initialState) as (keyof T)[]).filter(
    (key) => key !== 'search',
  )

  const hasActiveFilters = computed(() =>
    (Object.keys(filters) as (keyof T)[]).some((key) =>
      isFilterActive(key as string, filters[key]),
    ),
  )

  const debouncedApplyFilters = useDebounceFn(applyFilters, 300)

  if (hasSearch) {
    watch(() => filters.search, debouncedApplyFilters)
  }

  if (otherFilterKeys.length > 0) {
    watch(
      () => otherFilterKeys.map((key) => filters[key]),
      applyFilters,
      { deep: true },
    )
  }

  function clearFilters() {
    for (const key of Object.keys(filters) as (keyof T)[]) {
      if (key === 'search') {
        filters[key] = '' as T[keyof T]
        continue
      }

      const current = filters[key]

      if (typeof current === 'boolean') {
        filters[key] = false as T[keyof T]
      } else if (typeof current === 'string') {
        filters[key] = 'all' as T[keyof T]
      } else {
        filters[key] = null as T[keyof T]
      }
    }
  }

  return {
    filters,
    hasActiveFilters,
    clearFilters,
  }
}
