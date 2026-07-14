<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import WarhammerAppSidebar from '@/components/WarhammerAppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const currentUrl = computed(() => new URL(page.url, window.location.origin));
const isWarhammerMode = computed(() => currentUrl.value.pathname.startsWith('/warhammer'));
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar v-if="!isWarhammerMode" />
        <WarhammerAppSidebar v-else />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>