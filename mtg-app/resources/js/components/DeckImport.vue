<template>
    <div class="deck-import">
        <h2 class="header">Import MTG Deck</h2>

        <form @submit.prevent="submitImport">
            <div class="import-options">
                <div class="option-tabs">
                    <button type="button" :class="{ active: activeTab === 'url' }" @click="activeTab = 'url'">
                        Upload from URL
                    </button>
                    <button type="button" :class="{ active: activeTab === 'text' }" @click="activeTab = 'text'">
                        Paste Decklist
                    </button>
                </div>

                <div v-if="activeTab === 'url'" class="form-group">
                    <label for="deck_url">Deck URL</label>
                    <textarea
                      id="deck_url"
                      v-model="url"
                      placeholder="Enter deck URL (e.g. Archidekt)"
                      rows="1"
                      :required="activeTab === 'url'"
                    ></textarea>
                    <small>Accepted sites: Archidekt</small>
                </div>

                <div v-if="activeTab === 'text'" class="form-group">
                    <label for="deck-text">Decklist Text</label>
                    <textarea
                        id="deck-text"
                        v-model="deckText"
                        placeholder="Paste your decklist here (one card per line)"
                        rows="10"
                        :required="activeTab === 'text'"
                    ></textarea>
                    <small>Format: Moxfield => [Count] [Card Name] [(Set Code)] [Collector Number] [?Foil] (e.g. "4 Lightning Bolt (M10) 146 *F*")</small>
                </div>
            </div>

            <button type="submit" :disabled="loading">
                {{ loading ? 'Importing...' : 'Import Deck' }}
            </button>

            <div v-if="error" class="error-message">
                {{ error }}
            </div>

            <div v-if="success" class="success-message">
                Deck imported successfully.
            </div>
        </form>

        <DeckAssignmentModal
            v-if="showAssignmentModal"
            :show="showAssignmentModal"
            :potential-commanders="potentialCommanders"
            :potential-companions="potentialCompanions"
            :url="url"
            @close="showAssignmentModal = false"
            @save="saveDeckDetails"
        />
    </div>
</template>

<script>
import DeckAssignmentModal from './DeckAssignmentModal.vue';
import axios from 'axios';

export default {
    components: {
        DeckAssignmentModal
    },  
    data() {
        return {
            form: {
                deck_name: 'placeholder',
                deck_description: 'placeholder',
            },
            url: '',
            deckText: '',
            activeTab: 'url',
            loading: false,
            error: null,
            success: false,
            showAssignmentModal: false,
            potentialCommanders: [],
            potentialCompanions: [],
            importedDeckId: null
        }
    },
    methods: {        
        async saveDeckDetails(details) {
          try {
              const response = await axios.put(`/api/decks/${this.importedDeckId}`, {
                  name: details.name,
                  description: details.description,
                  commanders: details.commanders,
                  power_level: details.power_level,
                  url: details.url
              });
              
              this.resetForm();
              
              window.location.href = `/deck/${this.importedDeckId}`;
          } catch (error) {
              console.error('Failed to save deck details:', error);
          }
      },

      resetForm() {
          this.form = {
              deck_name: '',
              deck_description: '',
          };
          this.url = '';
          this.deckText = '';
          this.activeTab = 'url';
      },

      async submitImport() {
        this.loading = true;
        this.error = null;
        this.success = false;

        try {
            const formData = new FormData();
            formData.append('deck_name', this.form.deck_name);
            formData.append('deck_description', this.form.deck_description)
            
            if(this.activeTab === 'text') {
                formData.append('text', this.deckText);
            } else {
                formData.append('url', this.url);
            }
            
            console.log(formData);

            const response = await axios.post('/api/decks/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            
            this.success = true;
            this.importedDeckId = response.data.deck_id;
            this.potentialCommanders = response.data.potential_commanders;
            this.potentialCompanions = response.data.potential_companions;
            
            this.showAssignmentModal = true;

        } catch (error) {
            console.error(error);
            this.error = error.response?.data?.message || 'Failed to import';
        } finally {
            this.loading = false;
        }
      }
    }
}
</script>

<style scoped>
/* General Form Styling */
form {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
  background: var(--card);
  color: var(--card-foreground);
  border-radius: var(--radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--border);
}

h2 {
  text-align: center;
  color: var(--foreground);
  margin-bottom: 25px;
  font-size: 28px;
  font-weight: 600;
  position: relative;
  padding-bottom: 10px;
}

h2::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background: linear-gradient(90deg, var(--chart-2), var(--primary));
  border-radius: 3px;
}

/* Import Options (tabs) */
.import-options {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 20px;
  background: var(--card);
}

.option-tabs {
  display: flex;
  border-bottom: 1px solid var(--border);
}

.option-tabs button {
  flex: 1;
  padding: 12px 16px;
  background: none;
  border: none;
  cursor: pointer;
  font-weight: 500;
  color: var(--muted-foreground);
  transition: all 0.3s ease;
  position: relative;
  font-size: 15px;
}

.option-tabs button:hover {
  color: var(--foreground);
  background-color: var(--accent);
}

.option-tabs button.active {
  color: var(--primary);
  font-weight: 600;
}

.option-tabs button.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background-color: var(--chart-2);
}

/* Form Groups */
.form-group {
  padding: 20px;
  animation: fadeIn 0.3s ease-out;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--foreground);
}

input[type="file"] {
  width: 100%;
  padding: 10px;
  border: 1px solid var(--input);
  border-radius: var(--radius);
  margin-bottom: 8px;
  background: var(--muted);
  transition: border 0.3s;
  color: var(--foreground);
}

input[type="file"]:focus {
  border-color: var(--chart-2);
  outline: none;
}

textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid var(--input);
  border-radius: var(--radius);
  resize: vertical;
  min-height: 150px;
  font-family: inherit;
  background: var(--muted);
  transition: border 0.3s;
  color: var(--foreground);
}

textarea:focus {
  border-color: var(--chart-2);
  outline: none;
}

small {
  display: block;
  color: var(--muted-foreground);
  font-size: 0.85em;
  margin-top: 8px;
  line-height: 1.4;
}

/* Submit Button */
button[type="submit"] {
  display: block;
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
  margin-top: 20px;
}

button[type="submit"]:hover {
  background: linear-gradient(135deg, var(--chart-3), var(--primary));
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

button[type="submit"]:active {
  transform: translateY(0);
}

button[type="submit"]:disabled {
  background: var(--muted);
  color: var(--muted-foreground);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Messages */
.error-message {
  color: var(--destructive);
  padding: 10px;
  margin-top: 15px;
  background: color-mix(in srgb, var(--destructive) 10%, transparent);
  border-radius: var(--radius);
  border-left: 4px solid var(--destructive);
}

.success-message {
  color: var(--chart-2);
  padding: 10px;
  margin-top: 15px;
  background: color-mix(in srgb, var(--chart-2) 10%, transparent);
  border-radius: var(--radius);
  border-left: 4px solid var(--chart-2);
}

/* Animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive Design */
@media (max-width: 480px) {
  form {
    padding: 15px;
  }
  
  h2 {
    font-size: 24px;
  }
  
  .option-tabs button {
    padding: 10px 8px;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>