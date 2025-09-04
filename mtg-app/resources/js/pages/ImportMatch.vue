<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import MatchImport from '../components/MatchImport.vue'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Match Import',
        href: '/match_import',
    },
];
</script>

<template>
    <Head title="Match Import" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">            
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <match-import />
            </div>
        </div>
    </AppLayout>
</template>

<script lang="ts">


export default {
    components:{
        MatchImport
    },
    methods: {
    }
}
</script>
