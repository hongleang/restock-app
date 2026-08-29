<script setup lang="ts">
import StockMovementController from '@/actions/App/Http/Controllers/StockMovementController'
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
import { StockMovement } from '@/types'

const { editing, products, types } = defineProps<{
  editing: StockMovement | null
  products: { id: number; name: string }[]
  types: { value: string; label: string }[]
  shops: { id: number; name: string }[]
}>()

const dialogOpen = defineModel<boolean>('open')
</script>

<template>
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
          <DialogTitle>
            {{ editing ? 'Edit stock movement' : 'New stock movement' }}
          </DialogTitle>
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
          <Label for="type">Quantity</Label>
          <Input
            id="quantity"
            name="quantity"
            :default-value="editing?.quantity ?? ''"
          />
          <InputError :message="errors.quantity" />
        </div>

        <div class="grid gap-2">
          <Label for="type">Type</Label>
          <Select name="type" :default-value="editing?.type ?? types[0]?.value">
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
</template>

<style scoped></style>
