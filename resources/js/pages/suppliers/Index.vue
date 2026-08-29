<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import SupplierController from '@/actions/App/Http/Controllers/SupplierController'
import Heading from '@/components/Heading.vue'
import Pagination from '@/components/Pagination.vue'
import { Button } from '@/components/ui/button'
import { index } from '@/routes/suppliers'
import type { BreadcrumbItem, Shop, Supplier } from '@/types'
import type { SimplePaginated } from '@/types/pagination'
import SupplierFilters, { type SupplierFilterProps } from '@/components/suppliers/SupplierFilter.vue'
import SupplierDialog from '@/components/suppliers/SupplierDialog.vue'

const {
  suppliers: suppliersData,
  shops,
  filters,
} = defineProps<{
  suppliers: SimplePaginated<Supplier>
  shops: Shop[]
  filters: SupplierFilterProps
}>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Suppliers', href: index() },
    ] satisfies BreadcrumbItem[],
  },
})

const suppliers = computed(() => suppliersData.data)

const dialogOpen = ref(false)
const editing = ref<Supplier | null>(null)

function openCreate() {
  editing.value = null
  dialogOpen.value = true
}

function openEdit(supplier: Supplier) {
  editing.value = supplier
  dialogOpen.value = true
}

function destroySupplier(supplier: Supplier) {
  if (
    confirm(
      `Delete ${supplier.first_name} ${supplier.last_name}? This cannot be undone.`,
    )
  ) {
    router.delete(SupplierController.destroy.url(supplier.id), {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="Suppliers" />

  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <Heading
        title="Suppliers"
        description="Manage who you order stock from"
      />
      <Button @click="openCreate">New supplier</Button>
    </div>

    <SupplierFilters :filters="filters" :shops="shops" />

    <div
      class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
    >
      <table class="w-full text-left text-sm">
        <thead
          class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
        >
          <tr>
            <th class="px-4 py-3 font-medium">Name</th>
            <th class="px-4 py-3 font-medium">Shop</th>
            <th class="px-4 py-3 font-medium">Email</th>
            <th class="px-4 py-3 font-medium">Phone</th>
            <th class="px-4 py-3 font-medium">Lead time</th>
            <th class="px-4 py-3 font-medium">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="supplier in suppliers"
            :key="supplier.id"
            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
          >
            <td class="px-4 py-3">
              <div class="font-medium">
                {{ supplier.first_name }} {{ supplier.last_name }}
              </div>
              <div
                v-if="supplier.contact_name"
                class="text-xs text-muted-foreground"
              >
                Attn: {{ supplier.contact_name }}
              </div>
            </td>
            <td class="px-4 py-3">{{ supplier.shop?.name }}</td>
            <td class="px-4 py-3">{{ supplier.email }}</td>
            <td class="px-4 py-3">{{ supplier.phone }}</td>
            <td class="px-4 py-3">{{ supplier.lead_time_days }}d</td>
            <td class="px-4 py-3 text-right">
              <Button variant="ghost" size="sm" @click="openEdit(supplier)"
                >Edit</Button
              >
              <Button
                variant="ghost"
                size="sm"
                class="text-destructive"
                @click="destroySupplier(supplier)"
              >
                Delete
              </Button>
            </td>
          </tr>
          <tr v-if="suppliers.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">
              No Suppliers Found.
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination
        :prev-page-url="suppliersData.links.prev"
        :next-page-url="suppliersData.links.next"
        :from="suppliersData.meta.from"
        :to="suppliersData.meta.to"
      />
    </div>

    <SupplierDialog :editing="editing" :shops="shops" v-model:open="dialogOpen"/>

  </div>
</template>
