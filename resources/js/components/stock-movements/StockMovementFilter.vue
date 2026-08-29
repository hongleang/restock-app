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

import { useFilters } from '@/composables/useFilters'
import { router } from '@inertiajs/vue3'
import { index } from '@/routes/stock-movements'
import { Product, type Shop, type StockMovementTypeOption } from '@/types'

export type StockMovementFilterProps = {
  product_id: number | null
  type: string | null
  from: string | null
  to: string | null
}

const props = defineProps<{
  filters: StockMovementFilterProps
  products: Product[]
  types: StockMovementTypeOption[]
  shops: Shop[]
}>()

const { filters, clearFilters, hasActiveFilters } = useFilters({
  initialState: {
    product: props.filters.product_id
      ? props.filters.product_id.toString()
      : 'all',
    type: props.filters.type ? props.filters.type : 'all',
    from: props.filters.from,
    to: props.filters.to,
  },
  applyFilters,
})

function applyFilters() {
  router.get(
    index.url(),
    {
      product: filters.product || undefined,
      type: filters.type || undefined,
      from: filters.from || undefined,
      to: filters.to || undefined,
    },
    {
      preserveState: true,
    },
  )
}
</script>

<template>
  <div class="flex flex-wrap items-end gap-3">
    <div class="grid gap-1.5">
      <Label for="filter-product">Product</Label>
      <Select v-model="filters.product">
        <SelectTrigger id="filter-product" class="w-48">
          <SelectValue placeholder="All products" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All products</SelectItem>
          <SelectItem
            v-for="product in products"
            :key="product.id"
            :value="product.id.toString()"
          >
            {{ product.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-type">Type</Label>
      <Select v-model="filters.type">
        <SelectTrigger id="filter-type" class="w-40">
          <SelectValue placeholder="All types" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All types</SelectItem>
          <SelectItem
            v-for="type in types"
            :key="type.value"
            :value="type.value"
          >
            {{ type.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-from">From</Label>
      <Input id="filter-from" v-model="filters.from" type="date" class="w-40" />
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-to">To</Label>
      <Input id="filter-to" v-model="filters.to" type="date" class="w-40" />
    </div>

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
