<template>
    <div class="army-import">
        <h2 class="header">Import Warhammer Army or Killteam</h2>

        <form @submit.prevent="submitImport">
            <div class="import-options">
                <div class="option-tabs">
                    <button type="button" :class="{ active: activeTab === 'w40k' }" @click="activeTab = 'url'">
                        Warhammer 40k
                    </button>
                    <button type="button" :class="{ active: activeTab === 'kt' }" @click="activeTab = 'text'">
                        Killteam
                    </button>
                </div>

                <div v-if="activeTab === 'w40k'" class="form-group">
                    <label for="army_name">Army Name</label>
                    <textarea
                      id="army_name"
                      v-model="url"
                      placeholder="Enter army name"
                      rows="1"
                      :required="activeTab === 'w40k'"
                    ></textarea>
                    <label for="army_description">Army Description</label>
                    <textarea
                      id="army_description"
                      v-model="url"
                      placeholder="Enter army description"
                      rows="1"
                      :required="activeTab === 'w40k'"
                    ></textarea>
                    <label for="army_faction">Faction</label>
                    <Select
                      id="army_faction"
                      :required="activeTab === 'w40k'"
                    >
                      <option value="" selected="selected">Select Faction</option>
                    </Select>
                    <label for="army_subfaction">Subfaction</label>
                    <Select
                      id="army_subfaction"
                      :required="activeTab === 'w40k'"
                    >
                      <option value="" selected="selected">Select Faction First</option>
                    </Select>
                </div>

                <div v-if="activeTab === 'kt'" class="form-group">
                    <label for="kt-name">Killteam name</label>
                    <textarea
                        id="kt-name"
                        v-model="deckText"
                        placeholder="Paste your decklist here (one card per line)"
                        rows="1"
                        :required="activeTab === 'kt'"
                    >
                    </textarea>  
                </div>
            </div>

            <button type="submit" :disabled="loading">
                {{ loading ? 'Importing...' : 'Import Team' }}
            </button>

            <div v-if="error" class="error-message">
                {{ error }}
            </div>

            <div v-if="success" class="success-message">
                Team imported successfully.
            </div>
        </form>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    
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
            if(this.activeTab==='w40k'){
              formData.append();
            } else {
              formData.append();
            }
            
            
            console.log(formData);

            const response = await axios.post('/api/army/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            
            this.success = true;

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