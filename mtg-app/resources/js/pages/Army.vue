<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import ArmyViewer from '../components/ArmyViewer.vue';
import { type BreadcrumbItem } from '@/types';

// Props
interface Stats {
    global: {
        wins: number;
        losses: number;
        total_games: number;
        win_percentage: number;
    };
    personal: {
        wins: number;
        losses: number;
        total_games: number;
        win_percentage: number;
    };
}
const props = defineProps<{
    army_id: number
}>();

// State
const army = ref<any>(null);
const armystats = ref<string | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Army View',
        href: `/warhammer/army/${props.army_id}`,
    },
];

// Fetch army data
const fetchArmy = async () => {
    try {
        isLoading.value = true;
        error.value = null;
        const response = await axios.get(`/api/warhammer/armies/${props.army_id}`);
        army.value = response.data.army;
        armystats.value = response.data.stats;
        console.log(army.value);
        console.log(armystats.value);
    } catch (err) {
        error.value = 'Failed to load army data';
        console.error('Error fetching army:', err);
    } finally {
        isLoading.value = false;
    }
};

// Fetch on mount
onMounted(() => {
    if (props.army_id) {
        fetchArmy();
    }
});
</script>

<template>
    <Head title="Army" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="animate-spin text-4xl mb-4">⟳</div>
                    <p class="text-gray-500">Loading army details...</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <p class="text-red-500 mb-4">{{ error }}</p>
                    <button 
                        @click="fetchArmy" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
                    >
                        Retry
                    </button>
                </div>
            </div>

            <!-- Army Viewer -->
            <ArmyViewer 
                v-else-if="army && armystats"
                :army="army"
                :armystats=armystats
            />
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
</style>