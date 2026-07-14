<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Ban, Folder, LogOut, Settings, RefreshCw } from 'lucide-vue-next';
import { callWithErrorHandling, computed } from 'vue';

interface Props {
    user: User;
}

const page = usePage();
const currentUrl = computed(() => new URL(page.url, window.location.origin));
const isWarhammerMode = computed(() => currentUrl.value.pathname.startsWith('/warhammer'));

const switchHref = computed(() => {
    const url = new URL(currentUrl.value.toString());
    const pathname = url.pathname;

    if (isWarhammerMode.value) {
        url.pathname = '/stats';
    } else {
        url.pathname = '/warhammer/stats';
    }

    return url.pathname + url.search;
});

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="switchHref" prefetch as="button">
                <RefreshCw class="mr-2 h-4 w-4" />
                {{ isWarhammerMode ? 'MTG Mode' : 'Warhammer Mode' }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <a class="block w-full" :href="route('bans')" prefetch as="button">
                <Ban class="mr-2 h-4 w-4" />
                Ban List
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <a class="block w-full" href="https://github.com/raynboez/mtg-deck-repo" target="_blank" rel="noopener noreferrer" prefetch as="button">
                <Folder class="mr-2 h-4 w-4" />
                Github Repo
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="route('profile.edit')" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full" method="post" :href="route('logout')" @click="handleLogout" as="button">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
