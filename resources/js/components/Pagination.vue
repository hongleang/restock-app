<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'

defineProps<{
  prevPageUrl: string | null | undefined
  nextPageUrl: string | null | undefined
  from: number | null | undefined
  to: number | null | undefined
}>()
</script>

<template>
  <div
    class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3 text-sm dark:border-sidebar-border"
  >
    <p class="text-muted-foreground">
      <template v-if="from && to">Showing {{ from }}–{{ to }}</template>
      <template v-else>No results</template>
    </p>
    <div class="flex gap-2">
      <Link
        v-if="prevPageUrl"
        :href="prevPageUrl"
        preserve-scroll
        preserve-state
      >
        <Button variant="outline" size="sm">Previous</Button>
      </Link>
      <Button v-else variant="outline" size="sm" disabled>Previous</Button>

      <Link
        v-if="nextPageUrl"
        :href="nextPageUrl"
        preserve-scroll
        preserve-state
      >
        <Button variant="outline" size="sm">Next</Button>
      </Link>
      <Button v-else variant="outline" size="sm" disabled>Next</Button>
    </div>
  </div>
</template>
