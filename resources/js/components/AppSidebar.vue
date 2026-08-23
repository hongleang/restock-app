<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowLeftRight, LayoutGrid, Package, Truck } from '@lucide/vue'
import AppLogo from '@/components/AppLogo.vue'
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'
import { dashboard } from '@/routes'
import { index as products } from '@/routes/products'
import { index as stockMovements } from '@/routes/stock-movements'
import { index as suppliers } from '@/routes/suppliers'
import type { NavItem } from '@/types'

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
  },
  {
    title: 'Products',
    href: products(),
    icon: Package,
  },
  {
    title: 'Suppliers',
    href: suppliers(),
    icon: Truck,
  },
  {
    title: 'Stock movements',
    href: stockMovements(),
    icon: ArrowLeftRight,
  },
]
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="dashboard()">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
