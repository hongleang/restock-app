<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ProductController from '@/actions/App/Http/Controllers/ProductController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
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
import { index } from '@/routes/products'
import type { BreadcrumbItem, Product, Shop, Supplier } from '@/types'
import { SimplePaginated } from '@/types/pagination'

const {
  products: productsData,
  shops,
  suppliers,
} = defineProps<{
  products: SimplePaginated<Product>
  shops: Shop[]
  suppliers: Supplier[]
}>()

defineOptions({
  layout: {
    breadcrumbs: [
      { title: 'Products', href: index() },
    ] satisfies BreadcrumbItem[],
  },
})

const products = computed(() => productsData.data)

const dialogOpen = ref(false)
const editing = ref<Product | null>(null)
const selectedShopId = ref<string>('')

const availableSuppliers = computed(() =>
  suppliers.filter(
    (supplier) => supplier.shop_id.toString() === selectedShopId.value,
  ),
)

function openCreate() {
  editing.value = null
  selectedShopId.value = (shops[0]?.id ?? '').toString()
  dialogOpen.value = true
}

function openEdit(product: Product) {
  editing.value = product
  selectedShopId.value = product.shop_id.toString()
  dialogOpen.value = true
}

function destroyProduct(product: Product) {
  if (confirm(`Delete ${product.name}? This cannot be undone.`)) {
    router.delete(ProductController.destroy.url(product.id), {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="Products" />

  <div class="flex flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
      <Heading
        title="Products"
        description="Track catalog, pricing and stock levels"
      />
      <Button @click="openCreate">New product</Button>
    </div>

    <div
      class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
    >
      <table class="w-full text-left text-sm">
        <thead
          class="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border"
        >
          <tr>
            <th class="px-4 py-3 font-medium">Product</th>
            <th class="px-4 py-3 font-medium">Shop</th>
            <th class="px-4 py-3 font-medium">Supplier</th>
            <th class="px-4 py-3 font-medium">Stock</th>
            <th class="px-4 py-3 font-medium">Cost / Sell</th>
            <th class="px-4 py-3 font-medium">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in products"
            :key="product.id"
            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
          >
            <td class="px-4 py-3">
              <div class="font-medium">{{ product.name }}</div>
              <div class="text-xs text-muted-foreground">
                {{ product.sku }} &middot; {{ product.category }}
              </div>
            </td>
            <td class="px-4 py-3">{{ product.shop?.name }}</td>
            <td class="px-4 py-3">
              <span v-if="product.supplier"
                >{{ product.supplier.first_name }}
                {{ product.supplier.last_name }}</span
              >
              <span v-else class="text-muted-foreground">&mdash;</span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="
                  product.current_stock <= product.reorder_point
                    ? 'font-medium text-destructive'
                    : ''
                "
              >
                {{ product.current_stock }}
              </span>
              <span class="text-xs text-muted-foreground">
                (reorder at {{ product.reorder_point }})</span
              >
            </td>
            <td class="px-4 py-3">
              ${{ product.cost_price }} / ${{ product.sell_price }}
            </td>
            <td class="px-4 py-3 text-right">
              <Button variant="ghost" size="sm" @click="openEdit(product)"
                >Edit</Button
              >
              <Button
                variant="ghost"
                size="sm"
                class="text-destructive"
                @click="destroyProduct(product)"
              >
                Delete
              </Button>
            </td>
          </tr>
          <tr v-if="products.length === 0">
            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">
              No products yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Dialog v-model:open="dialogOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto">
        <Form
          :key="editing?.id ?? 'new'"
          v-bind="
            editing
              ? ProductController.update.form(editing.id)
              : ProductController.store.form()
          "
          class="space-y-4"
          @success="dialogOpen = false"
          v-slot="{ errors, processing }"
        >
          <DialogHeader>
            <DialogTitle>{{
              editing ? 'Edit product' : 'New product'
            }}</DialogTitle>
          </DialogHeader>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="shop_id">Shop</Label>
              <Select
                name="shop_id"
                :model-value="selectedShopId"
                @update:model-value="
                  (value) => (selectedShopId = value as string)
                "
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
            <div class="grid gap-2">
              <Label for="supplier_id">Supplier</Label>
              <Select
                name="supplier_id"
                :default-value="editing?.supplier_id?.toString()"
              >
                <SelectTrigger id="supplier_id" class="w-full">
                  <SelectValue placeholder="No supplier" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="supplier in availableSuppliers"
                    :key="supplier.id"
                    :value="supplier.id.toString()"
                  >
                    {{ supplier.first_name }} {{ supplier.last_name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <InputError :message="errors.supplier_id" />
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
              id="name"
              name="name"
              :default-value="editing?.name"
              required
            />
            <InputError :message="errors.name" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="sku">SKU</Label>
              <Input
                id="sku"
                name="sku"
                :default-value="editing?.sku"
                required
              />
              <InputError :message="errors.sku" />
            </div>
            <div class="grid gap-2">
              <Label for="barcode">Barcode</Label>
              <Input
                id="barcode"
                name="barcode"
                :default-value="editing?.barcode"
                required
              />
              <InputError :message="errors.barcode" />
            </div>
          </div>

          <div class="grid gap-2">
            <Label for="category">Category</Label>
            <Input
              id="category"
              name="category"
              :default-value="editing?.category"
              required
            />
            <InputError :message="errors.category" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label for="cost_price">Cost price</Label>
              <Input
                id="cost_price"
                type="number"
                step="0.01"
                min="0"
                name="cost_price"
                :default-value="editing?.cost_price"
                required
              />
              <InputError :message="errors.cost_price" />
            </div>
            <div class="grid gap-2">
              <Label for="sell_price">Sell price</Label>
              <Input
                id="sell_price"
                type="number"
                step="0.01"
                min="0"
                name="sell_price"
                :default-value="editing?.sell_price"
                required
              />
              <InputError :message="errors.sell_price" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
              <Label for="current_stock">Current stock</Label>
              <Input
                id="current_stock"
                type="number"
                min="0"
                name="current_stock"
                :default-value="editing?.current_stock ?? 0"
                required
              />
              <InputError :message="errors.current_stock" />
            </div>
            <div class="grid gap-2">
              <Label for="reorder_point">Reorder point</Label>
              <Input
                id="reorder_point"
                type="number"
                min="0"
                name="reorder_point"
                :default-value="editing?.reorder_point ?? 0"
                required
              />
              <InputError :message="errors.reorder_point" />
            </div>
            <div class="grid gap-2">
              <Label for="reorder_qty">Reorder qty</Label>
              <Input
                id="reorder_qty"
                type="number"
                min="0"
                name="reorder_qty"
                :default-value="editing?.reorder_qty ?? 0"
                required
              />
              <InputError :message="errors.reorder_qty" />
            </div>
          </div>

          <DialogFooter>
            <Button type="submit" :disabled="processing">
              {{ editing ? 'Save changes' : 'Create product' }}
            </Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
  </div>
</template>
