<script setup>


import axios from 'axios';
import { X, XCircle } from 'lucide-vue-next';
</script>


<template>
    <div class="match-tracker bg-background text-foreground p-6 rounded-lg border border-border shadow-sm">
        <h2 class="header text-2xl font-semibold text-center mb-6 pb-4 border-b border-border">Record Warhammer Match</h2>

        <form @submit.prevent="submitMatch" class="space-y-6">
            <div class="match-details grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                    <label for="game_mode" class="block text-sm font-medium mb-1">Game Mode</label>
                    <select id="game_mode" v-model="matchDetails.game_mode" class="w-full p-2 border border-input rounded-md bg-background" required>
                        <option value="Warhammer 40k">Warhammer 40K</option>
                        <option value="Killteam">Killteam</option>
                    </select>
                </div>


                <div v-if="matchDetails.game_mode === 'Killteam'" class="form-group md:col-span-2">
                                <label :for="critop" class="block text-sm font-medium mb-1">Crit Op Choice</label>
                                <select 
                                    :id="critop" 
                                    v-model="critop"
                                    class="w-full p-2 border border-input rounded-md bg-background"
                                    required
                                >
                                    <option value="" disabled>Select a Crit Op</option>
                                    
                                    <option value="Loot">Loot</option>
                                    <option value="Secure">Secure</option>
                                    <option value="Transmission">Transmission</option>
                                    <option value="Orb">Orb</option>
                                    <option value="Stake Claim">Stake Claim</option>
                                    <option value="Energy Cells">Energy Cells</option>
                                    <option value="Download">Download</option>
                                    <option value="Data">Data</option>
                                    <option value="Reboot">Reboot</option>
                                    
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
                                :disabled="!matchDetails.game_mode"
                                :class="{'opacity-50 cursor-not-allowed': !matchDetails.game_mode}"
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
                            <label :for="'player-army-' + index" class="block text-sm font-medium mb-1">{{ matchDetails.game_mode === 'Killteam' ? 'Kill Team' : 'Army' }}</label>
                            <select 
                                :id="'player-army-' + index" 
                                v-model="player.army_id"
                                :disabled="!player.user_id || !matchDetails.game_mode"
                                :class="{'opacity-50 cursor-not-allowed': !player.user_id || !matchDetails.game_mode}"
                                
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                                <option value="" disabled>{{ matchDetails.game_mode === 'Killteam' ? 'Select a Kill Team' : 'Select an Army' }}</option>
                                <option 
                                    v-for="army in getUserArmies(player.user_id, matchDetails.game_mode)" 
                                    :key="army.army_id" 
                                    :value="army.army_id"
                                >
                                    {{ army.army_name }}
                                </option>
                                <option value="borrow">Borrow an Army</option>
                            </select>
                        </div>


        
                        <div v-if="player.army_id === 'borrow'" class="borrow-fields">
                            <div class="form-group">
                                <label :for="'borrow-user-' + index" class="block text-sm font-medium mb-1">Borrow From</label>
                                <select 
                                    :id="'borrow-user-' + index" 
                                    v-model="player.borrow_user_id"
                                    class="w-full p-2 border border-input rounded-md bg-background"
                                    required
                                >
                                    <option value="" disabled>Select a player</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                            </div>
                        
                        </div>
                        <div v-if="player.army_id === 'borrow'" class="borrow-fields">
                        <div class="form-group">
                                <label :for="'borrow-army-' + index" class="block text-sm font-medium mb-1">Army to Borrow</label>
                                <select 
                                    :id="'borrow-army-' + index" 
                                    v-model="player.borrow_army_id"
                                    :disabled="!player.borrow_user_id"
                                    :class="{'opacity-50 cursor-not-allowed': !player.borrow_user_id}"
                                    class="w-full p-2 border border-input rounded-md bg-background"
                                    required
                                >
                                    <option value="" disabled>Select an army</option>
                                    <option 
                                        v-for="army in getUserArmies(player.borrow_user_id)" 
                                        :key="army.army_id" 
                                        :value="army.army_id"
                                    >
                                        {{ army.army_name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div v-if="matchDetails.game_mode === 'Warhammer 40k'" class="form-group md:col-span-2">
                                <label :for="'player-army-disposition' + index" class="block text-sm font-medium mb-1">Army Disposition</label>
                                <select 
                                    :id="'player-army-disposition' + index" 
                                    v-model="player.primary_objective"
                                    class="w-full p-2 border border-input rounded-md bg-background"
                                    required
                                >
                                    <option value="" disabled>Select a disposition</option>
                                    <option value="Take and Hold">Take and Hold</option>
                                    <option value="Purge the Foe">Purge the Foe</option>
                                    <option value="Disruption">Disruption</option>
                                    <option value="Reconnaissance">Reconnaissance</option>
                                    <option value="Priority Assets">Priority Assets</option>
                                </select>
                            </div>
                        <div v-if="matchDetails.game_mode === 'Killteam'" class="form-group md:col-span-2">
                                <label :for="'player-army-disposition' + index" class="block text-sm font-medium mb-1">Tac Op Choice</label>
                                <select 
                                    :id="'player-army-disposition' + index" 
                                    v-model="player.secondary_objective"
                                    class="w-full p-2 border border-input rounded-md bg-background"
                                    required
                                >
                                    <option value="" disabled>Select a Tac Op</option>
                                    <optgroup label="Seek And Destroy">
                                        <option value="Dominate">Dominate</option>
                                        <option value="Rout">Rout</option>
                                        <option value="Sweep & Clear">Sweep & Clear</option>
                                    </optgroup>
                                    <optgroup label="Security">
                                        <option value="Plant Banner">Plant Banner</option>
                                        <option value="Martyrs">Martyrs</option>
                                        <option value="Envoy">Envoy</option>
                                    </optgroup>
                                    <optgroup label="Infiltration">
                                        <option value="Plant Devices">Plant Devices</option>
                                        <option value="Steal Intelligence">Steal Intelligence</option>
                                        <option value="Track Enemy">Track Enemy</option>
                                    </optgroup>
                                    <optgroup label="Recon">
                                        <option value="Flank">Flank</option>
                                        <option value="Retrieval">Retrieval</option>
                                        <option value="Scout Enemy Movement">Scout Enemy Movement</option>
                                    </optgroup>
                                </select>
                            </div>
                        <div class="form-group">
                            <label :for="'victory-points-' + index" class="block text-sm font-medium mb-1">Victory Points</label>
                            <input 
                                :id="'victory-points-' + index" 
                                type="number" 
                                v-model.number="player.victory_points"
                                min="0"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label :for="'primary-points-' + index" class="block text-sm font-medium mb-1">{{ matchDetails.game_mode === 'Killteam' ? 'Crit Op' : 'Primary Objective Points' }}</label>
                            <input 
                                :id="'primary-points-' + index" 
                                type="number" 
                                v-model.number="player.primary_points"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label :for="'secondary-points-' + index" class="block text-sm font-medium mb-1">{{ matchDetails.game_mode === 'Killteam' ? 'Tac Op' : 'Secondary Objective Points' }}</label>
                            <input 
                                :id="'secondary-points-' + index" 
                                type="number" 
                                v-model.number="player.secondary_points"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label :for="'tertiary-points-' + index" class="block text-sm font-medium mb-1">{{ matchDetails.game_mode === 'Killteam' ? 'Kill Op' : 'Paint Points' }}</label>
                            <input 
                                :id="'tertiary-points-' + index" 
                                type="number" 
                                v-model.number="player.tertiary_points"
                                class="w-full p-2 border border-input rounded-md bg-background"
                                required
                            >
                        </div>
                        
                        
                        
                        <div class="form-group flex items-center">
                            <input 
                                type="checkbox" 
                                :id="'winner-' + index" 
                                v-model="player.is_winner"
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
                        army_id: '',
                        primary_objective: '',
                        secondary_objective: '',
                        victory_points: 0,
                        primary_points: null,
                        secondary_points: null,
                        tertiary_points: null,
                        is_winner: false
                    },
                    {
                        user_id: '',
                        army_id: '',
                        primary_objective: '',
                        secondary_objective: '',
                        victory_points: 0,
                        primary_points: null,
                        secondary_points: null,
                        tertiary_points: null,
                        is_winner: false
                    }
                ],
                matchDetails: {
                    date_played: new Date().toISOString().slice(0, 16),
                    game_mode: 'Warhammer 40k',
                },
                critop: '',     
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
                    armies: [],
                    }));
                    this.users.forEach(user => this.fetchUserArmies(user.id));
                    } catch (error) {
                        console.error('Error fetching users:', error);
                    }
            },
            async fetchUserArmies(userId)
            {
                try 
                {
                    let apiReq = '/api/warhammer/armies/user/' + userId;
                    const response = await axios.get(apiReq);
                    const user = this.users.find(u => u.id === userId);
                    if(user)
                    {
                        user.armies = response.data.map((army) => ({
                            army_name: army.name,
                            army_id: army.army_id,
                            game_mode: army.game_mode
                        }));
                    }
                } 
                catch (error) 
                {
                    console.error('Error fetching armies:', error);
                }

            },
            async fetchUserArmiesByGamemode(userId, gameMode)
            {
                try 
                {
                    let apiReq = '/api/warhammer/armies/user/' + userId + '/gamemode/' + gameMode;
                    const response = await axios.get(apiReq);
                    const user = this.users.find(u => u.id === userId);
                    if(user)
                    {
                        user.armies = response.data.map((army) => ({
                            army_name: army.name,
                            army_id: army.army_id,
                        }));
                    }
                } 
                catch (error) 
                {
                    console.error('Error fetching armies:', error);
                }

            },
            onUserChange(playerIndex, event) {
                const userId = event.target.value;
                this.players[playerIndex].user_id = userId;
                this.players[playerIndex].army_id = '';
                const user = this.users.find(u => u.id === userId);
                if (user && (!user.armies || user.armies.length === 0)) {
                    this.fetchUserArmiesByGamemode(userId, this.matchDetails.game_mode);
                }
            },
            getUserArmies(userId, game_mode) {
                
                const user = this.users.find(u => Number(u.id) === Number(userId));
                if (!user || !user.armies || !Array.isArray(user.armies)) {
                    return [];
                }
                
                return user.armies.filter(army => army.game_mode === game_mode);
            },
            addPlayer() {
                this.players.push({
                        user_id: '',
                        army_id: '',
                        primary_objective: '',
                        secondary_objective: '',
                        victory_points: 0,
                        primary_points: null,
                        secondary_points: null,
                        tertiary_points: null,
                        is_winner: false
                    });
            },
            removePlayer(index) {
                this.players.splice(index, 1);
            },
            setWinner(winnerIndex) {
                this.players.forEach((player, index) => {
                    if (index !== winnerIndex) {
                        player.is_winner = false;
                    } else {
                        player.is_winner = true;
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
                        game_mode: this.matchDetails.game_mode,
                    };
                    
                    if(this.matchDetails.game_mode === 'Killteam') {
                        for (let player of matchData.players) {
                            player.primary_objective = this.critop;
                        }
                    }
                    
                    const response = await axios.put('/api/warhammer/matchRecord', matchData);
                    
                    this.loading = false;
                    this.success = true;
                    
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
                
                const allNamesFilled = this.players.every(player => player.name !== '');
                
                if (!allNamesFilled) {
                    this.error = 'Please select a player name for all participants.';
                    return false;
                }
                
                const allArmiesFilled = this.players.every(player => {
                    if (player.army_id === 'borrow') {
                        return player.borrow_user_id && player.borrow_army_id;
                    }
                    return player.army_id !== '';
                });
                    
                if (!allArmiesFilled) {
                    this.error = 'Please select an army for all participants.';
                    return false;
                }
                return true;
            },
            resetForm() {
                this.players = [
                    {
                        user_id: '',
                        army_id: '',
                        primary_objective: '',
                        secondary_objective: '',
                        victory_points: 0,
                        primary_points: null,
                        secondary_points: null,
                        tertiary_points: null,
                        is_winner: false
                    },
                    {
                        user_id: '',
                        army_id: '',
                        primary_objective: '',
                        secondary_objective: '',
                        victory_points: 0,
                        primary_points: null,
                        secondary_points: null,
                        tertiary_points: null,
                        is_winner: false
                    }
                ];
                
                this.matchDetails = {
                    date_played: new Date().toISOString().slice(0, 16),
                    game_mode: 'Warhammer 40k',
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