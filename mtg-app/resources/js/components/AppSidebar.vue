<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarGroup, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, User, Deck } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, ChevronRight} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import axios from 'axios';
import Separator from './ui/separator/Separator.vue';
</script>
<script lang="ts">




export default {
    data()
    {
        return {
            users: [] as User[],
            expandedUsers: [] as number[],
            navItems: [
                {
                    title:"",
                    href:'',
                }
            ]
        }
    },
    methods:
    {
        async fetchUsers()
        {
            try {
                const response = await axios.get('/api/users');
                this.users = response.data.map((user: any) => 
                ({
                id: user.user_id,
                name: user.name,
                decks: [],
                }));
                this.users.forEach(user => this.fetchUserDecks(user.id));
                } catch (error) {
                    console.error('Error fetching users:', error);
                }
        },
        async fetchUserDecks(userId: number)
        {
            try 
            {
                let apiReq = '/api/decks/user/' + userId;
                const response = await axios.get(apiReq);
                const user = this.users.find(u => u.id === userId);
                if(user)
                {
                    user.decks = response.data.map((deck: any) => ({
                        deck_name: deck.deck_name,
                        deck_id: deck.deck_id,
                        href: '/deck_import',
                        icon: LayoutGrid,
                    }));
                }
            } 
            catch (error) 
            {
                console.error('Error fetching decks:', error);
            }

        },
        toggleUser(userId: number) 
        {
            const index = this.expandedUsers.indexOf(userId);
            if (index > -1) {
                this.expandedUsers.splice(index, 1);
            } else {
                this.expandedUsers.push(userId);
            }
        },
    
        getUserDecksAsNavItems(userId: number): NavItem[] 
        {
            const user = this.users.find(u => u.id === userId);
            if (!user?.decks) return [];
            
            return user.decks.map(deck => ({
                title: deck.deck_name,
                href: `/deck/${deck.deck_id}`,
                icon: LayoutGrid,
            }));
        }
    },
    mounted() {
        this.fetchUsers();
    }

}

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/raynboez/mtg-deck-repo',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
    
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton class="headerButton" size="lg" as-child>
                        <Link :href="route('deck_import')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <Separator/>
        <span class="flex flex-col p-2 mx-auto">Imported Decks</span>
        <SidebarContent>            
            <div v-for="user in users" :key="user.id" class="user-section">
                <SidebarMenuItem @click="toggleUser(user.id)">
                    <SidebarMenuButton>
                        <span>{{ user.name }}</span>
                        <span class="toggle-icon ml-auto">
                        <component 
                            :is="ChevronRight" 
                            class="h-4 w-4 transition-transform duration-200"
                            :class="{
                                'rotate-90': expandedUsers.includes(user.id),
                                'rotate-0': !expandedUsers.includes(user.id)
                            }"
                        />
                        </span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <Transition name="slide">                
                    <SidebarGroup v-if="expandedUsers.includes(user.id)" class="user-decks">
                        <NavMain :items="getUserDecksAsNavItems(user.id)" :username=user.name />
                    </SidebarGroup>
                </Transition>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<style scoped>
.slide-enter-active {
  transition: all 0.3s ease-out;
}

.slide-leave-active {
  transition: all 0.2s ease-in;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}


/* Submit Button */
.headerButton {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, var(--chart-2), var(--primary));
  color: var(--primary-foreground);
  border: none;
  border-radius: var(--radius);
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.headerButton:hover {
  background: linear-gradient(135deg, var(--chart-3), var(--primary));
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.headerButton:active {
  transform: translateY(0);
}

.headerButton:disabled {
  background: var(--muted);
  color: var(--muted-foreground);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

</style>