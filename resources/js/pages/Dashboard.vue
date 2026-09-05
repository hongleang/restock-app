<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { dashboard } from '@/routes'
import Heading from '@/components/Heading.vue'
import { Shop } from '@/types'

type ReorderList = {
  product: {
    id: number
    name: string
    sku: string
    current_stock: number
  }
  shop: Shop
  days_until_stockout: number | null
  urgency: string
  urgency_colour: string
}

const { reorderList } = defineProps<{
  reorderList: ReorderList[]
}>()

defineOptions({
  layout: {
    breadcrumbs: [
      {
        title: 'Dashboard',
        href: dashboard(),
      },
    ],
  },
})
</script>

<template>
  <Head title="Dashboard" />

  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <Heading title="Dashboard" />
    </div>

    <div
      class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
    >
      <table class="w-full text-left text-sm">
        <thead
          class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
        >
          <tr>
            <th class="px-4 py-3 font-medium">Name</th>
            <th class="px-4 py-3 font-medium">SKU</th>
            <th class="px-4 py-3 font-medium">Current Stock</th>
            <th class="px-4 py-3 font-medium">Shop</th>
            <th class="px-4 py-3 font-medium">Day Until Stock Out</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in reorderList"
            :key="item.product.id"
            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
          >
            <td class="px-4 py-3">{{ item.product.name }}</td>
            <td class="px-4 py-3">{{ item.product.sku }}</td>
            <td class="px-4 py-3">{{ item.product.current_stock }}</td>
            <td class="px-4 py-3">{{ item.shop.name }}</td>
            <td class="px-4 py-3" :class="item.urgency_colour">{{ item.days_until_stockout }}</td>
          </tr>
          <tr v-if="reorderList.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">
              No Data Found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
