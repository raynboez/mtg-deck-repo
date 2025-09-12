<template>
  <div v-if="show" class="modal-overlay">
    <div class="modal-content bg-sidebar">
      <h2>Assign Deck Details</h2>
      
      <form @submit.prevent="submitDeckDetails">
        <!-- Deck Name -->
        <div class="form-group">
          <label for="deckName">Deck Name</label>
          <input 
            id="deckName" 
            v-model="deckDetails.name" 
            type="text" 
            required
            class="form-control"
          >
        </div>
        
        <!-- Description -->
        <div class="form-group">
          <label for="deckDescription">Description</label>
          <textarea 
            id="deckDescription" 
            v-model="deckDetails.description" 
            class="form-control"
            rows="3"
          ></textarea>
        </div>
        
        <div class="form-group">
          <label>Commander(s)</label>
          <div v-for="(card, index) in potentialCommanders" :key="card.card_id" class="form-check">
            <input
              type="checkbox"
              :id="'commander-' + card.id"
              v-model="deckDetails.commanders"
              :value="card.card_id"
              class="form-check-input"
            >
            <label :for="'commander-' + card.id" class="form-check-label">
              {{ card.card_name }}
            </label>
          </div>
        </div>
        
        <!-- Companion Selection 
        <div v-if="potentialCompanions.length > 0" class="form-group">
          <label>Companion(s)</label>
          <div v-for="card in potentialCompanions" :key="card.id" class="form-check">
            <input
              type="checkbox"
              :id="'companion-' + card.id"
              v-model="deckDetails.companions"
              :value="card.id"
              class="form-check-input"
            >
            <label :for="'companion-' + card.id" class="form-check-label">
              {{ card.name }}
            </label>
          </div>
        </div>
        -->

        <div class="form-group ">
          <label class="custom-select" for="powerLevel">Commander Bracket:
          <select 
            id="powerLevel" 
            v-model="deckDetails.power_level" 
            required
          >
            <option value="">Select deck bracket</option>
            <option value="1">1 - Jank</option>
            <option value="2">2 - Casual</option>
            <option value="3">3 - Focused</option>
            <option value="4">4 - Optimized</option>
            <option value="5">5 - Competitive</option>
          </select>
        </label>
        </div>
        
        <div class="modal-actions">
          <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Deck</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    show: Boolean,
    potentialCommanders: Array,
    initialDeckData: Object
  },
  
  data() {
    return {
      deckDetails: {
        name: '',
        description: '',
        commanders: [],
        power_level: ''
      }
    };
  },
  
  methods: {
    closeModal() {
      this.$emit('close');
    },
    
    submitDeckDetails() {
      this.$emit('save', this.deckDetails);
      this.closeModal();
    },
    
    initializeForm() {
      if (this.initialDeckData) {
        this.deckDetails = {
          name: this.initialDeckData.deck_name || '',
          description: this.initialDeckData.description || '',
          commanders: [...(this.initialDeckData.commanders || [])],
          power_level: this.initialDeckData.power_level?.toString() || ''
        };
      }
    }
  },
  
  watch: {
    show(newVal) {
      if (newVal) {
        this.initializeForm();
      }
    }
  },
  
  mounted() {
    if (this.show && this.initialDeckData) {
      this.initializeForm();
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
  padding: 2rem;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 1rem;
}

.form-control {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-check {
  margin-bottom: 0.5rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
}

.btn-primary {
  background: #007bff;
  color: white;
  border: none;
}

label.custom-select {
    position: relative;
    display: inline-block;

}

.custom-select select {
    display: inline-block;
    padding: 4px 3px 3px 5px;
    margin: 0;
    font: inherit;
    outline:none;
    line-height: 1.2;
    background: white;
    color:black;
    border:0;
}

.no-pointer-events .custom-select:after {
    content: none;
}
</style>