<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import StockMovementController from '@/actions/App/Http/Controllers/StockMovementController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
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
import { index } from '@/routes/stock-movements'
import type {
  BreadcrumbItem,
  Product,
  StockMovement,
  StockMovementTypeOption,
} from '@/types'
import { SimplePaginated } from '@/types/pagination'

const {
  movements: movementsData,
  products,
  types,
} = defineProps<{
  movements: SimplePaginated<StockMovement>
  products: Product[]
  types: StockMovementTypeOption[]
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
      <Button @click="openCreate">New movement</Button>
    </div>

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
            <td class="px-4 py-3">
              <Badge :variant="badgeVariant[movement.type]">{{
                movement.type
              }}</Badge>
            </td>
            <td class="px-4 py-3 text-muted-foreground">
              {{ movement.note ?? '—' }}
            </td>
            <td class="px-4 py-3">
              {{
                movement.user
                  ? `${movement.user.first_name} ${movement.user.last_name}`
                  : 'System'
              }}
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
              No stock movements yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Dialog v-model:open="dialogOpen">
      <DialogContent>
        <Form
          :key="editing?.id ?? 'new'"
          v-bind="
            editing
              ? StockMovementController.update.form(editing.id)
              : StockMovementController.store.form()
          "
          class="space-y-4"
          @success="dialogOpen = false"
          v-slot="{ errors, processing }"
        >
          <DialogHeader>
            <DialogTitle>{{
              editing ? 'Edit stock movement' : 'New stock movement'
            }}</DialogTitle>
          </DialogHeader>

          <div v-if="!editing" class="grid gap-2">
            <Label for="product_id">Product</Label>
            <Select name="product_id">
              <SelectTrigger id="product_id" class="w-full">
                <SelectValue placeholder="Select a product" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="product in products"
                  :key="product.id"
                  :value="product.id.toString()"
                >
                  {{ product.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="errors.product_id" />
          </div>
          <div v-else class="grid gap-2">
            <Label>Product</Label>
            <p class="text-sm text-muted-foreground">
              {{ editing.product?.name }}
            </p>
          </div>

          <div class="grid gap-2">
            <Label for="type">Type</Label>
            <Select
              name="type"
              :default-value="editing?.type ?? types[0]?.value"
            >
              <SelectTrigger id="type" class="w-full">
                <SelectValue placeholder="Select a type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="type in types"
                  :key="type.value"
                  :value="type.value"
                >
                  {{ type.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="errors.type" />
          </div>

          <div class="grid gap-2">
            <Label for="note">Note</Label>
            <Input
              id="note"
              name="note"
              :default-value="editing?.note ?? ''"
              placeholder="Optional"
            />
            <InputError :message="errors.note" />
          </div>

          <DialogFooter>
            <Button type="submit" :disabled="processing">
              {{ editing ? 'Save changes' : 'Record movement' }}
            </Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
  </div>
</template>
