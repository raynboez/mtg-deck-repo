<script setup>


import axios from 'axios';
import { X, XCircle } from 'lucide-vue-next';
</script>


<template>
    <div class="match-tracker bg-background text-foreground p-6 rounded-lg border border-border shadow-sm">
        <h2 class="header text-2xl font-semibold text-center mb-6 pb-4 border-b border-border">Record MTG Match</h2>

        <form @submit.prevent="submitMatch" class="space-y-6">
            <!-- Move date played and format here -->
            <div class="match-details grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="form-group">
                    <label for="date-played" class="block text-sm font-medium mb-1">Date Played</label>
                    <input 
                        id="date-played" 
                        type="datetime-local" 
                        v-model="matchDetails.date_played"
                        class="w-full p-2 border border-input rounded-md bg-background"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="format" class="block text-sm font-medium mb-1">Format</label>
                    <select id="format" v-model="matchDetails.format" class="w-full p-2 border border-input rounded-md bg-background" required>
                        <option value="" disabled>Select a format</option>
                        <option value="Gulag Commander - Season 0">Gulag Commander - Season 0</option>
                        <option value="Casual Commander">Casual Commander</option>
                        <option value="Custom Game">Custom Game</option>
                    </select>
                </div>

                <!-- Add bracket dropdown -->
                <div class="form-group">
                    <label for="bracket" class="block text-sm font-medium mb-1">Bracket</label>
                    <select id="bracket" v-model="matchDetails.bracket" class="w-full p-2 border border-input rounded-md bg-background" required>
                        <option value="1">1</option>
                        <option value="2" selected>2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
            </div>
            <div class="actions flex justify-end gap-3">
                <button type="button" class="add-player-btn bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 transition-colors" @click="addPlayer">
                    <i class="fas fa-plus mr-2"></i> Add Player
                </button>
            
            
                <button type="button" @click="resetForm" class="px-4 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground transition-colors">Reset</button>
                <button type="submit" :disabled="loading" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 disabled:bg-muted disabled:text-muted-foreground transition-colors">
                    {{ loading ? 'Saving...' : 'Save Match' }}
                </button>
            </div>

            <div v-if="error" class="error-message p-3 bg-destructive/20 text-destructive rounded-md">
                {{ error }}
            </div>

            <div v-if="success" class="success-message p-3 bg-green-500/20 text-green-600 rounded-md dark:text-green-400">
                Match recorded successfully.
            </div>

            <div class="player-grid grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="player-card bg-card text-card-foreground p-4 rounded-md border border-border" v-for="(player, index) in players" :key="index">
                    <div class="player-header flex justify-between items-center mb-4">
                        <div class="player-title font-medium">Player {{ index + 1 }}</div>
                        <button 
                                v-if="players.length > 1" 
                                type="button" 
                                class="remove-player rounded-full flex items-center justify-center" 
                                @click="removePlayer(index)"
                                >
                                <X class="w-7 h-7 " />
                        </button>
                    </div>
                    
                    <div class="player-form grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="form-group md:col-span-2">
                            <label :for="'player-name-' + index" class="block text-sm font-medium mb-1">Player Name</label>
                            <select 
                                :id="'player-name-' + index" 
                                v-model="player.user_id"
                                @change="onUserChange(index, $event)"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                                <option value="" disabled>Select a player</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group md:col-span-2">
                            <label :for="'player-deck-' + index" class="block text-sm font-medium mb-1">Deck</label>
                            <select 
                                :id="'player-deck-' + index" 
                                v-model="player.deck_id"
                                :disabled="!player.user_id"
                                :class="{'opacity-50 cursor-not-allowed': !player.user_id}"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                                <option value="" disabled>Select a deck</option>
                                <option 
                                    v-for="deck in getUserDecks(player.user_id)" 
                                    :key="deck.deck_id" 
                                    :value="deck.deck_id"
                                >
                                    {{ deck.deck_name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label :for="'starting-life-' + index" class="block text-sm font-medium mb-1">Starting Life</label>
                            <input 
                                :id="'starting-life-' + index" 
                                type="number" 
                                v-model.number="player.starting_life"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label :for="'final-life-' + index" class="block text-sm font-medium mb-1">Final Life</label>
                            <input 
                                :id="'final-life-' + index" 
                                type="number" 
                                v-model.number="player.final_life"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label :for="'turn-order-' + index" class="block text-sm font-medium mb-1">Turn Order</label>
                            <input 
                                :id="'turn-order-' + index" 
                                type="number" 
                                v-model.number="player.turn_order"
                                min="1"
                                :max="players.length"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label :for="'order-lost-' + index" class="block text-sm font-medium mb-1">Elimination Order</label>
                            <input 
                                :id="'order-lost-' + index" 
                                type="number" 
                                v-model.number="player.order_lost"
                                min="0"
                                :max="players.length"
                                :disabled="player.winner"
                                :class="{'opacity-50 cursor-not-allowed': player.winner}"
                                class="w-full p-2 border border-input rounded-md bg-background"
                            >
                        </div>
                        
                        <div class="form-group md:col-span-2 flex items-center">
                            <input 
                                type="checkbox" 
                                :id="'winner-' + index" 
                                v-model="player.winner"
                                @change="setWinner(index)"
                                class="mr-2"
                            >
                            <label :for="'winner-' + index" class="text-sm font-medium">Winner</label>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                users:[],
                players: [
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 1,
                        order_lost: null,
                        winner: false
                    },
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 2,
                        order_lost: null,
                        winner: false
                    },
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 3,
                        order_lost: null,
                        winner: false
                    },
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 4,
                        order_lost: null,
                        winner: false
                    }
                ],
                matchDetails: {
                    date_played: new Date().toISOString().slice(0, 16),
                    format: '',
                    bracket: '2'
                },
                loading: false,
                error: null,
                success: false
            };
        },
        methods: {
            async fetchUsers()
            {
                try {
                    const response = await axios.get('/api/users');
                    this.users = response.data.map((user) => 
                    ({
                    id: user.user_id,
                    name: user.name,
                    decks: [],
                    }));
                    this.users.forEach(user => this.fetchUserDecks(user.id));
                    } catch (error) {
                        console.error('Error fetching users:', error);
                    }
            },
            async fetchUserDecks(userId)
            {
                try 
                {
                    let apiReq = '/api/decks/user/' + userId;
                    const response = await axios.get(apiReq);
                    const user = this.users.find(u => u.id === userId);
                    if(user)
                    {
                        user.decks = response.data.map((deck) => ({
                            deck_name: deck.deck_name,
                            deck_id: deck.deck_id,
                        }));
                    }
                } 
                catch (error) 
                {
                    console.error('Error fetching decks:', error);
                }

            },
            onUserChange(playerIndex, event) {
                const userId = event.target.value;
                this.players[playerIndex].user_id = userId;
                this.players[playerIndex].deck_id = '';
                const user = this.users.find(u => u.id === userId);
                if (user && (!user.decks || user.decks.length === 0)) {
                    this.fetchUserDecks(userId);
                }
            },            
            getUserDecks(userId) {
                
                const user = this.users.find(u => Number(u.id) === Number(userId));
                if (!user || !user.decks || !Array.isArray(user.decks)) {
                    return [];
                }
                
                return user.decks;
            },
            addPlayer() {
                this.players.push({
                    user_id: '',
                    deck_id: '',
                    starting_life: 40,
                    final_life: null,
                    turn_order: this.players.length + 1,
                    order_lost: null,
                    winner: false
                });
            },
            removePlayer(index) {
                this.players.splice(index, 1);
                this.players.forEach((player, idx) => {
                    player.turn_order = idx + 1;
                });
            },
            setWinner(winnerIndex) {
                this.players.forEach((player, index) => {
                    if (index !== winnerIndex) {
                        player.winner = false;
                    } else {
                        player.order_lost = null;
                    }
                });
            },
            async submitMatch() {
                this.loading = true;
                this.error = null;
                this.success = false;
                
                if (!this.validateForm()) {
                    this.loading = false;
                    return;
                }
                
                try {
                    const matchData = {
                        players: this.players,
                        date_played: this.matchDetails.date_played,
                        format: this.matchDetails.format,
                        bracket: this.matchDetails.bracket
                    };
                    
                    const response = await axios.put('/api/matchRecord', matchData);
                    
                    this.loading = false;
                    this.success = true;
                    console.log('API Response:', response.data);
                    
                    setTimeout(() => {
                        this.resetForm();
                    }, 2000);
                    
                } catch (error) {
                    this.loading = false;
                    
                    if (error.response) {
                        console.error('API Error Response:', error.response.data);
                        this.error = error.response.data.message || 'An error occurred while saving the match';
                    } else if (error.request) {
                        console.error('API Error Request:', error.request);
                        this.error = 'No response from server. Please check your connection.';
                    } else {
                        console.error('API Error:', error.message);
                        this.error = 'An unexpected error occurred.';
                    }
                }
            },
            validateForm() {
                const hasWinner = this.players.some(player => player.winner);
                
                if (!hasWinner) {
                    this.error = 'Please mark one player as the winner.';
                    return false;
                }
                
                const allNamesFilled = this.players.every(player => player.name !== '');
                
                if (!allNamesFilled) {
                    this.error = 'Please select a player name for all participants.';
                    return false;
                }
                
                const allDecksFilled = this.players.every(player => player.deck_id !== '');
                
                if (!allDecksFilled) {
                    this.error = 'Please select a deck for all participants.';
                    return false;
                }
                
                return true;
            },
            resetForm() {
                this.players = [
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 1,
                        order_lost: null,
                        winner: false
                    },
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 2,
                        order_lost: null,
                        winner: false
                    },
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 3,
                        order_lost: null,
                        winner: false
                    },
                    {
                        user_id: '',
                        deck_id: '',
                        starting_life: 40,
                        final_life: null,
                        turn_order: 4,
                        order_lost: null,
                        winner: false
                    }
                ];
                
                this.matchDetails = {
                    date_played: new Date().toISOString().slice(0, 16),
                    format: '',
                    bracket: '2'
                };
                
                this.error = null;
                this.success = false;
            }
            
        },
        mounted() {
            this.fetchUsers();
        }
    };
</script>