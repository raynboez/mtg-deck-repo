<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { ChartLine } from 'lucide-vue-next';
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground">
        <ChartLine color="var(--secondary)"/>
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">Statistics</span>
    </div>
</template>
