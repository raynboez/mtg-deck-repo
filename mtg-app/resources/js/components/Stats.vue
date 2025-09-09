<script setup>
import axios from 'axios';
import { useRouter } from 'vue-router';

</script>

<template>
    <div class="text-foreground container mx-auto px-4 py-8">
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 ">Loading statistics...</p>
        </div>

        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <div class="text-red-600 text-2xl mb-2">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-red-800 font-semibold mb-2">Error Loading Statistics</h3>
            <p class="text-red-600">{{ error }}</p>
            <button @click="fetchStats" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                Try Again
            </button>
        </div>

        <div v-else>
            <header class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-2">MTG Match Statistics</h1>
                <p class="">Track your performance and progress over time</p>
            </header>

            <div class="card rounded-xl shadow-md p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium  mb-1">Time Period</label>
                        <select v-model="filters.period" @change="fetchStats" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="all">All Time</option>
                            <option value="month">This Month</option>
                            <option value="week">This Week</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium  mb-1">Format</label>
                        <select v-model="filters.format" @change="fetchStats" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="all">All Formats</option>
                            <option value="Gulag Commander - Season 0">Gulag Commander</option>
                            <option value="Casual Commander">Casual Commander</option>
                            <option value="Custom Game">Custom Game</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium  mb-1">Bracket</label>
                        <select v-model="filters.bracket" @change="fetchStats" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="all">All Brackets</option>
                            <option v-for="n in 5" :value="n">{{ n }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium  mb-1">Player</label>
                        <select v-model="filters.player" @change="fetchStats" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="all">All Players</option>
                            <option v-for="player in players" :value="player.id">{{ player.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card card rounded-xl shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2">{{ statistics.total_matches || 0 }}</div>
                    <div class="">Total Matches</div>
                </div>
                <div class="stat-card card rounded-xl shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">{{ statistics.average_turns_per_game || 0 }}</div>
                    <div class="">Avg. Turns/Game</div>
                </div>
                <div class="stat-card card rounded-xl shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-2">{{ mostWinsPlayer }}</div>
                    <div class="">Most Wins</div>
                </div>
                <div class="stat-card card rounded-xl shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-red-600 mb-2">{{ highestWinRate }}%</div>
                    <div class="">Best Win Rate</div>
                </div>
            </div>

            <h2 class="text-2xl font-semibold  mb-6">Player Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div v-for="player in statistics.player_stats || []" :key="player.user_id" class="card rounded-xl shadow-md p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-xl font-bold">
                            {{ getInitials(player.name) }}
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold">{{ player.name }}</h3>
                            <p class="">{{ player.wins }} wins / {{ player.losses }} losses</p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm  mb-1">
                            <span>Win Rate</span>
                            <span>{{ player.win_rate }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" :style="{ width: player.win_rate + '%' }"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="text-center p-2 bg-blue-50 rounded-md">
                            <div class="font-semibold text-blue-600">{{ player.points }}</div>
                            <div class="">Season Points</div>
                        </div>
                        <div class="text-center p-2 bg-purple-50 rounded-md">
                            <div class="font-semibold text-purple-600">{{ player.favorite_deck }}</div>
                            <div class="">Favorite Deck</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                <div class="card rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Points</h3>
                    <canvas ref="winChart" width="400" height="250"></canvas>
                </div>
                <div class="card rounded-xl shadow-md p-6">
                    <h3 class="text-xl font-semibold mb-4">Colour Identity Popularity</h3>
                    <canvas ref="colourChart" width="400" height="250"></canvas>
                </div>
            </div>




            <div v-if="selectedMatch" class="fixed inset-0 z-50">
                <div class="modal-backdrop" @click="selectedMatch = null"></div>
                <div class="modal-content">
                    <button @click="selectedMatch = null" class="absolute top-4 right-4  hover:">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <div class="pb-3 border-b border-gray-200">
                        <h3 class="text-lg font-semibold ">Match Details</h3>
                    </div>

                    <div class="mt-4 space-y-3">
                        
                        
                        <div>
                            <label class="block text-sm font-medium ">Date</label>
                            <p class="mt-1 text-sm ">{{ selectedMatch.date }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium ">Format</label>
                            <p class="mt-1 text-sm ">{{ selectedMatch.format }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium ">Bracket</label>
                            <p class="mt-1 text-sm ">{{ selectedMatch.bracket }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium ">Total Turns</label>
                            <p class="mt-1 text-sm ">{{ selectedMatch.totalTurns }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium ">Players</label>
                            <div class="mt-2 space-y-2">
                                <div 
                                    v-for="player in selectedMatch.players" 
                                    :key="player.id || player.user.id" 
                                    @click="navigateToDeck(player.deck.deck_id)"
                                    :class="[
                                        'flex items-center space-x-3 p-2 rounded transition-colors cursor-pointer',
                                        player.is_winner ? 'bg-green-100 hover:bg-green-200' : 'bg-gray-50 hover:bg-gray-100'
                                        ]"
                                >
                                    <span class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-semibold">
                                    {{ getInitials(player.user?.name || player.name) }}
                                    
                                    </span>
                                    <span class="text-sm ">
                                    {{ player.user?.name || player.name }} playing {{ player.deck.deck_name }} - 
                                    <span v-show="player.is_winner" class="text-green-600">🏆 with {{ player.final_life }}hp</span>
                                    <span v-show="!player.is_winner" class="text-red-600"> Lost on turn {{ player.turn_lost }} at {{ player.final_life }}hp</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 mt-4 border-t border-gray-200">
                        <button @click="selectedMatch = null" class="px-4 py-2 bg-gray-200  rounded hover:bg-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
            <h2 class="text-2xl font-semibold card mb-6">Recent Matches</h2>
            <div class="card rounded-xl shadow-md overflow-hidden mb-12">
                <table class="card min-w-full divide-y divide-gray-200">
                    <thead class="">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Format</th>
                            <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Players</th>
                            <th class="px-6 py-3 text-left text-xs font-medium  uppercase tracking-wider">Winner</th>
                        </tr>
                    </thead>
                    <tbody class="card divide-y divide-gray-200">
                        <tr v-for="match in statistics.recent_matches || []" :key="match.id" 
                            @click="openMatchModal(match)"
                            class="cursor-pointer hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">{{ match.date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm ">{{ match.format }}</td>
                            <td class="px-6 py-4 text-sm ">
                            <div class="flex -space-x-2">
                                <span v-for="player in match.players" :key="player.id || player.user.id" 
                                    class="h-8 w-8 rounded-full bg-blue-100 border-2 border-white text-xs flex items-center justify-center font-semibold">
                                {{ getInitials(player.user?.name || player.name) }}
                                </span>
                            </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full">{{ match.winner }}</span>
                            </td>
                        </tr>
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
    data() {
        return {
            selectedMatch:null,
            loading: true,
            error: null,
            filters: {
                period: 'all',
                format: 'all',
                bracket: 'all',
                player: 'all'
            },
            statistics: {},
            players: [],
            winChart: null,
            formatChart: null
        };
    },    
    computed: {
        mostWinsPlayer() {
            if (!this.statistics.player_stats || this.statistics.player_stats.length === 0) {
                return 'N/A';
            }
            return this.statistics.player_stats.reduce((prev, current) => 
                (prev.wins > current.wins) ? prev : current).name;
        },
        highestWinRate() {
            if (!this.statistics.player_stats || this.statistics.player_stats.length === 0) {
                return '0';
            }
            const player = this.statistics.player_stats.reduce((prev, current) => 
                (prev.win_rate > current.win_rate) ? prev : current);
            return player.win_rate;
        }
    },
    mounted() {
        this.fetchStats();
        document.addEventListener('keydown', this.handleKeydown);
    },
    methods: {
        async fetchStats() {
            this.loading = true;
            this.error = null;
            
            try {
                const params = new URLSearchParams();
                if (this.filters.period !== 'all') params.append('period', this.filters.period);
                if (this.filters.format !== 'all') params.append('format', this.filters.format);
                if (this.filters.bracket !== 'all') params.append('bracket', this.filters.bracket);
                if (this.filters.player !== 'all') params.append('player', this.filters.player);
                
                const url = `/api/stats${params.toString() ? `?${params.toString()}` : ''}`;
                const response = await axios.get(url);
                this.statistics = response.data.statistics;
                this.loading = false;
                this.$nextTick(() => {
                    this.initCharts();
                });
                
            } catch (error) {
                this.loading = false;
                this.error = error.response?.data?.message || 'Failed to load statistics';
                console.error('Error fetching stats:', error);
            }
        },
        initCharts() {
            if (this.winChart) this.winChart.destroy();
            if (this.colourChart) this.colourChart.destroy();
            const datasetsArray = Object.values(this.statistics.datasets);
            if (this.statistics.player_stats && this.statistics.player_stats.length > 0) {
                const winCtx = this.$refs.winChart.getContext('2d');
                this.winChart = new Chart(winCtx, {
                    type: 'line',
                    data: {
                        labels: this.statistics.labels,
                        datasets: 
                            datasetsArray
                        
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                            }
                        },
                        scales: {
                            x: {
                                display: false,
                            },
                            y: {
                            }
                        }
                    }
                });
            }
            if (this.statistics.colour_distribution) {
                const colourCtx = this.$refs.colourChart.getContext('2d');
                
                const factionColorMap = {
                    'White': 'W',
                    'Blue': 'U', 
                    'Black': 'B',
                    'Red': 'R',
                    'Green': 'G',
                    'Azorius': 'WU',
                    'Dimir': 'UB',
                    'Rakdos': 'BR',
                    'Gruul': 'RG',
                    'Selesnya': 'GW',
                    'Orzhov': 'WB',
                    'Izzet': 'UR',
                    'Golgari': 'BG',
                    'Boros': 'RW',
                    'Simic': 'GU',
                    'Bant': 'GWU',
                    'Esper': 'WUB',
                    'Grixis': 'UBR',
                    'Jund': 'BRG',
                    'Naya': 'RGW',
                    'Abzan': 'WBG',
                    'Jeskai': 'URW',
                    'Sultai': 'BGU',
                    'Mardu': 'RWB',
                    'Temur': 'GUR',
                    'Glint': 'WUBR',
                    'Dune': 'UBRG',
                    'Ink': 'BRGW',
                    'Witch': 'RGWU',
                    'Yore': 'GWUB',
                    'Five-Color': 'WUBRG',
                    'Colorless': 'C'
                };

                const colorMap = {
                    'W': '#F8F5E6',
                    'U': '#0E68AB',
                    'B': '#150B00',
                    'R': '#D3202A',
                    'G': '#00733D',
                    'C': '#AAAAAA'
                };

                const gradientPlugin = {
                    id: 'pieGradientPlugin',
                    beforeDraw: function(chart) {
                        const ctx = chart.ctx;
                        const chartArea = chart.chartArea;
                        const meta = chart.getDatasetMeta(0);
                        
                        if (!chartArea) {
                            return;
                        }
                        
                meta.data.forEach((slice, index) => {
                    const faction = chart.data.labels[index];
                    const colorIdentity = factionColorMap[faction] || 'C';
                    const colorArray = colorIdentity.split('').map(colorCode => colorMap[colorCode]);
                    
                    if (colorArray.length === 1) {
                        return;
                    }
                    
                    const x = slice.x;
                    const y = slice.y;
                    const outerRadius = slice.outerRadius;
                    const startAngle = slice.startAngle;
                    const endAngle = slice.endAngle;
                    
                    const gradient = ctx.createLinearGradient(
                        x + outerRadius * Math.cos(startAngle),
                        y + outerRadius * Math.sin(startAngle),
                        x + outerRadius * Math.cos(endAngle),
                        y + outerRadius * Math.sin(endAngle)
                    );
                    
                    if (colorArray.length === 2) {
                        gradient.addColorStop(0, colorArray[0]);
                        gradient.addColorStop(1, colorArray[1]);
                    } else if (colorArray.length === 3) {
                        gradient.addColorStop(0, colorArray[0]);
                        gradient.addColorStop(0.5, colorArray[1]);
                        gradient.addColorStop(1, colorArray[2]);
                    } else if (colorArray.length === 4) {
                        gradient.addColorStop(0, colorArray[0]);
                        gradient.addColorStop(0.33, colorArray[1]);
                        gradient.addColorStop(0.66, colorArray[2]);
                        gradient.addColorStop(1, colorArray[3]);
                    } else {
                        const stops = colorArray.length;
                        for (let i = 0; i < stops; i++) {
                            gradient.addColorStop(i / (stops - 1), colorArray[i]);
                        }
                    }
                    
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(x, y);
                    ctx.arc(x, y, outerRadius, startAngle, endAngle);
                    ctx.closePath();
                    ctx.fillStyle = gradient;
                    ctx.fill();
                    ctx.restore();
                    
                    if (slice.options) {
                        slice.options.backgroundColor = 'transparent';
                    }
                });
            }
        };

        const labels = Object.keys(this.statistics.colour_distribution);
        const data = Object.values(this.statistics.colour_distribution);
        
        
        const tempColors = labels.map(faction => {
            const colorIdentity = factionColorMap[faction] || 'C';
            const colorArray = colorIdentity.split('').map(colorCode => colorMap[colorCode]);
            return colorArray[0];
        });
        
        this.colourChart = new Chart(colourCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: tempColors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            },
            plugins: [gradientPlugin]
        });
    }
        },
        getInitials(name) {
            return name ? name.charAt(0) : '?';
        },
        openMatchModal(match) {
            this.selectedMatch = match;
        },
        handleKeydown(event) {
            if (event.key === 'Escape' && this.selectedMatch) {
                this.selectedMatch = null;
            }
        },
        navigateToDeck(deckId) {
            this.selectedMatch = null;
            window.location.href = `/deck/${deckId}`;
            
        }
    },
    beforeDestroy() {
        if (this.winChart) this.winChart.destroy();
        if (this.formatChart) this.formatChart.destroy();
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.handleKeydown);
    },
};
</script>

<style scoped>
/* Optional: Add some animations for the modal */
.fixed {
  transition: opacity 0.3s ease;
}

.relative {
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  z-index: 40;
}

.modal-content {
  position: relative;
  background-color: rgba(255, 255, 255);
  margin: 2rem auto;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  max-width: 28rem;
  width: 90%;
  z-index: 50;
}

.modal-content.transparent {
  background-color: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.card {
    background-color: var(--color-background);
}
</style>