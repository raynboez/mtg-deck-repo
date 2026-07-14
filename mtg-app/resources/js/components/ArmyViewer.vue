<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import axios from 'axios';
// Props
interface Army {
    army_id: number
    user_id: number
    name: string
    description: string
    game_mode: string
    points: number
    faction: string
    subfaction: string
    army_link: string
    list: string
    created_at: string
    updated_at: string
}

interface Photo {
    id: number
    army_id: number
    user_id: number
    photo_url: string
    is_primary: boolean
    created_at: string
}

interface Props {
    army: Army
    armystats: string
}

const props = defineProps<Props>()

// State
const photos = ref<Photo[]>([])
const isLoading = ref(false)
const isUploading = ref(false)
const error = ref<string | null>(null)
const selectedFile = ref<File | null>(null)
const uploadProgress = ref(0)
const fileInput = ref<HTMLInputElement | null>(null)

const parseStats = (stats: string) => {
    const lines = stats.split('\n').filter(line => line.trim())
    return {
        armyStats: lines[0] || '',
        personalStats: lines[1] || ''
    }
}

const stats = computed(() => parseStats(props.armystats))

const totalGames = computed(() => {
    const match = stats.value.armyStats.match(/(\d+)/)
    return match ? match[1] : '0'
})

const wins = computed(() => {
    const match = stats.value.armyStats.match(/(\d+)-(\d+)/)
    return match ? match[1] : '0'
})

const losses = computed(() => {
    const match = stats.value.armyStats.match(/(\d+)-(\d+)/)
    return match ? match[2] : '0'
})

const winRate = computed(() => {
    const match = stats.value.armyStats.match(/\((\d+)%\)/)
    return match ? match[1] : '0'
})

const personalWins = computed(() => {
    const match = stats.value.personalStats.match(/(\d+)-(\d+)/)
    return match ? match[1] : '0'
})

const personalLosses = computed(() => {
    const match = stats.value.personalStats.match(/(\d+)-(\d+)/)
    return match ? match[2] : '0'
})

const personalWinRate = computed(() => {
    const match = stats.value.personalStats.match(/\((\d+)%\)/)
    return match ? match[1] : '0'
})
const currentUserId = ref<number | null>(null);
const fetchCurrentUser = async () => {
  try {
    const response = await axios.get('/api/user/current');
    currentUserId.value = response.data;
  } catch (error) {
    console.error('Failed to fetch current user:', error);
  }
};

const canManagePhotos = computed(() => {
    if (!currentUserId.value) return false
    return currentUserId.value === props.army.user_id
})

const fetchPhotos = async () => {
    isLoading.value = true
    error.value = null
    
    try {
        const response = await axios.get(`/api/warhammer/armies/${props.army.army_id}/photos`)
        if (!response) {
            throw new Error('Failed to fetch photos')
        }
        
        photos.value = response.data.photos || []
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Error loading photos'
        console.error('Error fetching photos:', err)
    } finally {
        isLoading.value = false
    }
}

const uploadPhoto = async () => {
    if (!selectedFile.value) return
    
    isUploading.value = true
    uploadProgress.value = 0
    error.value = null
    
    const formData = new FormData()
    formData.append('photo', selectedFile.value)
    formData.append('army_id', String(props.army.army_id))
    
    try {
        const response = await axios.post('/api/warhammer/armies/photos', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    const percentCompleted = Math.round(
                        (progressEvent.loaded * 100) / progressEvent.total
                    )
                    uploadProgress.value = percentCompleted
                }
            }
        })
        
        // Add new photo to list
        if (response.data.photo) {
            photos.value.unshift(response.data.photo)
        }
        
        selectedFile.value = null
        uploadProgress.value = 0
        if (fileInput.value) {
            fileInput.value.value = ''
        }
        
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Error uploading photo'
        console.error('Error uploading photo:', err)
    } finally {
        isUploading.value = false
    }
}
const deletePhoto = async (photoId: number) => {
    if (!confirm('Are you sure you want to delete this photo?')) return
    
    try {
        await axios.delete(`/api/warhammer/armies/photos/${photoId}`)
        photos.value = photos.value.filter(p => p.id !== photoId)
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Error deleting photo'
        console.error('Error deleting photo:', err)
    }
}

const setPrimaryPhoto = async (photoId: number) => {
    try {
        await axios.patch(`/api/warhammer/armies/photos/${photoId}/set-primary`)
        photos.value = photos.value.map(p => ({
            ...p,
            is_primary: p.id === photoId
        }))
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Error setting primary photo'
        console.error('Error setting primary photo:', err)
    }
}

const handleFileSelect = (event: Event) => {
    const input = event.target as HTMLInputElement
    if (input.files && input.files.length > 0) {
        const file = input.files[0]
        
        if (!file.type.startsWith('image/')) {
            error.value = 'Please select an image file'
            input.value = ''
            return
        }
        
        if (file.size > 10 * 1024 * 1024) {
            error.value = 'Image size must be less than 10MB'
            input.value = ''
            return
        }
        
        selectedFile.value = file
    }
}
const primaryPhoto = computed(() => {
    return photos.value.find(p => p.is_primary) || photos.value[0] || null
})

onMounted(() => {
    fetchCurrentUser();
    fetchPhotos()
})
</script>

<template>
    
        

        <!-- Army Header -->
        <div class="army-header">
            <div class="army-title">
                <h1>{{ props.army.name }}</h1>
                <span class="army-badge">{{ props.army.game_mode }}</span>
            </div>
            <div class="army-meta">
                <span class="meta-item">
                    <span class="meta-label">Faction:</span>
                    {{ props.army.faction }}
                </span>
                <span class="meta-item">
                    <span class="meta-label">Subfaction:</span>
                    {{ props.army.subfaction }}
                </span>
                <span class="meta-item">
                    <span class="meta-label">Points:</span>
                    {{ props.army.points || 'N/A' }}
                </span>
            </div>
        </div>
<!-- Photo Gallery Section -->
        <div class="photo-section">
            <div class="photo-header">
                <h2>Army Photos</h2>
                <span class="photo-count">{{ photos.length }} photos</span>
            </div>
            
            
            
            <!-- Error Display -->
            <div v-if="error" class="error-message">
                {{ error }}
                <button @click="error = null" class="error-close">×</button>
            </div>
            
            <!-- Photo Grid -->
            <div v-if="isLoading" class="loading-state">
                <div class="spinner"></div>
                <p>Loading photos...</p>
            </div>
            
            <div v-else-if="photos.length === 0" class="empty-state">
              <div v-if="canManagePhotos" class="upload-area">
                  <div class="upload-container">
                      <input 
                          type="file" 
                          accept="image/*" 
                          @change="handleFileSelect"
                          ref="fileInput"
                          class="file-input"
                          id="photo-upload"
                      >
                      <label for="photo-upload" class="upload-label">
                          <span class="upload-icon">📸</span>
                          <span>Choose a photo</span>
                          <span class="upload-hint">JPG, PNG, GIF up to 5MB</span>
                      </label>
                      
                      <button 
                          v-if="selectedFile"
                          @click="uploadPhoto"
                          :disabled="isUploading"
                          class="upload-btn"
                      >
                          {{ isUploading ? 'Uploading...' : 'Upload Photo' }}
                      </button>
                  </div>
                  
                  <!-- Upload Progress -->
                  <div v-if="isUploading" class="progress-bar">
                      <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
                      <span class="progress-text">{{ uploadProgress }}%</span>
                  </div>
              </div>
                <span class="empty-icon">🖼️</span>
                <p>No photos uploaded yet</p>
                <p v-if="canManagePhotos" class="empty-hint">
                    Upload your army photos
                </p>
                
            </div>
            
            <div v-else class="photo-grid">
                <div 
                    v-for="photo in photos" 
                    :key="photo.id"
                    class="photo-item"
                    :class="{ 'primary': photo.is_primary }"
                >
                    <div class="photo-wrapper">
                        <img :src="photo.photo_url" :alt="`Army photo ${photo.id}`" class="photo-image">
                        
                        <!-- Photo Badges -->
                        <div class="photo-badges">
                            <span v-if="photo.is_primary" class="badge-primary">⭐</span>
                        </div>
                        
                        <!-- Photo Actions -->
                        <div v-if="canManagePhotos" class="photo-actions">
                            <button 
                                v-if="!photo.is_primary"
                                @click="setPrimaryPhoto(photo.id)"
                                class="action-btn set-primary"
                                title="Set as primary"
                            >
                                ⭐
                            </button>
                            <button 
                                @click="deletePhoto(photo.id)"
                                class="action-btn delete"
                                title="Delete photo"
                            >
                                🗑️
                            </button>
                        </div>
                    </div>
                    <div class="photo-meta">
                        <span class="photo-date">
                            {{ new Date(photo.created_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
                <!-- Upload Area -->
              <div v-if="canManagePhotos" class="upload-area">
                  <div class="upload-container">
                      <input 
                          type="file" 
                          accept="image/*" 
                          @change="handleFileSelect"
                          ref="fileInput"
                          class="file-input"
                          id="photo-upload"
                      >
                      <label for="photo-upload" class="upload-label">
                          <span class="upload-icon">📸</span>
                          <span>Choose a photo</span>
                          <span class="upload-hint">JPG, PNG, GIF up to 5MB</span>
                      </label>
                      
                      <button 
                          v-if="selectedFile"
                          @click="uploadPhoto"
                          :disabled="isUploading"
                          class="upload-btn"
                      >
                          {{ isUploading ? 'Uploading...' : 'Upload Photo' }}
                      </button>
                  </div>
                  
                  <!-- Upload Progress -->
                  <div v-if="isUploading" class="progress-bar">
                      <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
                      <span class="progress-text">{{ uploadProgress }}%</span>
                  </div>
              </div>
            </div>
            
        </div>
        <!-- Stats Section -->
        <div class="stats-section">
            <h2>Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value">{{ totalGames }}</div>
                    <div class="stat-label">Total Games</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ wins }}</div>
                    <div class="stat-label">Wins</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ losses }}</div>
                    <div class="stat-label">Losses</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">{{ winRate }}%</div>
                    <div class="stat-label">Win Rate</div>
                </div>
            </div>

            
            
            <!-- Personal Stats -->
            <div class="personal-stats">
                <h3>Personal Record</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">{{ personalWins }}</div>
                        <div class="stat-label">Personal Wins</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ personalLosses }}</div>
                        <div class="stat-label">Personal Losses</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ personalWinRate }}%</div>
                        <div class="stat-label">Personal Win Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Section -->
        <div class="details-section">
            <div class="detail-item">
                <span class="detail-label">Description</span>
                <p class="detail-value">{{ props.army.description || 'No description' }}</p>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">List</span>
                <p class="detail-value">{{ props.army.list || 'No list available' }}</p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Army Link</span>
                <a v-if="props.army.army_link" 
                   :href="props.army.army_link" 
                   target="_blank" 
                   class="detail-link">
                    {{ props.army.army_link }}
                </a>
                <span v-else class="detail-value">No link</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Created</span>
                <span class="detail-value">{{ new Date(props.army.created_at).toLocaleDateString() }}</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Last Updated</span>
                <span class="detail-value">{{ new Date(props.army.updated_at).toLocaleDateString() }}</span>
            </div>
        </div>
</template>

<style scoped>
.army-details-container {
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Photo Section Styles */
.photo-section {
    background: var(--color-background);
    padding: 24px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.photo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.photo-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.photo-count {
    font-size: 14px;
    color: #6b7280;
}

.upload-area {
    margin-bottom: 20px;
}

.upload-container {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.file-input {
    display: none;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    flex: 1;
    min-height: 120px;
    background: #f9fafb;
}

.upload-label:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.upload-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.upload-hint {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.upload-btn {
    padding: 10px 24px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.upload-btn:hover:not(:disabled) {
    background: #2563eb;
}

.upload-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.progress-bar {
    margin-top: 12px;
    background: #e5e7eb;
    border-radius: 4px;
    height: 24px;
    position: relative;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #2563eb);
    transition: width 0.3s;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: 500;
    color: #1f2937;
}

.error-message {
    background: #fee2e2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.error-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #dc2626;
    padding: 0 8px;
}

.loading-state {
    text-align: center;
    padding: 40px 0;
    color: #6b7280;
}

.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 40px 0;
    color: #6b7280;
}

.empty-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 16px;
}

.empty-hint {
    font-size: 14px;
    margin-top: 8px;
}

.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.photo-item {
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: border-color 0.2s;
}

.photo-item.primary {
    border-color: #f59e0b;
}

.photo-wrapper {
    position: relative;
    padding-top: 100%;
    background: #f3f4f6;
    overflow: hidden;
}

.photo-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-badges {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    gap: 4px;
}

.badge-primary {
    background: #2563eb;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.photo-actions {
    position: absolute;
    bottom: 8px;
    right: 8px;
    display: flex;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.2s;
}

.photo-wrapper:hover .photo-actions {
    opacity: 1;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: rgba(0, 0, 0, 0.8);
}

.action-btn.set-primary:hover {
    background: #f59e0b;
}

.action-btn.delete:hover {
    background: #dc2626;
}

.photo-meta {
    padding: 8px 12px;
    background: #f9fafb;
}

.photo-date {
    font-size: 12px;
    color: #6b7280;
}

/* Existing styles remain the same */
.army-header {
    background: var(--color-background);
    padding: 24px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.army-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}

.army-title h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.army-badge {
    background-color: #3b82f6;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.army-meta {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.meta-item {
    font-size: 14px;
    color: #6b7280;
}

.meta-label {
    font-weight: 500;
    color: #374151;
    margin-right: 4px;
}

.stats-section {
    background: var(--color-background);
    padding: 24px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.stats-section h2 {
    margin-top: 0;
    margin-bottom: 16px;
    font-size: 20px;
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-item {
    text-align: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.personal-stats {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.personal-stats h3 {
    font-size: 16px;
    font-weight: 500;
    margin-top: 0;
    margin-bottom: 12px;
    color: #374151;
}

.details-section {
    background: var(--color-background);
    padding: 24px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item:last-child {
    grid-column: span 2;
}

.detail-label {
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 14px;
    color: #1f2937;
    margin: 0;
    word-break: break-word;
}

.detail-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
}

.detail-link:hover {
    text-decoration: underline;
}

.raw-stats {
    background: var(--color-background);
    padding: 24px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.raw-stats h3 {
    margin-top: 0;
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 500;
}

.stats-display {
    background: #f9fafb;
    padding: 12px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    margin: 0;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

@media (max-width: 640px) {
    .army-title {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .army-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .details-section {
        grid-template-columns: 1fr;
    }
    
    .detail-item:last-child {
        grid-column: span 1;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .upload-container {
        flex-direction: column;
    }
    
    .upload-btn {
        width: 100%;
    }
}
</style>