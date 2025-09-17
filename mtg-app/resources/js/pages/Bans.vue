<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import Stats from '../components/Stats.vue';
import Banlist from '@/components/Banlist.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bans',
        href: '/bans',
    },
];
</script>

<template>
    <Head title="Ban List" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">            
                <Banlist />
        </div>
    </AppLayout>
</template>

<script lang="ts">


export default {
    components:{
        Banlist
    },
    methods: {
    }
}
</script>