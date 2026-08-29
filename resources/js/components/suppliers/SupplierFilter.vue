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
import { index } from '@/routes/suppliers'
import { Shop } from '@/types'

export type SupplierFilterProps = {
  search: string | null
  shop_id: number | null
}

const props = defineProps<{
  filters: SupplierFilterProps
  shops: Shop[]
}>()

const { filters, clearFilters, hasActiveFilters } = useFilters({
  initialState: {
    search: props.filters.search ?? null,
    shop: props.filters.shop_id ? props.filters.shop_id.toString() : null,
  },
  applyFilters,
})

function applyFilters() {
  router.get(
    index.url(),
    {
      search: filters.search || undefined,
      shop: filters.shop || undefined,
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
        placeholder="Name, contact or email"
        class="w-56"
      />
    </div>

    <div class="grid gap-1.5">
      <Label for="filter-shop">Shop</Label>
      <Select v-model="filters.shop">
        <SelectTrigger id="filter-shop" class="w-40">
          <SelectValue placeholder="All shops" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All shops</SelectItem>
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
