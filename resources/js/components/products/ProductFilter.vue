<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { useFilters } from '@/composables/useFilters'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/products'
import { Shop, Supplier } from '@/types'

export type ProductFilterProps = {
  search: string | null
  shop_id: number | null
  category: string | null
  supplier_id: number | null
  low_stock: boolean
}

const props = defineProps<{
  filters: ProductFilterProps
  shops: Shop[]
  categories: string[]
  suppliers: Supplier[]
}>()

const { filters, clearFilters, hasActiveFilters } = useFilters({
  initialState: {
    search: props.filters.search ?? null,
    shop: props.filters.shop_id ? props.filters.shop_id.toString() : null,
    category: props.filters.category ?? '',
    supplier: props.filters.supplier_id
      ? props.filters.supplier_id.toString()
      : '',
    lowStockOnly: props.filters.low_stock ?? false,
  },
  applyFilters,
})

function applyFilters() {
  router.get(
    index.url(),
    {
      search: filters.search || undefined,
      shop: filters.shop || undefined,
      category: filters.category || undefined,
      supplier: filters.supplier || undefined,
      low_stock: filters.lowStockOnly || false,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  )
}
</script>

<template>
  <div class="flex flex-wrap items-end gap-3">
    <div class="grid gap-1.5">
      <Label for="filter-search">Search</Label>
      <Input
        id="filter-search"
        v-model="filters.search"
        placeholder="Name or SKU"
        class="w-48"
      />
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-shop">Shop</Label>
      <Select v-model="filters.shop">
        <SelectTrigger id="filter-shop" class="w-40">
          <SelectValue placeholder="All shops" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem :value="null">All shops</SelectItem>
          <SelectItem
            v-for="shop in shops"
            :key="shop.id"
            :value="shop.id.toString()"
          >
            {{ shop.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-category">Category</Label>
      <Select v-model="filters.category">
        <SelectTrigger id="filter-category" class="w-40">
          <SelectValue placeholder="All categories" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem :value="null">All categories</SelectItem>
          <SelectItem
            v-for="category in categories"
            :key="category"
            :value="category"
          >
            {{ category }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-supplier">Supplier</Label>
      <Select v-model="filters.supplier">
        <SelectTrigger id="filter-supplier" class="w-40">
          <SelectValue placeholder="All suppliers" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All suppliers</SelectItem>
          <SelectItem
            v-for="supplier in suppliers"
            :key="supplier.id"
            :value="supplier.id.toString()"
          >
            {{ supplier.first_name }} {{ supplier.last_name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <label class="flex items-center gap-2 pb-2 text-sm">
      <Checkbox v-model:checked="filters.lowStockOnly" />
      Low stock only
    </label>

    <Button
      v-if="hasActiveFilters"
      variant="ghost"
      size="sm"
      @click="clearFilters"
    >
      Clear filters
    </Button>
  </div>
</template>

<style scoped></style>
