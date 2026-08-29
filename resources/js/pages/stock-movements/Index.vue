<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import StockMovementController from '@/actions/App/Http/Controllers/StockMovementController'
import Heading from '@/components/Heading.vue'
import Pagination from '@/components/Pagination.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { index } from '@/routes/stock-movements'
import type {
  BreadcrumbItem,
  Product,
  Shop,
  StockMovement,
  StockMovementTypeOption,
} from '@/types'
import type { SimplePaginated } from '@/types/pagination'
import StockMovementFilter, {
  type StockMovementFilterProps,
} from '@/components/stock-movements/StockMovementFilter.vue'
import StockMovementDialog from '@/components/stock-movements/StockMovementDialog.vue'
import StockMovementImportDialog from '@/components/stock-movements/StockMovementImportDialog.vue'

const {
  movements: movementsData,
  products,
  types,
  shops,
  filters,
} = defineProps<{
  movements: SimplePaginated<StockMovement>
  products: Product[]
  types: StockMovementTypeOption[]
  shops: Shop[]
  filters: StockMovementFilterProps
}>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Stock movements', href: index() },
    ] satisfies BreadcrumbItem[],
  },
})

const movements = computed(() => movementsData.data)

const badgeVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
  restock: 'default',
  sale: 'secondary',
  adjustment: 'destructive',
}

const dialogOpen = ref(false)
const editing = ref<StockMovement | null>(null)

const importDialogOpen = ref(false)

function openCreate() {
  editing.value = null
  dialogOpen.value = true
}

function openEdit(movement: StockMovement) {
  editing.value = movement
  dialogOpen.value = true
}

function destroyMovement(movement: StockMovement) {
  if (confirm('Delete this stock movement? This cannot be undone.')) {
    router.delete(StockMovementController.destroy.url(movement.id), {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="Stock movements" />

  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <Heading
        title="Stock movements"
        description="History of sales, restocks and adjustments"
      />
      <div class="flex gap-2">
        <Button variant="outline" @click="importDialogOpen = true">
          Import CSV
        </Button>
        <Button @click="openCreate">New movement</Button>
      </div>
    </div>

    <StockMovementFilter
      :filters="filters"
      :products="products"
      :types="types"
      :shops="shops"
    />

    <div
      class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
    >
      <table class="w-full text-left text-sm">
        <thead
          class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
        >
          <tr>
            <th class="px-4 py-3 font-medium">Date</th>
            <th class="px-4 py-3 font-medium">Product</th>
            <th class="px-4 py-3 font-medium">Quantity</th>
            <th class="px-4 py-3 font-medium">Type</th>
            <th class="px-4 py-3 font-medium">Note</th>
            <th class="px-4 py-3 font-medium">By</th>
            <th class="px-4 py-3 font-medium">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="movement in movements"
            :key="movement.id"
            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
          >
            <td class="px-4 py-3 text-muted-foreground">
              {{ new Date(movement.created_at).toLocaleString() }}
            </td>
            <td class="px-4 py-3 font-medium">{{ movement.product?.name }}</td>
            <td class="px-4 py-3">{{ movement.quantity }}</td>
            <td class="px-4 py-3">
              <Badge :variant="badgeVariant[movement.type]">
                {{ movement.type_label }}
              </Badge>
            </td>
            <td class="px-4 py-3 text-muted-foreground">
              {{ movement.note ?? '—' }}
            </td>
            <td class="px-4 py-3">
              {{ movement.user ? movement.user?.name : 'System' }}
            </td>
            <td class="px-4 py-3 text-right">
              <Button variant="ghost" size="sm" @click="openEdit(movement)"
                >Edit</Button
              >
              <Button
                variant="ghost"
                size="sm"
                class="text-destructive"
                @click="destroyMovement(movement)"
              >
                Delete
              </Button>
            </td>
          </tr>
          <tr v-if="movements.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">
              No Stocks found.
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination
        :prev-page-url="movementsData.links.prev"
        :next-page-url="movementsData.links.next"
        :from="movementsData.meta.from"
        :to="movementsData.meta.to"
      />
    </div>

    <StockMovementImportDialog v-model:open="importDialogOpen" :shops="shops" />

    <StockMovementDialog
      v-model:open="dialogOpen"
      :editing="editing"
      :products="products"
      :types="types"
      :shops="shops"
    />
  </div>
</template>
