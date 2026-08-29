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
import SupplierController from '@/actions/App/Http/Controllers/SupplierController'
import { Shop, Supplier } from '@/types'

const { editing, shops } = defineProps<{
  editing: Supplier | null
  shops: Shop[]
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
</template>
