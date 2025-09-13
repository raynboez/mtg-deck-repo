<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add Card to Deck</h2>
        <button class="close-button" @click="$emit('close')">×</button>
      </div>
      <div class="modal-body">
        <div class="search-section">
          <div class="search-input-wrapper">
            <input 
              type="text" 
              placeholder="Search for cards by name..." 
              v-model="searchQuery"
              @keypress.enter="searchCards"
              class="search-input"
              :disabled="isSearching"
            />
            <button 
              @click="searchCards" 
              class="search-button"
              :disabled="isSearching || !searchQuery.trim()"
            >
              <span v-if="!isSearching">Search</span>
              <span v-else>Searching...</span>
            </button>
          </div>
          
          <div v-if="searchError" class="error-message">
            {{ searchError }}
          </div>
        </div>

        <div v-if="isSearching" class="loading-section">
          <div class="loading-spinner"></div>
          <p>Searching Scryfall...</p>
        </div>

        <div v-else-if="searchResults.length > 0" class="results-section">
          <h3>Search Results ({{ searchResults.length }})</h3>
          <div class="search-results">
            <div 
              v-for="card in searchResults" 
              :key="card.id"
              class="search-result-card"
              :class="{ selected: selectedCardId === card.id }"
              @click="selectCard(card)"
            >
              <img 
                :src="card.image_uris?.normal || card.card_faces?.[0]?.image_uris?.normal || card_back" 
                :alt="card.name"
                class="card-image"
                @error="handleImageError"
              />
              <div class="card-details">
                <h4>{{ card.name }}</h4>
                <p>{{ card.mana_cost }}</p>
                <p class="type-line">{{ card.type_line }}</p>
                <p class="set-info">{{ card.set_name }} ({{ card.set.toUpperCase() }})</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="searchQuery && !isSearching" class="no-results">
          <p>No cards found. Try a different search term.</p>
        </div>

        <div v-else class="initial-state">
          <p>Search for Magic: The Gathering cards to add to your deck.</p>
          <p class="hint">Try searching by card name, like "Lightning Bolt" or "Counterspell"</p>
        </div>
      </div>
      
      <div class="modal-footer">
        <div v-if="selectedCard" class="selected-card-info">
          <span>Selected: <strong>{{ selectedCard.name }}</strong></span>
          <div class="quantity-control" v-if="!isCommanderCard(selectedCard)">
            <label>Quantity:</label>
            <div class="quantity-buttons">
              <button @click="decrementQuantity">−</button>
              <span>{{ quantity }}</span>
              <button @click="incrementQuantity">+</button>
            </div>
          </div>
          <span v-else class="commander-note">(Commander - 1 copy max)</span>
        </div>
        
        <div class="footer-buttons">
          <button class="btn-secondary" @click="$emit('close')">Cancel</button>
          <button 
            class="btn-primary" 
            @click="addCardToDeck" 
            :disabled="!selectedCard || isAdding"
          >
            <span v-if="!isAdding">Add Card</span>
            <span v-else>Adding...</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';
import card_back from '../../assets/card-back.png';

interface ScryfallCard {
  id: string;
  name: string;
  mana_cost: string;
  type_line: string;
  oracle_text: string;
  set: string;
  set_name: string;
  collector_number: string;
  image_uris?: {
    normal: string;
  };
  card_faces?: Array<{
    name: string;
    image_uris: {
      normal: string;
    };
  }>;
  legalities: {
    commander: string;
  };
}

const props = defineProps<{
  show: boolean;
  deckId: number;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'add-card', cardData: any, quantity: number): void;
}>();

const searchQuery = ref('');
const searchResults = ref<ScryfallCard[]>([]);
const selectedCard = ref<ScryfallCard | null>(null);
const selectedCardId = ref<string | null>(null);
const isSearching = ref(false);
const searchError = ref('');
const quantity = ref(1);
const isAdding = ref(false);

const debouncedSearch = debounce(async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    return;
  }

  isSearching.value = true;
  searchError.value = '';

  try {
    const encodedQuery = encodeURIComponent(searchQuery.value);
    const response = await fetch(
      `https://api.scryfall.com/cards/search?q=${encodedQuery}&order=name&unique=cards`
    );

    if (!response.ok) {
      if (response.status === 404) {
        searchResults.value = [];
        searchError.value = 'No cards found matching your search.';
      } else {
        throw new Error(`Scryfall API error: ${response.status}`);
      }
      return;
    }

    const data = await response.json();
    searchResults.value = data.data || [];

    if (searchResults.value.length === 0) {
      searchError.value = 'No cards found matching your search.';
    }
  } catch (error) {
    console.error('Search error:', error);
    searchError.value = 'Failed to search for cards. Please try again.';
    searchResults.value = [];
  } finally {
    isSearching.value = false;
  }
}, 500);


const searchCards = () => {
  debouncedSearch();
};

const selectCard = (card: ScryfallCard) => {
  selectedCard.value = card;
  selectedCardId.value = card.id;
  quantity.value = isCommanderCard(card) ? 1 : 1;
};

const isCommanderCard = (card: ScryfallCard) => {
  //removed the check for commanders because fuck it
  return false;
};

const incrementQuantity = () => {
  if (selectedCard.value && !isCommanderCard(selectedCard.value)) {
    quantity.value++;
  }
};

const decrementQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement;
  img.src = card_back;
};

const addCardToDeck = async () => {
  if (!selectedCard.value) return;

  isAdding.value = true;

  try {
    console.log(selectedCard.value);
    emit('add-card', selectedCard.value, quantity.value);
  } catch (error) {
    console.error('Error preparing card data:', error);
    searchError.value = 'Failed to add card. Please try again.';
  } finally {
    isAdding.value = false;
  }
};

watch(() => props.show, (newVal) => {
  if (!newVal) {
    searchQuery.value = '';
    searchResults.value = [];
    selectedCard.value = null;
    selectedCardId.value = null;
    searchError.value = '';
    quantity.value = 1;
  }
});
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
  max-width: 800px;
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

.search-section {
  margin-bottom: 1rem;
}

.search-input-wrapper {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.search-input {
  flex-grow: 1;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
}

.search-button {
  padding: 0.5rem 1rem;
  background-color: #16a34a;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.search-button:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.error-message {
  color: #dc2626;
  font-size: 0.875rem;
}

.loading-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #16a34a;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.results-section h3 {
  margin-bottom: 0.5rem;
}

.search-results {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 0.5rem;
  max-height: 300px;
  overflow-y: auto;
}

.search-result-card {
  display: flex;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.search-result-card:hover {
  background-color: var(--color-background);
}

.search-result-card.selected {
  background-color: var(--color-background);
  border-color: #3b82f6;
}

.card-image {
  width: 60px;
  height: 84px;
  margin-right: 0.75rem;
  border-radius: 4px;
}

.card-details {
  flex-grow: 1;
}

.card-details h4 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
}

.card-details p {
  margin: 0 0 0.25rem 0;
  font-size: 0.75rem;
}

.type-line {
  color: #6b7280;
}

.set-info {
  font-size: 0.7rem;
  color: #9ca3af;
}

.no-results, .initial-state {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.hint {
  font-size: 0.875rem;
  font-style: italic;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.selected-card-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem;
  background-color: var(--color-background);
  border-radius: 4px;
}

.quantity-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.quantity-buttons {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.quantity-buttons button {
  width: 24px;
  height: 24px;
  border: 1px solid #d1d5db;
  background-color: var(--color-background);
  border-radius: 4px;
  cursor: pointer;
}

.quantity-buttons span {
  margin: 0 0.5rem;
  font-weight: bold;
}

.commander-note {
  font-size: 0.875rem;
  color: #6b7280;
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

.btn-primary {
  background-color: #16a34a;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  min-width: 100px;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}
</style>