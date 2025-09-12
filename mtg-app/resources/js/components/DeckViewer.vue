<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, computed, Component } from 'vue';
import card_back from '../../assets/card-back.png';
import { createPopper } from '@popperjs/core';
import { RefreshCw, CircleAlert, Sword, Lightbulb, Target, Swords} from 'lucide-vue-next';
import axios from 'axios';
import DeckAssignmentModal from './DeckAssignmentModal.vue';
import AddCardModal from './AddCardModal.vue'; 
import RemoveCardModal from './RemoveCardModal.vue'; 

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


const props = defineProps<{
  deck: {
    deck_id: number;
    deck_name: string;
    description: string;
    colour_identity: string[];
    power_level: number;
  };
  cards: Card[];
  reverse: Card[];
  commanders: number[];
  potentialCommanders: number[];
  deckstats: string | null;
  cardcount: number | null;
}>();



const hoveredCard = ref<Card | null>(null);
const isMounted = ref(false);
const popperInstance = ref<ReturnType<typeof createPopper> | null>(null);
const popperElement = ref<HTMLElement | null>(null);
const showingReverse = ref<Record<number, boolean>>({});


const CARD_TYPE_ORDER = {
  singular: [
    'Commander',
    'Companion',
    'Creature',
    'Artifact',
    'Enchantment',
    'Instant',
    'Sorcery',
    'Planeswalker',
    'Battle',
    'Land'
  ],
  plural: [
    'Commander',
    'Companion',
    'Creatures',
    'Artifacts',
    'Enchantments',
    'Instants',
    'Sorceries',
    'Planeswalkers',
    'Battles',
    'Lands'
  ]
};

const showAssignmentModal = ref(false);
const showAddCardModal = ref(false);
const showRemoveCardModal = ref(false);

const openEditModal = () => {
  showAssignmentModal.value = true;
};
const openAddCardModal = () => {
  showAddCardModal.value = true;
};

const openRemoveCardModal = () => {
  showRemoveCardModal.value = true;
};

const closeAddCardModal = () => {
  showAddCardModal.value = false;
};

const closeRemoveCardModal = () => {
  showRemoveCardModal.value = false;
};

const saveDeckDetails = async (details: any) => {
  try {
                const response = await axios.put(`/api/decks/${props.deck.deck_id}`, {
                name: details.name,
                description: details.description,
                commanders: details.commanders,
                power_level: details.power_level
                });
                
                window.location.href = `/deck/${props.deck.deck_id}`;
            } catch (error) {
                console.error('Failed to save deck details:', error);
            }
};

const reverseCardsMap = computed(() => {
  const map: Record<number, Card> = {};
  props.reverse.forEach(reverse => {
    map[reverse.face_card_id] = reverse;
  });
  return map;
});

const groupedCards = computed(() => {
  const groups: Record<string, { cards: Card[], totalQuantity: number }> = {};

  CARD_TYPE_ORDER.singular.forEach(type => {
    groups[type] = { cards: [], totalQuantity: 0 };
  });
  groups['Other'] = { cards: [], totalQuantity: 0 };
  props.cards.forEach(card => {
    var type = '';
    if(props.commanders.includes(card.card_id)){
      type = CARD_TYPE_ORDER.singular[0];
    } 
    else {
      type = getPrimaryCardType(card.type_line);
    }
    
    const group = groups[type] || groups['Other'];
    group.cards.push(card);
    group.totalQuantity += card.quantity;
  });
  
  return groups;
});

const getPrimaryCardType = (typeLine: string): string => {
  if (typeLine.includes('Land')) {
      return 'Land';
    }
  for (const type of CARD_TYPE_ORDER.singular) {
    
    if (typeLine.includes(type)) {
      return type;
    }
  }
  return 'Other';
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
      quantity: card.quantity
    };
  }
  return card;
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

onMounted(() => {
  isMounted.value = true;
});

onUnmounted(() => {
  if (popperInstance.value) {
    popperInstance.value.destroy();
  }
  isMounted.value = false;
});


async function copyDeckToClipboard() {
  const btn = document.getElementById('copyDeckBtn') as HTMLButtonElement;;
  
  if (!btn) return;

  try {
    btn.innerHTML = 'Copying...';
    btn.disabled = true;
    
    const response = await axios.get(`/api/getDeck/${props.deck.deck_id}`,{ responseType: 'text' });
    
    if (!(response.status === 200)) throw new Error('Network response was not ok');
    const data = response.data; 
    
    await navigator.clipboard.writeText(data);
    
    btn.innerHTML = 'Copied!';
    setTimeout(() => {
      btn.innerHTML = 'COPY DECK';
      btn.disabled = false;
    }, 2000);
    
  } catch (error) {
    console.error('Error:', error);
    btn.innerHTML = 'Error!';
    setTimeout(() => {
      btn.innerHTML = 'COPY DECK';
      btn.disabled = false;
    }, 2000);
  }
}


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



const addCardToDeck = async (cardData: any, quantity: number) => {
  try {
    console.log(cardData)
    
    const response = await axios.put(`/api/decks/${props.deck.deck_id}/add`, {
      scryfallData:cardData, 
      amount: quantity
    });
    if (response.status === 201) {
      showToastNotification(`Added ${quantity} ${quantity === 1 ? 'copy' : 'copies'} of ${cardData.card_name}`);
      closeAddCardModal();
      
      window.location.reload(); 
    }
  } catch (error) {
    console.error('Failed to add card:', error);
    showToastNotification('Error adding card to deck');
  }
};

const removeCardFromDeck = async (cardId: number, cardName: string, quantity?: number) => {
  try {
    if(!quantity){
      quantity=1;
    }
    const response = await axios.post(`/api/decks/${props.deck.deck_id}/remove`, {
      card_id:cardId,
      amount:quantity
    });
    
    if (response.status === 201) {
      showToastNotification(`Removed ${quantity} ${quantity === 1 ? 'copy' : 'copies'} of ${cardName}`);    
      closeRemoveCardModal();
      window.location.reload(); 
    }
  } catch (error) {
    console.error('Failed to remove card:', error);
    showToastNotification('Error removing card');
  }
};

</script>
<template>
  <div class="deck-viewer">
    <div class="flex justify-between items-start">
      <div>
        <h1 class="text-2xl font-bold mb-4">{{ deck.deck_name }} - Bracket {{ deck.power_level }}</h1>
        <p class="text-muted-foreground mb-4">{{ deck.description }}</p>
        <p class="text-muted-foreground mb-6">Win-Loss: {{ deckstats }}</p>
        <p class="text-muted-foreground mb-6">Cards in Deck: {{ cardcount }}</p>
      </div>
      
      <div class="flex items-center gap-3">
        <!--
        <div class="relative">
          <select class="
            text-sm
            h-9 
            border border-gray-300 rounded-md 
            py-0 px-3
            focus:ring-2 focus:ring-green-500 focus:border-transparent
            bg-white shadow-sm
            hover:border-gray-400
            text-black
            disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:bg-gray-200
          " disabled>
            <option>v1.0</option>
            <option>v2.0</option>
            <option>v3.0</option>
          </select>
        </div>
      -->
        <button 
          id="copyDeckBtn"
          class="
            bg-green-600 hover:bg-green-700 
            text-white font-medium 
            h-9 px-4
            rounded-md
            transition-all duration-200
            shadow-sm hover:shadow-md
            focus:ring-2 focus:ring-green-500 focus:ring-opacity-50
          "
          @click="copyDeckToClipboard">
          COPY DECK
        </button>
        
        <button 
          class="
            bg-gray-200 hover:bg-gray-300 
            text-gray-700 
            font-medium 
            h-9 px-4
            rounded-md
            transition-all duration-200
            shadow-sm
            focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50
          "
          @click="openEditModal"
        >
          EDIT DECK DETAILS
        </button>

        <button 
        class="
          bg-green-600 hover:bg-green-700 
          text-white font-medium 
          h-9 px-4
          rounded-md
          transition-all duration-200
          shadow-sm hover:shadow-md
          focus:ring-2 focus:ring-green-500 focus:ring-opacity-50
        "
        @click="openAddCardModal"
      >
        ADD CARD
      </button>
        <button 
        class="
          bg-red-600 hover:bg-red-700 
          text-white font-medium 
          h-9 px-4
          rounded-md
          transition-all duration-200
          shadow-sm hover:shadow-md
          focus:ring-2 focus:ring-red-500 focus:ring-opacity-50
        "
        @click="openRemoveCardModal"
      >
        REMOVE CARD
      </button>
      </div>
    </div>

    <div v-for="type in CARD_TYPE_ORDER.singular" :key="type">    
      <div v-if="groupedCards[type] && groupedCards[type].cards.length > 0" class="mb-8">
        <h2 class="text-xl font-semibold mb-4 border-b pb-2">{{ CARD_TYPE_ORDER.plural[CARD_TYPE_ORDER.singular.indexOf(type)] }} ({{ groupedCards[type].totalQuantity }})</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          <div 
            v-for="card in groupedCards[type].cards" 
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
            <div v-if="card.quantity > 1" class="absolute bottom-1 left-1 bg-gray-200 text-black text-xs font-semibold px-2 py-1 rounded-md">
              ×{{ card.quantity }}
            </div>
            <div v-if="card.is_gamechanger" class="absolute top-1 right-1 bg-orange-500 rounded-full w-6 h-6 flex items-center justify-center p-1">
              <Swords class="w-4 h-4" />
            </div>
            <div v-if="reverseCardsMap[card.card_id]" class="absolute top-1 left-1 bg-purple-500 rounded-full w-6 h-6 flex items-center justify-center p-1">
              <RefreshCw/>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="groupedCards['Other'] && groupedCards['Other'].cards.length > 0" class="mb-8">
      <h2 class="text-xl font-semibold mb-4 border-b pb-2">Other ({{ groupedCards['Other'].totalQuantity }})</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <div 
          v-for="card in groupedCards['Other'].cards" 
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
          <div v-if="card.quantity > 1" class="absolute bottom-1 left-1 bg-gray-200 text-black text-xs font-semibold px-2 py-1 rounded-md">
            ×{{ card.quantity }}
          </div>
          <div v-if="card.is_gamechanger" class="absolute top-1 right-1 bg-orange-500 rounded-full w-6 h-6 flex items-center justify-center p-1">
            <Swords class="w-4 h-4" />
          </div>
          <div v-if="reverseCardsMap[card.card_id]" class="absolute top-1 left-1 bg-purple-500 rounded-full w-6 h-6 flex items-center justify-center p-1">
              <RefreshCw/>
          </div>
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
          <p class="text-sm mt-2" v-html="hoveredCard.oracle_text.replace(/\n/g, '<br>')"></p>
          <div class="flex justify-between items-center mt-2 text-xs text-gray-500">
            <span>{{ hoveredCard.set }} #{{ hoveredCard.collector_number }}</span>
            <span>{{ hoveredCard.mana_cost }}</span>
          </div>
        </div>
      </div>
    </div>

    <transition name="toast">
      <div v-if="showToast" class="toast-notification">
        <span>{{ toastMessage }}</span>
        <button class="toast-close" @click="showToast = false">×</button>
      </div>
    </transition>
    <DeckAssignmentModal
            v-if="showAssignmentModal"
            :show="showAssignmentModal"
            :potential-commanders="potentialCommanders"
            :potential-companions="[]"
            :initial-deck-data="deck"
            @close="showAssignmentModal = false"
            @save="saveDeckDetails"
        />

        <AddCardModal
      v-if="showAddCardModal"
      :show="showAddCardModal"
      :deck-id="deck.deck_id"
      @close="closeAddCardModal"
      @add-card="addCardToDeck"
    />

    <RemoveCardModal
      v-if="showRemoveCardModal"
      :show="showRemoveCardModal"
      :deck-id="deck.deck_id"
      :cards="cards"
      @close="closeRemoveCardModal"
      @remove-card="removeCardFromDeck"
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

.toast-notification {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #1f2937;
  color: white;
  padding: 12px 20px;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1000;
  max-width: 90%;
}

.toast-close {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 18px;
  cursor: pointer;
  padding: 0;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.toast-close:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: white;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translate(-50%, 100%);
}

.toast-enter-to,
.toast-leave-from {
  opacity: 1;
  transform: translate(-50%, 0);
}
</style>