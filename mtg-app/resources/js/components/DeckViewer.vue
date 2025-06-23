<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import card_back from '../../assets/card-back.png';
import { createPopper } from '@popperjs/core';

interface Card {
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
}>();

const hoveredCard = ref<Card | null>(null);
const isMounted = ref(false);
const popperInstance = ref<ReturnType<typeof createPopper> | null>(null);
const popperElement = ref<HTMLElement | null>(null);

const CARD_TYPE_ORDER = {
  'singular': [
    'Creature',
  'Artifact',
  'Enchantment',
  'Instant',
  'Sorcery',
  'Planeswalker',
  'Battle',
  'Land'
  ],
  'plural':[
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

const getPrimaryCardType = (typeLine: string): string => {
  for (const type of CARD_TYPE_ORDER.singular) {
    if (typeLine.includes(type)) {
      return type;
    }
  }
  return 'Other';
};

const groupedCards = computed(() => {
  const groups: Record<string, { cards: Card[], totalQuantity: number }> = {};

  CARD_TYPE_ORDER.singular.forEach(type => {
    groups[type] = { cards: [], totalQuantity: 0 };
  });
  groups['Other'] = { cards: [], totalQuantity: 0 };
  
  props.cards.forEach(card => {
    const type = getPrimaryCardType(card.type_line);
    const group = groups[type] || groups['Other'];
    group.cards.push(card);
    group.totalQuantity += card.quantity;
  });
  
  return groups;
});


onMounted(() => {
  isMounted.value = true;
});

onUnmounted(() => {
  if (popperInstance.value) {
    popperInstance.value.destroy();
  }
  isMounted.value = false;
});

const handleMouseEnter = async (card: Card, event: MouseEvent) => {
  if (!isMounted.value) return;
  
  hoveredCard.value = card;
  
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



</script>

<template>
  <div class="deck-viewer">
    <h1 class="text-2xl font-bold mb-4">{{ deck.deck_name }}</h1>
    <p class="text-gray-600 mb-6">{{ deck.description }}</p>
    
   
    <div v-for="type in CARD_TYPE_ORDER.singular" :key="type">    
      <div v-if="groupedCards[type] && groupedCards[type].cards.length > 0" class="mb-8">
        <h2 class="text-xl font-semibold mb-4 border-b pb-2">{{ CARD_TYPE_ORDER.plural[CARD_TYPE_ORDER.singular.indexOf(type)] }} ({{ groupedCards[type].totalQuantity }})</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          <div 
            v-for="card in groupedCards[type].cards" 
            :key="`${card.oracle_id}-${card.collector_number}`"
            class="card-item relative"
            @mouseenter="handleMouseEnter(card, $event)"
            @mouseleave="handleMouseLeave"
          >
            <img 
              :src="card.image_url || card_back" 
              :alt="card.card_name"
              class="w-full rounded-xl border border-background hover:border-blue-500 transition-colors"
              loading="lazy"
            />
            <div v-if="card.quantity > 1" class="absolute bottom-1 left-1 bg-gray-200 text-black text-xs font-semibold px-2 py-1 rounded-md">
              ×{{ card.quantity }}
            </div>
            <div v-if="card.is_gamechanger" class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1 rounded">
              GC
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
          @mouseenter="handleMouseEnter(card, $event)"
          @mouseleave="handleMouseLeave"
        >
          <img 
            :src="card.image_url || card_back" 
            :alt="card.card_name"
            class="w-full rounded-xl border border-background hover:border-blue-500 transition-colors"
            loading="lazy"
          />
          <div v-if="card.quantity > 1" class="absolute bottom-1 left-1 bg-gray-200 text-black text-xs font-semibold px-2 py-1 rounded-md">
            ×{{ card.quantity }}
          </div>
          <div v-if="card.is_gamechanger" class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1 rounded">
            GC
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