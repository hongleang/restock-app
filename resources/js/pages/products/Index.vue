<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import ProductController from '@/actions/App/Http/Controllers/ProductController'
import Heading from '@/components/Heading.vue'
import Pagination from '@/components/Pagination.vue'
import { Button } from '@/components/ui/button'
import { index } from '@/routes/products'
import type { BreadcrumbItem, Product, Shop, Supplier } from '@/types'
import type { SimplePaginated } from '@/types/pagination'
import ProductFilter, {
  type ProductFilterProps,
} from '@/components/products/ProductFilter.vue'
import ProductDialog from '@/components/products/ProductDialog.vue'

const {
  products: productsData,
  shops,
  suppliers,
  filters,
} = defineProps<{
  products: SimplePaginated<Product>
  shops: Shop[]
  suppliers: Supplier[]
  categories: string[]
  filters: ProductFilterProps
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

    <ProductFilter
      :filters="filters"
      :shops="shops"
      :suppliers="suppliers"
      :categories="categories"
    />

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
              <span v-if="product.supplier">
                {{ product.supplier.first_name }}
                {{ product.supplier.last_name }}
              </span>
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
              No products found.
            </td>
          </tr>
        </tbody>
      </table>

      <Pagination
        :prev-page-url="productsData.links.prev"
        :next-page-url="productsData.links.next"
        :from="productsData.meta.from"
        :to="productsData.meta.to"
      />
    </div>

    <ProductDialog
      v-model:open="dialogOpen"
      :editing="editing"
      :shops="shops"
      :suppliers="suppliers"
      :selected-shop-id="selectedShopId"
    />
  </div>
</template>
