<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import DeckImport from '../components/DeckImport.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deck Import',
        href: '/deck_import',
    },
];
</script>

<template>
    <Head title="Deck Import" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">            
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <deck-import @deck-imported="handleDeckImported" />
            </div>
        </div>
    </AppLayout>
</template>

<script lang="ts">


export default {
    components:{
        DeckImport
    },
    methods: {
        handleDeckImported(deck: any){
        }
    }
}
</script>
