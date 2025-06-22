<script>
export default {
  props: {
    deck: {
      type: Object,
      required: true
    },
    cards: {
      type: Array,
      required: true
    }
  },

  data() {
    return {
      previewCard: null
    }
  },

  computed: {
    totalCards() {
      return this.cards.reduce((total, card) => total + card.count, 0)
    },

    deckStats() {
      return [
        { label: 'Creatures', value: this.countCardsByType('Creature') },
        { label: 'Instants', value: this.countCardsByType('Instant') },
        { label: 'Sorceries', value: this.countCardsByType('Sorcery') },
        { label: 'Lands', value: this.countCardsByType('Land') },
        { label: 'Avg CMC', value: this.calculateAvgCMC().toFixed(2) }
      ]
    },

    groupedCards() {
      const groups = {
        'Creatures': [],
        'Instants': [],
        'Sorceries': [],
        'Enchantments': [],
        'Artifacts': [],
        'Planeswalkers': [],
        'Lands': [],
        'Other': []
      }

      this.cards.forEach(card => {
        const type = this.determineCardType(card.type_line)
        if (groups[type]) {
          groups[type].push(card)
        } else {
          groups['Other'].push(card)
        }
      })

      for (const type in groups) {
        groups[type].sort((a, b) => a.name.localeCompare(b.name))
      }

      return groups
    }
  },

  methods: {
    determineCardType(typeLine) {
      if (typeLine.includes('Creature')) return 'Creatures'
      if (typeLine.includes('Instant')) return 'Instants'
      if (typeLine.includes('Sorcery')) return 'Sorceries'
      if (typeLine.includes('Enchantment')) return 'Enchantments'
      if (typeLine.includes('Artifact')) return 'Artifacts'
      if (typeLine.includes('Planeswalker')) return 'Planeswalkers'
      if (typeLine.includes('Land')) return 'Lands'
      return 'Other'
    },

    countCardsByType(type) {
      return this.cards
        .filter(card => card.type_line.includes(type))
        .reduce((total, card) => total + card.count, 0)
    },

    calculateAvgCMC() {
      const totalCMC = this.cards.reduce((total, card) => {
        if (!card.mana_cost) return total
        return total + (this.parseManaCost(card.mana_cost) * card.count)
      }, 0)

      return totalCMC / this.totalCards
    },

    parseManaCost(manaCost) {
      if (!manaCost) return 0
      return manaCost.replace(/[^{}\d]/g, '')
        .split('')
        .reduce((total, char) => {
          if (char === '{') return total
          if (char === '}') return total + 1
          if (!isNaN(char)) return total + parseInt(char)
          return total + 1
        }, 0)
    },

    formatManaCost(manaCost) {
      if (!manaCost) return ''
      return manaCost.replace(/{([^}]+)}/g, '<span class="mana-symbol">$1</span>')
    },

    showCardPreview(card) {
      this.previewCard = card
    }
  }
}
</script>

<template>
  <div class="deck-viewer">
    <div class="deck-header">
      <h2>{{ deck.name }}</h2>
      <div class="deck-meta">
        <span>Format: {{ deck.format }}</span>
        <span>Total Cards: {{ totalCards }}</span>
      </div>
    </div>

    <div class="deck-stats">
      <div class="stat-item" v-for="stat in deckStats" :key="stat.label">
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-label">{{ stat.label }}</div>
      </div>
    </div>

    <div class="card-display">
      <div class="card-group" v-for="(cards, type) in groupedCards" :key="type">
        <h3>{{ type }} ({{ cards.length }})</h3>
        <div class="card-list">
          <div 
            class="card-item" 
            v-for="card in cards" 
            :key="card.id"
            @mouseenter="showCardPreview(card)"
          >
            <span class="card-count">{{ card.count }}x</span>
            <span class="card-name">{{ card.name }}</span>
            <span class="card-mana-cost" v-html="formatManaCost(card.mana_cost)"></span>
          </div>
        </div>
      </div>
    </div>

    <div class="card-preview" v-if="previewCard" @click="previewCard = null">
      <div class="preview-content" @click.stop>
        <img :src="previewCard.image_url" :alt="previewCard.name" />
        <div class="preview-details">
          <h3>{{ previewCard.name }}</h3>
          <p v-html="formatManaCost(previewCard.mana_cost)"></p>
          <p>{{ previewCard.type_line }}</p>
          <p>{{ previewCard.oracle_text }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.deck-viewer {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.deck-header {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #ddd;
}

.deck-meta {
  display: flex;
  gap: 20px;
  color: #666;
}

.deck-stats {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f5f5f5;
  border-radius: 5px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
}

.stat-label {
  font-size: 0.8rem;
  color: #666;
}

.card-group {
  margin-bottom: 30px;
}

.card-group h3 {
  margin-bottom: 10px;
  padding-bottom: 5px;
  border-bottom: 1px solid #eee;
}

.card-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 5px;
}

.card-item {
  display: flex;
  align-items: center;
  padding: 5px 10px;
  background: #fff;
  border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  cursor: pointer;
}

.card-item:hover {
  background: #f0f0f0;
}

.card-count {
  min-width: 25px;
  font-weight: bold;
  margin-right: 10px;
}

.card-name {
  flex-grow: 1;
}

.card-mana-cost {
  margin-left: 10px;
}

.mana-symbol {
  display: inline-block;
  width: 15px;
  height: 15px;
  background: #ccc;
  border-radius: 50%;
  text-align: center;
  line-height: 15px;
  font-size: 10px;
  margin: 0 1px;
}

.card-preview {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.preview-content {
  background: white;
  padding: 20px;
  border-radius: 5px;
  max-width: 800px;
  display: flex;
  gap: 20px;
}

.preview-content img {
  width: 300px;
  height: auto;
  border-radius: 5px;
}

.preview-details {
  flex: 1;
}
</style>