<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Remove Card from Deck</h2>
        <button class="close-button" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <p>Select a card to remove from your deck:</p>
        <div class="cards-list">
          <div 
            v-for="card in cards" 
            :key="card.card_id"
            class="card-item"
            :class="{ selected: selectedCardId === card.card_id }"
            @click="selectCard(card)"
          >
            <img :src="card.image_url" :alt="card.card_name" class="card-image" />
            <div class="card-info">
              <h3>{{ card.card_name }}</h3>
              <p>{{ card.type_line }}</p>
            </div>
            <div class="quantity">×{{ card.quantity }}</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div v-if="selectedCard && selectedCard.quantity > 1" class="quantity-selector">
          <span class="quantity-label">Remove:</span>
          <div class="quantity-controls">
            <button 
              @click="decrementQuantity" 
              :disabled="removeQuantity <= 1"
              class="qty-btn"
            >−</button>
            <span class="qty-display">{{ removeQuantity }}</span>
            <button 
              @click="incrementQuantity" 
              :disabled="removeQuantity >= selectedCard.quantity"
              class="qty-btn"
            >+</button>
            <span class="max-quantity">of {{ selectedCard.quantity }}</span>
          </div>
          <button 
            class="remove-all-btn"
            @click="removeAllCopies"
          >
            Remove All
          </button>
        </div>
        
        <div class="footer-buttons">
          <button class="btn-secondary" @click="$emit('close')">Cancel</button>
          <button 
            class="btn-danger" 
            @click="removeCard" 
            :disabled="!selectedCardId"
          >
            {{ removeButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

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


defineProps<{
  show: boolean;
  deckId: number;
  cards: Card[];
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'remove-card', cardId: number, cardName: string, quantity?: number): void;
}>();

const selectedCardId = ref<number | null>(null);
const selectedCard = ref<Card | null>(null);
const removeQuantity = ref(1);

const removeButtonText = computed(() => {
  if (!selectedCard.value) return 'Remove Card';
  
  if (selectedCard.value.quantity > 1 && removeQuantity.value > 1) {
    return `Remove ${removeQuantity.value} Copies`;
  }
  
  return 'Remove Card';
});

const selectCard = (card: Card) => {
  selectedCardId.value = card.card_id;
  selectedCard.value = card;
  removeQuantity.value = 1;
};

const incrementQuantity = () => {
  if (selectedCard.value && removeQuantity.value < selectedCard.value.quantity) {
    removeQuantity.value++;
  }
};

const decrementQuantity = () => {
  if (removeQuantity.value > 1) {
    removeQuantity.value--;
  }
};

const removeAllCopies = () => {
  if (selectedCard.value) {
    removeQuantity.value = selectedCard.value.quantity;
  }
};

const removeCard = () => {
  if (selectedCardId.value && selectedCard.value) {
    if (removeQuantity.value === 1) {
      emit('remove-card', selectedCardId.value, selectedCard.value.card_name);
    } else {
      emit('remove-card', selectedCardId.value, selectedCard.value.card_name, removeQuantity.value);
    }
  }
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: var(--color-background);
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 1rem;
  flex-grow: 1;
  overflow-y: auto;
}

.cards-list {
  margin-top: 1rem;
  margin-bottom: 1rem;
}

.card-item {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  margin-bottom: 0.5rem;
  cursor: pointer;
}

.card-item:hover {
  background-color: var(--color-background);
}

.card-item.selected {
  background-color: var(--color-background);
  border-color: #3b82f6;
}

.card-image {
  width: 40px;
  height: 40px;
  margin-right: 1rem;
  border-radius: 4px;
}

.card-info {
  flex-grow: 1;
}

.card-info h3 {
  margin: 0;
  font-size: 1rem;
}

.card-info p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.quantity {
  font-weight: bold;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.quantity-selector {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background-color: #f9fafb;
}

.quantity-label {
  font-weight: 500;
  margin-right: 0.5rem;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.qty-btn {
  width: 28px;
  height: 28px;
  border: 1px solid #d1d5db;
  background-color: white;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qty-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.qty-display {
  font-weight: bold;
  min-width: 24px;
  text-align: center;
}

.max-quantity {
  margin-left: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.remove-all-btn {
  padding: 0.25rem 0.5rem;
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 4px;
  font-size: 0.875rem;
  cursor: pointer;
  margin-left: 0.5rem;
}

.remove-all-btn:hover {
  background-color: #fee2e2;
}

.footer-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.btn-danger {
  background-color: #dc2626;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  min-width: 120px;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-danger:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}
</style>