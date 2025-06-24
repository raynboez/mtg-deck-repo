<template>
    <div class="deck-import">
        <h2>Import MTG Deck</h2>

        <form @submit.prevent="submitImport">
            <div class="form-group">
                <label for="deck-name">Deck Name</label>
                <input id="deck-name" v-model="form.deck_name" type="text" required>
            </div>

            <div class="form-group">
                <label for="deck-description">Description</label>
                <textarea id="deck-description" v-model="form.deck_description"></textarea>
            </div>

            <div class="import-options">
                <div class="option-tabs">
                    <button type="button" :class="{ active: activeTab === 'file' }" @click="activeTab = 'file'">
                        Upload File
                    </button>
                    <button type="button" :class="{ active: activeTab === 'text' }" @click="activeTab = 'text'">
                        Paste Decklist
                    </button>
                </div>

                <div v-if="activeTab === 'file'" class="form-group">
                    <label for="csv-file">Deck File (CSV/TXT)</label>
                    <input
                        id="csv-file"
                        type="file"
                        accept=".csv,.txt"
                        @change="handleFileUpload"
                        :required="activeTab === 'file'"
                    >
                    <small>Accepted formats: Moxfield => CSV, TXT (one card per line)</small>
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
            @close="showAssignmentModal = false"
            @save="saveDeckDetails"
        />
    </div>
</template>

<script>
import DeckAssignmentModal from './DeckAssignmentModal.vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
export default {
    components: {
        DeckAssignmentModal
    },  
    data() {
        return {
            form: {
                deck_name: '',
                deck_description: '',
                file: null
            },
            deckText: '',
            activeTab: 'file',
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
        handleFileUpload(event) {
            this.form.file = event.target.files[0];
        },
        
        async saveDeckDetails(details) {
            try {
                const response = await axios.put(`/api/decks/${this.importedDeckId}`, {
                name: details.name,
                description: details.description,
                commanders: details.commanders,
                power_level: details.power_level
                });
                
                window.location.href = `/deck/${this.importedDeckId}`;
            } catch (error) {
                console.error('Failed to save deck details:', error);
            }
        },

        async submitImport() {
            this.loading = true;
            this.error = null;
            this.success = false;


            try {
                const formData = new FormData();
                formData.append('deck_name', this.form.deck_name);
                formData.append('deck_description', this.form.deck_description)
                if(this.activeTab === 'text')
                {
                    formData.append('text', this.deckText);
                }
                else
                {
                    formData.append('file', this.form.file);
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

                this.form = {
                    deck_name: '',
                    deck_description: '',
                    file: null,
                    text: ''
                };
                if(this.activeTab === 'file')
                {
                    document.getElementById('csv-file').value = '';
                }

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
    .error-message {
        color:red;
        margin-top:1rem;
    }
    .success-message {
        color:green;
        margin-top:1rem;
    }
</style>