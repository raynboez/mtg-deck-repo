<script setup lang="ts">
import DeckViewer from '../components/DeckViewer.vue'
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deck View',
        href: '/deck/${deckId}',
    },
];
</script>

<template>
    <Head title="Deck" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">      
            <deck-viewer 
                v-if="deck && cards && reverse" 
                :deck="deck" 
                :cards="cards"
                :reverse="reverse"
                :commanders=commanders
                :exportText="exportText" 
                />
            <div v-else>Loading deck...</div>
        </div>
    </AppLayout>
</template>


<script lang="ts">


export default {
  components: {
    DeckViewer
  },
  props: {
    deck_id: Number,
  },
  data() {
    return {
      deck: null,
      cards: null,
      reverse: null,
      commanders: [],
      exportText: ''
    }
  },

  async created() {
    await this.fetchDeck(this.deck_id)
  },

  methods: {
    async fetchDeck(deckId: any) {
      try {
        const response = await axios.get(`/api/decks/${deckId}`)
        this.deck = response.data.deck
        this.cards = response.data.cards
        this.reverse = response.data.reverse
        this.commanders = Array.isArray(response.data.commanders) ? response.data.commanders : JSON.parse(response.data.commanders);
        this.exportText = response.data.export
      } catch (error) {
        console.error('Error fetching deck:', error)
      }
    }
  }
}
</script>