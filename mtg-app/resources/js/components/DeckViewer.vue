<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import card_back from '../../assets/card-back.png';

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
const hoverPosition = ref({ x: 0, y: 0 });
const isMounted = ref(false);

onMounted(() => {
  isMounted.value = true;
});

onUnmounted(() => {
  isMounted.value = false;
});

const handleMouseEnter = (card: Card, event: MouseEvent) => {
  if (!isMounted.value) return;
  hoveredCard.value = card;
  console.log(hoveredCard.value);
  updatePosition(event);
};

const handleMouseMove = (event: MouseEvent) => {
  if (!isMounted.value || !hoveredCard.value) return;
  updatePosition(event);
};

const handleMouseLeave = () => {
  hoveredCard.value = null;
};

const updatePosition = (event: MouseEvent) => {
  if (!isMounted.value) return;
  
  const x = Math.max(0, event.clientX + 20);
  const y = Math.max(0, event.clientY);
  
  const maxX = window.innerWidth
  const maxY = window.innerHeight - 384; 
  
  hoverPosition.value = {
    x: Math.min(x, maxX),
    y: Math.min(y, maxY)
  };
};
</script>

<template>
  <div class="deck-viewer">
    <h1 class="text-2xl font-bold mb-4">{{ deck.deck_name }}</h1>
    <p class="text-gray-600 mb-6">{{ deck.description }}</p>
    
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
      <div 
        v-for="card in cards" 
        :key="`${card.oracle_id}-${card.collector_number}`"
        class="card-item relative"
        @mouseenter="handleMouseEnter(card, $event)"
        @mousemove="handleMouseMove"
        @mouseleave="handleMouseLeave"
      >
        <img 
          :src="card.image_url || card_back" 
          :alt="card.card_name"
          class="w-full rounded-md border border-gray-200 hover:border-blue-500 transition-colors"
          loading="lazy"
          
        />
        <div v-if="card.is_gamechanger" class="absolute top-1 right-1 bg-red-500 text-white text-xs px-1 rounded">
          GC
        </div>
      </div>
    </div>

    <transition name="fade">
      <div 
        v-if="hoveredCard && isMounted"
        class="fixed z-50 w-64 pointer-events-none transition-opacity"
        :style="{
          left: `${hoverPosition.x}px`,
          top: `${hoverPosition.y}px`,
          'max-width': 'calc(100vw - 20px)'
        }"
      >
        <div class="bg-white rounded-lg shadow-xl border border-gray-200">
          <img 
            :src="hoveredCard.image_url || card_back" 
            :alt="hoveredCard.card_name"
            class="w-full rounded-t-lg"
        
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
    </transition>
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

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>