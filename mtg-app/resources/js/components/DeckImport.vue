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
            <div class="form-group">
                <label for="csv-file">Deck CSV file</label>
                <input
                    id="csv-file"
                    type="file"
                    accept=".csv,.txt"
                    @change="handleFileUpload"
                    required
                >
                <small>Format:TBD</small>

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
    </div>
</template>

<script>
import axios from 'axios';
export default {
    data() {
        return {
            form: {
                deck_name: '',
                deck_description: '',
                file: null
            },
            loading: false,
            error: null,
            success: false
        }
    },
    methods: {
        handleFileUpload(event) {
            this.form.file = event.target.files[0];
        },
        async submitImport() {
            this.loading = true;
            this.error = null;
            this.success = false;

            try {
                const formData = new FormData();
                formData.append('deck_name', this.form.deck_name);
                formData.append('deck_description', this.form.deck_description)
                formData.append('file', this.form.file);
                console.log(formData);

                const response = await axios.post('/api/decks/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });
                
                this.success = true;
                this.$emit('deck-imported', response.data);

                this.form = {
                    deck_name: '',
                    deck_description: '',
                    file: null
                };
                document.getElementById('csv-file').value = '';
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