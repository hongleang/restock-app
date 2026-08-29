<script setup lang="ts">
import InputError from '@/components/InputError.vue'
import { Input } from '@/components/ui/input'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Form } from '@inertiajs/vue3'
import ProductController from '@/actions/App/Http/Controllers/ProductController'
import { computed, Ref } from 'vue'
import { Product, Shop, Supplier } from '@/types'

const { editing, suppliers, selectedShopId, shops } = defineProps<{
  editing: Product | null
  selectedShopId: string
  suppliers: Supplier[]
  shops: Shop[]
}>()

const dialogOpen = defineModel<boolean>('open')

const availableSuppliers = computed(() =>
  suppliers.filter(
    (supplier) => supplier.shop_id.toString() === selectedShopId,
  ),
)
</script>

<template>
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
            <Input id="sku" name="sku" :default-value="editing?.sku" required />
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
</template>

<style scoped></style>
