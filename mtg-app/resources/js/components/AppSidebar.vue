<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarGroup, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, User, Deck } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, ChevronRight} from 'lucide-vue-next';
import AppLogo from './ImportDeckButton.vue';
import axios from 'axios';
import Separator from './ui/separator/Separator.vue';
import ImportDeckButton from './ImportDeckButton.vue';
import RecordMatchButton from './RecordMatchButton.vue';
import StatsButton from './StatsButton.vue';
</script>
<script lang="ts">

const factionToManaSymbolsMap: Record<string, string[]> = {
        'bant':   ['{W}','{U}','{G}'],
        'esper':  ['{W}','{U}','{B}'],
        'grixis': ['{U}','{B}','{R}'],
        'jund':   ['{B}','{R}','{G}'],
        'naya':   ['{W}','{R}','{G}'],      
        'abzan':  ['{W}','{B}','{G}'],
        'jeskai': ['{W}','{U}','{R}'],
        'sultai': ['{U}','{B}','{G}'],
        'mardu':  ['{W}','{B}','{R}'],
        'temur':  ['{B}','{R}','{G}'],
        'wubr':   ['{W}','{U}','{B}','{R}'],
        'ubrg':   ['{U}','{B}','{R}','{G}'],
        'wbrg':   ['{W}','{B}','{R}','{G}'],
        'wurb':   ['{W}','{U}','{R}','{G}'],
        'wubg':   ['{W}','{U}','{B}','{G}'],        
        'FiveColor[': ['{W}','{U}','{B}','{R}','{G}'],
        'Five-Color': ['{W}','{U}','{B}','{R}','{G}'],
        'Colorless': ['{C}'],
  'azorius': ['{W}', '{U}'],
  'dimir': ['{U}', '{B}'],
  'rakdos': ['{B}', '{R}'],
  'gruul': ['{R}', '{G}'],
  'selesnya': ['{W}', '{G}'],
  'orzhov': ['{W}', '{B}'],
  'izzet': ['{U}', '{R}'],
  'golgari': ['{B}', '{G}'],
  'boros': ['{W}', '{R}'],
  'simic': ['{U}', '{G}'],
  'white': ['{W}'],
  'blue': ['{U}'],
  'black': ['{B}'],
  'red': ['{R}'],
  'green': ['{G}'],
  'colorless': ['{C}']
};
function factionToManaSymbols(factionName: string): string[] {
  const normalizedName = factionName.toLowerCase().trim();
  return factionToManaSymbolsMap[normalizedName];
}

function displayManaSymbols(factionName: string): string {
  try {
    const symbols = factionToManaSymbols(factionName);
    return symbols.join(' ');
  } catch (error) {
    return `Unknown faction: ${factionName}`;
  }
}

function getManaSymbolsHTML(factionName: string): string {
  try {
    const symbols = factionToManaSymbols(factionName);
    return symbols.map(symbol => 
      `<span class="mana ${symbol.replace(/[{}]/g, '').toLowerCase()}">${symbol}</span>`
    ).join(' ');
  } catch (error) {
    return `<span>Unknown faction: ${factionName}</span>`;
  }
}
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
                        deck_identity: displayManaSymbols(deck.colour_identity),
                        deck_name: deck.deck_name,
                        deck_id: deck.deck_id,
                        icon: getManaSymbolsHTML(deck.colour_identity),
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
                            <ImportDeckButton />
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
            <SidebarMenuItem>
                <SidebarMenuButton class="headerButton" size="lg" as-child>
                    <Link :href="route('match_import')">
                        <RecordMatchButton />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
                <SidebarMenuButton class="statsButton" size="lg" as-child>
                    <Link :href="route('stats')">
                        <StatsButton />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
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


.headerButton {
  width: 100%;
  padding: 12px;
  background: var(--chart-2);
  color: var(--primary-foreground);
  border: none;
  border-radius: var(--radius); 
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.headerButton:hover {
  background: var(--chart-3);
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

.statsButton {
  width: 100%;
  padding: 12px;
  background: var(--chart-2);
  color: var(--primary-foreground);
  border: none;
  border-radius: var(--radius); 
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}


.statsButton:hover {
  background: var(--chart-3);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.statsButton:active {
  transform: translateY(0);
}

.statsButton:disabled {
  background: var(--muted);
  color: var(--muted-foreground);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

</style>