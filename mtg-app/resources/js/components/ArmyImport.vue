<template>
    <div class="army-import">
        <h2 class="header">Import Warhammer Army or Kill Team</h2>

        <form @submit.prevent="submitImport">
            <div class="import-options">
                <div class="option-tabs">
                    <button type="button" :class="{ active: activeTab === 'w40k' }" @click="activeTab = 'w40k'">
                        Warhammer 40k
                    </button>
                    <button type="button" :class="{ active: activeTab === 'kt' }" @click="activeTab = 'kt'">
                        Kill Team
                    </button>
                </div>

                <div v-if="activeTab === 'w40k'" class="form-group">
                    <label for="army_name">Army Name</label>
                    <input
                      id="army_name"
                      v-model="form.army_name"
                      placeholder="Enter army name"
                      :required="activeTab === 'w40k'"
                    ></input>
                    <label for="army_description">Army Description</label>
                    <input
                      id="army_description"
                      v-model="form.army_description"
                      placeholder="Enter army description"
                      :required="activeTab === 'w40k'"
                    ></input>



                    <label for="army_faction">Faction</label>
                    <select
                      id="army_faction"
                        v-model="selectedFaction"
                        :required="activeTab === 'w40k'"
                        class="w-full p-2 border border-input rounded-md bg-background"
                    >
                        <option value="" disabled>Select Faction</option>
                        <option v-for="factionKey in factionKeys" :key="factionKey" :value="factionKey">
                            {{ factions[factionKey].displayName }}
                        </option>
                    </select>




                    <label for="army_subfaction">Subfaction</label>
                    <select
                        id="army_subfaction"
                        v-model="selectedSubfaction"
                        :required="activeTab === 'w40k'"
                        :disabled="!selectedFaction"
                        :class="{'opacity-50 cursor-not-allowed': !selectedFaction}"
                        class="w-full p-2 border border-input rounded-md bg-background"
                    >
                        <option value="" disabled>
                            Select Subfaction
                        </option>
                        <option v-for="subfaction in availableSubfactions" :key="subfaction.value" :value="subfaction.value">
                            {{ subfaction.label }}
                        </option>
                  </select>
                  <label :for="form.points" >Points</label>
                  <input 
                      :id="form.points" 
                      type="number" 
                      v-model.number="form.points"
                      min="0"
                      class="w-full p-2 border border-input rounded-md bg-background"
                      required
                    ></input>
                    <label :for="form.army_list" >Army List Link</label>
                  <input
                      id="army_list"
                      v-model="form.army_link"
                      placeholder="Enter link to Army List (optional)"
                    ></input>


                    <label :for="form.army_list">Army List</label>
                
                    <textarea
                      id="army_list"
                      v-model="form.army_list"
                      placeholder="Enter Army List as text (optional)"
                    ></textarea>  
                </div>






                <div v-if="activeTab === 'kt'" class="form-group">
                    <label for="army_name">Team Name</label>
                    <input
                      id="army_name"
                      v-model="form.army_name"
                      placeholder="Enter Team name"
                      :required="activeTab === 'kt'"
                    ></input>
                    <label for="army_description">Team Description</label>
                    <input
                      id="army_description"
                      v-model="form.army_description"
                      placeholder="Enter Team description"
                      :required="activeTab === 'kt'"
                    ></input>



                    <label for="army_faction">Faction</label>
                    <select
                      id="army_faction"
                        v-model="selectedFaction"
                        :required="activeTab === 'kt'"
                        class="w-full p-2 border border-input rounded-md bg-background"
                    >
                        <option value="" disabled>Select Faction</option>
                        <option v-for="factionKey in factionKeys" :key="factionKey" :value="factionKey">
                            {{ factions[factionKey].displayName }}
                        </option>
                    </select>




                    <label for="army_subfaction">Killteam</label>
                    <select
                        id="army_subfaction"
                        v-model="selectedSubfaction"
                        :required="activeTab === 'kt'"
                        :disabled="!selectedFaction"
                        :class="{'opacity-50 cursor-not-allowed': !selectedFaction}"
                        class="w-full p-2 border border-input rounded-md bg-background"
                    >
                        <option value="" disabled>
                            Select Killteam
                        </option>
                        <option v-for="subfaction in availableTeams" :key="subfaction.value" :value="subfaction.value">
                            {{ subfaction.label }}
                        </option>
                  </select>
                    <label :for="form.army_link" >Team List Link</label>
                  <input
                      id="army_link"
                      v-model="form.army_link"
                      placeholder="Enter link to Team List (optional)"
                    ></input>
                    <label :for="form.army_list">Team List</label>
                
                    <textarea
                      id="army_list"
                      v-model="form.army_list"
                      placeholder="Enter Team List as text (optional)"
                    ></textarea>  
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
                army_name: '',
                army_description: '',
                faction: '',
                subfaction: '',
                points: 2000,
                army_link: '',
                army_list: '',
                game_mode: '',
            },
            activeTab: 'w40k',
            loading: false,
            error: null,
            success: false,
            selectedFaction: '',
            selectedSubfaction: '',
            factions: {
                Astartes: {
                    displayName: 'Adeptus Astartes',
                    subfactions: [
                            { value: 'Black Templars', label: 'Black Templars' },
                            { value: 'Blood Angels', label: 'Blood Angels' },
                            { value: 'Dark Angels', label: 'Dark Angels' },
                            { value: 'Deathwatch', label: 'Deathwatch' },
                            { value: 'Imperial Fists', label: 'Imperial Fists' },
                            { value: 'Iron Hands', label: 'Iron Hands' },
                            { value: 'Raven Guard', label: 'Raven Guard' },
                            { value: 'Salamanders', label: 'Salamanders' },
                            { value: 'Space Marines', label: 'Space Marines' },
                            { value: 'Space Wolves', label: 'Space Wolves' },
                            { value: 'Ultramarines', label: 'Ultramarines' },
                            { value: 'White Scars', label: 'White Scars' }
                    ],
                  teams: [
                     { value: 'Angels Of Death', label: 'Angels of Death' },
                     { value: 'Deathwatch', label: 'Deathwatch' },
                     { value: 'Phobos Strike Team', label: 'Phobos Strike Team' },
                     { value: 'Scout Squad', label: 'Scout Squad' },
                     { value: 'Wolf Scouts', label: 'Wolf Scouts' },
                  ]
                },
                Chaos: {
                    displayName: 'Chaos',
                    subfactions: [
                        { value: 'Chaos Daemons', label: 'Chaos Daemons' },
                        { value: 'Chaos Knights', label: 'Chaos Knights' },
                        { value: 'Chaos Space Marines', label: 'Chaos Space Marines' },
                        { value: 'Death Guard', label: 'Death Guard' },
                        { value: 'Emperors Children', label: 'Emperors Children' },
                        { value: 'Thousand Sons', label: 'Thousand Sons' },
                        { value: 'Titanticus Traitoris', label: 'Titanicus Traitoris' },
                        { value: 'World Eaters', label: 'World Eaters' }
                    ],
                  teams: [
                     { value: 'Gellerpox Infected', label: 'Gellerpox Infected' },
                     { value: 'Legionaries', label: 'Legionaries' },
                     { value: 'Murderwing', label: 'Murderwing' },
                     { value: 'Nemesis Claw', label: 'Nemesis Claw' },
                     { value: 'Blooded', label: 'Blooded' },
                     { value: 'Chaos Cult', label: 'Chaos Cult' },
                     { value: 'Fellgor Ravagers', label: 'Fellgor Ravagers' },
                     { value: 'Plague Marines', label: 'Plague Marines' },
                     { value: 'Warpcoven', label: 'Warpcoven' },
                     { value: 'Goremongers', label: 'Goremongers' },
                  ]
                },
                Imperium: {
                    displayName: 'Imperium of Man',
                    subfactions: [
                            { value: 'Adepta Sororitas', label: 'Adepta Sororitas' },
                            { value: 'Adeptus Custodes', label: 'Adeptus Custodes' },
                            { value: 'Adeptus Mechanicus', label: 'Adeptus Mechanicus' },
                            { value: 'Adeptus Titanicus', label: 'Adeptus Titanicus' },
                            { value: 'Agents Of The Imperium', label: 'Agents of the Imperium' },
                            { value: 'Astra Militarum', label: 'Astra Militarum' },
                            { value: 'Grey Knights', label: 'Grey Knights' },
                            { value: 'Imperial Knights', label: 'Imperial Knights' }
                    ],
                  teams: [
                     { value: 'Celestian Insidiants', label: 'Celestian Insidiants' },
                     { value: 'Novitiates', label: 'Novitiates' },
                     { value: 'Battleclade', label: 'Battleclade' },
                     { value: 'Hunter Clade', label: 'Hunter Clade' },
                     { value: 'Elucidian Starstriders', label: 'Elucidian Starstriders' },
                     { value: 'Exaction Squad', label: 'Exaction Squad' },
                     { value: 'Imperial Navy Breachers', label: 'Imperial Navy Breachers' },
                     { value: 'Inquisitorial Agents', label: 'Inquisitorial Agents' },
                     { value: 'Sanctifiers', label: 'Sanctifiers' },
                     { value: 'DeathKorps', label: 'Death Korps' },
                     { value: 'Kasrkin', label: 'Kasrkin' },
                     { value: 'Ratlings', label: 'Ratlings' },
                     { value: 'Spectre Squad', label: 'Spectre Squad' },
                     { value: 'Tempestus Aquilons', label: 'Tempestus Aquilons' },
                  ]
                },
                Xenos: {
                    displayName: 'Xenos',
                    subfactions: [
                        { value: 'Aeldari', label: 'Aeldari' },
                        { value: 'Drukhari', label: 'Drukhari' },
                        { value: 'Genestealer Cults', label: 'Genestealer Cults' },
                        { value: 'Leagues Of Votann', label: 'Leagues of Votann' },
                        { value: 'Necrons', label: 'Necrons' },
                        { value: 'Orks', label: 'Orks' },
                        { value: 'Tau Empire', label: 'Tau Empire' },
                        { value: 'Tyranids', label: 'Tyranids' }
                    ],
                  teams: [
                     { value: 'Brood Brothers', label: 'Brood Brothers' },
                     { value: 'Wyrmblade', label: 'Wyrmblade' },
                     { value: 'Hearthkyn Salvagers', label: 'Hearthkyn Salvagers' },
                     { value: 'Hernkyn Yaegir', label: 'Hernkyn Yaegir' },
                     { value: 'Canoptek Circle', label: 'Canoptek Circle' },
                     { value: 'Hierotek Circle', label: 'Hierotek Circle' },
                     { value: 'Kommandos', label: 'Kommandos' },
                     { value: 'Wrecka Krew', label: 'Wrecka Krew' },
                     { value: 'Farstalker Kinband', label: 'Farstalker Kinband' },
                     { value: 'Pathfinders', label: 'Pathfinders' },
                     { value: 'Vespid Stingwings', label: 'Vespid Stingwings' },
                     { value: 'XV26 Stealth Battlesuits', label: 'XV26 Stealth Battlesuits' },
                     { value: 'Raveners', label: 'Raveners' },
                  ]
                }
            }
        }
    },
    computed: {
        availableSubfactions() {
            if (!this.selectedFaction || !this.factions[this.selectedFaction]) {
                return [];
            }
            return this.factions[this.selectedFaction].subfactions;
        },
        availableTeams() {
            if (!this.selectedFaction || !this.factions[this.selectedFaction]) {
                return [];
            }
            return this.factions[this.selectedFaction].teams;
        },
        factionKeys() {
            return Object.keys(this.factions);
        }
    },
    watch: {
        selectedFaction() {
            this.selectedSubfaction = '';
        }
    },
    methods: {         
      resetForm() {
          this.form = {
              army_name: '',
                army_description: '',
                faction: '',
                subfaction: '',
                points: 2000,
                army_link: '',
                army_list: '',
          };
          this.activeTab = 'w40k';
          this.loading = false;
          this.error = null;
          this.success = false;
          this.selectedFaction = '';
          this.selectedSubfaction = '';
      },

      async submitImport() {
        this.loading = true;
        this.error = null;
        this.success = false;

        try {
            this.form.faction = this.selectedFaction;
            this.form.subfaction = this.selectedSubfaction;
            if(this.activeTab==='kt'){
              this.form.game_mode = 'Killteam';
              this.form.points = 0;
            } else {
              this.form.game_mode = 'Warhammer 40k';
            }
            
            
            console.log(this.form);

            const response = await axios.post('/api/warhammer/armies/import', this.form, {
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


input, select{
  width: 100%;
  padding: 10px;
  border: 1px solid var(--input);
  border-radius: var(--radius);
  margin-bottom: 8px;
  background: var(--muted);
  transition: border 0.3s;
  color: var(--foreground);
}
input:focus, select:focus{
  width: 100%;
  padding: 10px;
  border: 1px solid var(--input);
  border-radius: var(--radius);
  margin-bottom: 8px;
  background: var(--muted);
  transition: border 0.3s;
  color: var(--foreground);
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