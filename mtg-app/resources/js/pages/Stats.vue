<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import Stats from '../components/Stats.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stats',
        href: '/stats',
    },
];
</script>

<template>
    <Head title="Statistics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">            
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <stats />
            </div>
        </div>
    </AppLayout>
</template>

<script lang="ts">


export default {
    components:{
        Stats
    },
    methods: {
    }
}
</script>