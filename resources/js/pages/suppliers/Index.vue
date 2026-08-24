<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import { computed, ref, watch } from 'vue'
import SupplierController from '@/actions/App/Http/Controllers/SupplierController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import Pagination from '@/components/Pagination.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { index } from '@/routes/suppliers'
import type { BreadcrumbItem, Shop, Supplier } from '@/types'
import { SimplePaginated } from '@/types/pagination'

type SupplierFilters = {
  search: string | null
  shop_id: number | null
}

const {
  suppliers: suppliersData,
  shops,
  filters,
} = defineProps<{
  suppliers: SimplePaginated<Supplier>
  shops: Shop[]
  filters: SupplierFilters
}>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Suppliers', href: index() },
    ] satisfies BreadcrumbItem[],
  },
})

const suppliers = computed(() => suppliersData.data)

const search = ref(filters.search ?? '')
const shopFilter = ref(filters.shop_id ? filters.shop_id.toString() : 'all')

const hasActiveFilters = computed(() => search.value !== '' || shopFilter.value !== 'all')

function applyFilters() {
  router.get(
    index.url(),
    {
      search: search.value || undefined,
      shop_id: shopFilter.value !== 'all' ? shopFilter.value : undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  )
}

const debouncedApplyFilters = useDebounceFn(applyFilters, 300)

watch(search, debouncedApplyFilters)
watch(shopFilter, applyFilters)

function clearFilters() {
  search.value = ''
  shopFilter.value = 'all'
}

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

    <div class="flex flex-wrap items-end gap-3">
      <div class="grid gap-1.5">
        <Label for="filter-search">Search</Label>
        <Input
          id="filter-search"
          v-model="search"
          placeholder="Name, contact or email"
          class="w-56"
        />
      </div>

      <div class="grid gap-1.5">
        <Label for="filter-shop">Shop</Label>
        <Select v-model="shopFilter">
          <SelectTrigger id="filter-shop" class="w-40">
            <SelectValue placeholder="All shops" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All shops</SelectItem>
            <SelectItem v-for="shop in shops" :key="shop.id" :value="shop.id.toString()">
              {{ shop.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
        Clear filters
      </Button>
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
              {{ hasActiveFilters ? 'No suppliers match your filters.' : 'No suppliers yet.' }}
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination
        :prev-page-url="suppliersData.prev_page_url"
        :next-page-url="suppliersData.next_page_url"
        :from="suppliersData.from"
        :to="suppliersData.to"
      />
    </div>

    <Dialog v-model:open="dialogOpen">
      <DialogContent>
        <Form
          :key="editing?.id ?? 'new'"
          v-bind="
            editing
              ? SupplierController.update.form(editing.id)
              : SupplierController.store.form()
          "
          class="space-y-4"
          @success="dialogOpen = false"
          v-slot="{ errors, processing }"
        >
          <DialogHeader>
            <DialogTitle>{{
              editing ? 'Edit supplier' : 'New supplier'
            }}</DialogTitle>
          </DialogHeader>

          <div class="grid gap-2">
            <Label for="shop_id">Shop</Label>
            <Select
              name="shop_id"
              :default-value="(editing?.shop_id ?? shops[0]?.id)?.toString()"
            >
              <SelectTrigger id="shop_id" class="w-full">
                <SelectValue placeholder="Select a shop" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="shop in shops"
                  :key="shop.id"
                  :value="shop.id.toString()"
                >
                  {{ shop.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="errors.shop_id" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="first_name">First name</Label>
              <Input
                id="first_name"
                name="first_name"
                :default-value="editing?.first_name"
                required
              />
              <InputError :message="errors.first_name" />
            </div>
            <div class="grid gap-2">
              <Label for="last_name">Last name</Label>
              <Input
                id="last_name"
                name="last_name"
                :default-value="editing?.last_name"
                required
              />
              <InputError :message="errors.last_name" />
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="contact_name">Contact name</Label>
            <Input
              id="contact_name"
              name="contact_name"
              :default-value="editing?.contact_name ?? ''"
            />
            <InputError :message="errors.contact_name" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="email">Email</Label>
              <Input
                id="email"
                type="email"
                name="email"
                :default-value="editing?.email"
                required
              />
              <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
              <Label for="phone">Phone</Label>
              <Input
                id="phone"
                name="phone"
                :default-value="editing?.phone"
                required
              />
              <InputError :message="errors.phone" />
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="lead_time_days">Lead time (days)</Label>
            <Input
              id="lead_time_days"
              type="number"
              min="1"
              max="365"
              name="lead_time_days"
              :default-value="editing?.lead_time_days ?? 3"
              required
            />
            <InputError :message="errors.lead_time_days" />
          </div>

          <DialogFooter>
            <Button type="submit" :disabled="processing">
              {{ editing ? 'Save changes' : 'Create supplier' }}
            </Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
  </div>
</template>
