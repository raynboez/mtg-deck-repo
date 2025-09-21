<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, computed, Component } from 'vue';
import card_back from '../../assets/card-back.png';
import { createPopper } from '@popperjs/core';
import { RefreshCw, CircleAlert, Sword, Lightbulb, Target, Swords} from 'lucide-vue-next';
import axios from 'axios';

import AddCardModal from './AddCardModal.vue'; 

interface Card {
  card_id: number;
  card_name: string;
  mana_cost: string;
  cmc: number;
  type_line: string;
  oracle_text: string;
  colours: string[];
  colour_identity: string[];
  image_url: string;
  scryfall_uri: string;
  set: string;
  collector_number: string;
  is_gamechanger: boolean;
  oracle_id: string;
  face_card_id: number;
  reverse_card_id: number;
  quantity: number;
}

interface Season {
  id: number;
  name: string;
  date_started: string;
  date_ended: string;
}

interface BannedCard extends Card {
  banned_by: {
    id: number;
    name: string;
    email: string;
  };
  notes: string;
}

const seasons = ref<Season[]>([]);
const selectedSeasonId = ref<number | null>(null);
const bannedCards = ref<BannedCard[]>([]);
const reverseCards = ref<Card[]>([]);
const isLoading = ref(false);

const hoveredCard = ref<Card | null>(null);
const isMounted = ref(false);
const popperInstance = ref<ReturnType<typeof createPopper> | null>(null);
const popperElement = ref<HTMLElement | null>(null);
const showingReverse = ref<Record<number, boolean>>({});

const fetchSeasons = async () => {
  try {
    const response = await axios.get('/api/seasons');
    seasons.value = response.data;
    
    if (seasons.value.length > 0) {
      selectedSeasonId.value = seasons.value[0].id;
      fetchBannedCards();
    }
  } catch (error) {
    console.error('Failed to fetch seasons:', error);
  }
};

const fetchBannedCards = async () => {
  if (!selectedSeasonId.value) return;
  
  isLoading.value = true;
  try {
    const response = await axios.get(`/api/banlist/season/${selectedSeasonId.value}`);
    bannedCards.value = response.data.cards;
    reverseCards.value = response.data.reverse || [];
  } catch (error) {
    console.error('Failed to fetch banned cards:', error);
  } finally {
    isLoading.value = false;
  }
};

const onSeasonChange = () => {
  fetchBannedCards();
};

const reverseCardsMap = computed(() => {
  const map: Record<number, Card> = {};
  reverseCards.value.forEach(reverse => {
    map[reverse.face_card_id] = reverse;
  });
  return map;
});

const getCardImage = (card: Card): string => {
  const reverseCard = reverseCardsMap.value[card.card_id];
  if (showingReverse.value[card.card_id] && reverseCard?.image_url) {
    return reverseCard.image_url;
  }
  return card.image_url || card_back;
};

const getCardDisplayData = (card: Card): Card => {
  const reverseCard = reverseCardsMap.value[card.card_id];
  if (showingReverse.value[card.card_id] && reverseCard) {
    return {
      ...reverseCard,
      quantity: card.quantity || 1
    };
  }
  return card;
};

const toggleCardReverse = (card: Card) => {
  if (reverseCardsMap.value[card.card_id]) {
    showingReverse.value = {
      ...showingReverse.value,
      [card.card_id]: !showingReverse.value[card.card_id]
    };    
    if (hoveredCard.value) {
        hoveredCard.value = getCardDisplayData(card);
    }
  }
};

const handleMouseEnter = async (card: Card, event: MouseEvent) => {
  if (!isMounted.value) return;
  
  hoveredCard.value = getCardDisplayData(card);
  
  await nextTick();
  
  if (popperElement.value) {
    if (popperInstance.value) {
      popperInstance.value.destroy();
    }
    
    const referenceElement = event.currentTarget as HTMLElement;
    
    popperInstance.value = createPopper(
      referenceElement,
      popperElement.value,
      {
        placement: 'right-start',
        strategy: 'fixed',
        modifiers: [
          {
            name: 'preventOverflow',
            options: {
              boundary: 'viewport',
              padding: 16,
              altAxis: true,
              tether: false
            }
          },
          {
            name: 'offset',
            options: {
              offset: [20, 10]
            }
          },
          {
            name: 'flip',
            options: {
              fallbackPlacements: ['left-start', 'right-start', 'top-start', 'bottom-start'],
              flipVariations: true
            }
          }
        ]
      }
    );
    
    popperInstance.value.update();
  }
};

const handleMouseLeave = () => {
  hoveredCard.value = null;
  if (popperInstance.value) {
    popperInstance.value.destroy();
    popperInstance.value = null;
  }
};

const currentUserId = ref<number | null>(null);
const adminUserIds = ref<number[]>([1]);
const showAddBanCardModal = ref(false);

const isAdminUser = computed(() => {
  return currentUserId.value && adminUserIds.value.includes(currentUserId.value);
});

const fetchCurrentUser = async () => {
  try {
    const response = await axios.get('/api/user/current');
    currentUserId.value = response.data;
  } catch (error) {
    console.error('Failed to fetch current user:', error);
  }
};
const showToast = ref(false);
const toastMessage = ref('');
const toastTimeout = ref<number | null>(null);

const showToastNotification = (message: string) => {
  toastMessage.value = message;
  showToast.value = true;
  
  if (toastTimeout.value) {
    clearTimeout(toastTimeout.value);
  }
  
  toastTimeout.value = setTimeout(() => {
    showToast.value = false;
  }, 3000);
};

const addCardToBanlist = async (cardData: any, user: number) => {
  try {
    console.log(cardData);
    console.log(user);
    const response = await axios.post('/api/banlist/add', {
      season_id: selectedSeasonId.value,
      scryfallData: cardData,
      banned_by: user,
      notes: ``
    });
    
    if (response.data.success) {
      showToastNotification(`Added ${cardData.card_name} to banlist`);
      closeAddBanCardModal();
      window.location.reload(); 
    }
  } catch (error) {
    console.error('Failed to add card to banlist:', error);
    showToastNotification('Error adding card to banlist');
  }
};

const openAddBanCardModal = () => {
  showAddBanCardModal.value = true;
};

const closeAddBanCardModal = () => {
  showAddBanCardModal.value = false;
};

onMounted(() => {
  isMounted.value = true;
  fetchSeasons();
  fetchCurrentUser();
});
onUnmounted(() => {
  if (popperInstance.value) {
    popperInstance.value.destroy();
  }
  isMounted.value = false;
});
</script>

<template>
  <div class="banlist-viewer">
    <div class="flex justify-between items-start">
      <div>
        <h1 class="text-2xl font-bold">Banned Cards</h1>
        <p class="text-muted-foreground">Cards banned for this season</p>
      </div>
      
      <div class="flex items-center gap-3">
        <button 
          v-if="isAdminUser && selectedSeasonId"
          @click="openAddBanCardModal"
          class="
            bg-red-600 hover:bg-red-700 
            text-white font-medium 
            h-9 px-4
            rounded-md
            transition-all duration-200
            shadow-sm hover:shadow-md
            focus:ring-2 focus:ring-red-500 focus:ring-opacity-50
          "
        >
          ADD TO BANLIST
        </button>
        <select 
          v-model="selectedSeasonId" 
          @change="onSeasonChange"
          class="
            text-sm
            h-9 
            border border-gray-300 rounded-md 
            py-0 px-3
            focus:ring-2 focus:ring-green-500 focus:border-transparent
            bg-white shadow-sm
            hover:border-gray-400
            text-black
          "
        >
          <option v-for="season in seasons" :key="season.id" :value="season.id">
            {{ season.name }} ({{ new Date(season.date_started).toLocaleDateString() }} - {{ new Date(season.date_ended).toLocaleDateString() }})
          </option>
        </select>
      </div>
    </div>

    <div v-if="isLoading" class="text-center py-8">
      <p>Loading banned cards...</p>
    </div>

    <div v-else-if="bannedCards.length === 0" class="text-center py-8">
      <p>No banned cards for this season.</p>
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
      <div 
        v-for="card in bannedCards" 
        :key="`${card.oracle_id}-${card.collector_number}`"
        class="card-item relative"
        @mouseenter="handleMouseEnter(getCardDisplayData(card), $event)"
        @mouseleave="handleMouseLeave"
        @click="toggleCardReverse(card)"
      >
        <img 
          :src="getCardImage(card)" 
          :alt="card.card_name"
          class="w-full rounded-xl border border-background hover:border-blue-500 transition-colors cursor-pointer"
          loading="lazy"
        />
        <div v-if="reverseCardsMap[card.card_id]" class="absolute top-1 left-1 bg-purple-500 rounded-full w-6 h-6 flex items-center justify-center p-1">
          <RefreshCw class="w-4 h-4 text-white"/>
        </div>
        <div class="mt-2 text-sm">
          <p class="font-semibold truncate">{{ card.card_name }}</p>
          <p class="text-gray-500 text-xs">Banned by: {{ card.banned_by?.name || 'Unknown' }}</p>
          <p v-if="card.notes" class="text-gray-500 text-xs truncate" :title="card.notes">Note: {{ card.notes }}</p>
        </div>
      </div>
    </div>
    
    <div 
      v-if="hoveredCard && isMounted"
      ref="popperElement"
      class="fixed z-50 w-64 pointer-events-none"
      style="max-width: calc(100vw - 20px)"
    >
      <div class="bg-background rounded-xl shadow-xl border border-gray-200">
        <img 
          :src="hoveredCard.image_url || card_back" 
          :alt="hoveredCard.card_name"
          class="w-full rounded-xl"
        />
        <div class="p-3">
          <h3 class="font-bold">{{ hoveredCard.card_name }}</h3>
          <p class="text-sm text-gray-600">{{ hoveredCard.type_line }}</p>
          <p class="text-sm mt-2" v-html="hoveredCard.oracle_text?.replace(/\n/g, '<br>') || ''"></p>
          <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
            <span>{{ hoveredCard.set }} #{{ hoveredCard.collector_number }}</span>
            <span>{{ hoveredCard.mana_cost }}</span>
          </div>
        </div>
      </div>
    </div>
    <AddCardModal
      v-if="showAddBanCardModal"
      :show="showAddBanCardModal"
      :deck-id=0
      @close="closeAddBanCardModal"
      @add-card="addCardToBanlist"
      modal-title="Add Card to Banlist"
    />
  </div>
</template>

<style scoped>
.card-item {
  transition: transform 0.2s;
}

.card-item:hover {
  transform: translateY(-5px);
  z-index: 10;
}

.fixed {
  transition: opacity 0.2s ease;
}

.fixed[style*="visibility: hidden"] {
  opacity: 0;
}
</style>