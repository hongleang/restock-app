<script setup lang="ts">
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
import { importMethod } from '@/routes/stock-movements'
import { Button } from '@/components/ui/button'
import { Form } from '@inertiajs/vue3'
import { Shop } from '@/types'

const { shops } = defineProps<{ shops: Shop[] }>()

const dialogOpen = defineModel<boolean>('open')
</script>

<template>
  <Dialog v-model:open="dialogOpen">
    <DialogContent>
      <Form
        v-bind="importMethod.form()"
        class="space-y-4"
        reset-on-success
        @success="dialogOpen = false"
        v-slot="{ errors, processing }"
      >
        <DialogHeader>
          <DialogTitle>Import stock movements</DialogTitle>
        </DialogHeader>

        <div class="grid gap-2">
          <Label for="import-shop_id">Shop</Label>
          <Select name="shop_id" :default-value="shops[0]?.id?.toString()">
            <SelectTrigger id="import-shop_id" class="w-full">
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
        </div>

        <div class="grid gap-2">
          <Label for="import-file">CSV file</Label>
          <input
            id="import-file"
            type="file"
            name="file"
            accept=".csv,text/csv"
            required
            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs outline-none file:mr-3 file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground md:text-sm"
          />
          <p class="text-xs text-muted-foreground">
            Columns: <code>sku</code>, <code>type</code> (sale, restock or
            adjustment) — required. <code>note</code> and <code>date</code>
            are optional. Products are matched by SKU within the selected shop.
          </p>
          <span
            v-if="errors"
            v-for="err in errors"
            class="text-sm text-red-600"
          >
            {{ err }}
          </span>
        </div>

        <DialogFooter>
          <Button type="submit" :disabled="processing">
            {{ processing ? 'Importing…' : 'Import' }}
          </Button>
        </DialogFooter>
      </Form>
    </DialogContent>
  </Dialog>
</template>

<style scoped></style>
