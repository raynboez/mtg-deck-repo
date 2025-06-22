<script setup lang="ts">
import DeckViewer from '../components/DeckViewer.vue'
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
interface Deck {
  id: number
  title: string
  // other properties
}

interface Props {
  deck: Deck
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">      
            <deck-viewer 
                v-if="deck && cards" 
                :deck="deck" 
                :cards="cards" 
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

  data() {
    return {
      deck: null,
      cards: null
    }
  },

  created() {
    this.fetchDeck(props.deck.id)
  },

  methods: {
    async fetchDeck(deckId: any) {
      try {
        const response = await axios.get(`/api/decks/${deckId}`)
        this.deck = response.data.deck
        this.cards = response.data.cards
      } catch (error) {
        console.error('Error fetching deck:', error)
      }
    }
  }
}
</script>