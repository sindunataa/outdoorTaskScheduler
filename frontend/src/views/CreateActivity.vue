<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <div class="flex items-center space-x-4 mb-4">
          <router-link
            to="/activities"
            class="text-gray-600 hover:text-gray-900 flex items-center"
          >
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Activities
          </router-link>
        </div>
        <h1 class="text-3xl font-bold text-gray-900">Schedule New Activity</h1>
        <p class="text-gray-600">Plan your outdoor activity and get weather-based time suggestions.</p>
      </div>

      <div class="bg-white rounded-lg shadow">
        <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                Activity Name
              </label>
              <input
                v-model="form.name"
                type="text"
                id="name"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="e.g., Field Survey, Equipment Maintenance"
              />
              <div v-if="errors.name" class="text-red-500 text-sm mt-1">
                <p v-for="error in errors.name" :key="error">{{ error }}</p>
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Location Details
              </label>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Province</label>
                  <select
                    v-model="selectedProvince"
                    @change="onProvinceChange"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                  >
                    <option value="">Select Province</option>
                    <option v-for="province in provinces" :key="province.code" :value="province">
                      {{ province.name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs text-gray-500 mb-1">City/Regency</label>
                  <select
                    v-model="selectedRegency"
                    @change="onRegencyChange"
                    :disabled="!selectedProvince || regenciesLoading"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm disabled:bg-gray-100"
                  >
                    <option value="">Select City/Regency</option>
                    <option v-for="regency in regencies" :key="regency.code" :value="regency">
                      {{ regency.name }}
                    </option>
                  </select>
                  <div v-if="regenciesLoading" class="text-xs text-gray-500 mt-1">Loading...</div>
                </div>

                <div>
                  <label class="block text-xs text-gray-500 mb-1">District/Sub-district</label>
                  <select
                    v-model="selectedDistrict"
                    @change="onDistrictChange"
                    :disabled="!selectedRegency || districtsLoading"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm disabled:bg-gray-100"
                    placeholder="Select District/Sub-district"
                  >
                    <option value="">Select District</option>
                    <option v-for="district in districts" :key="district.code" :value="district">
                      {{ district.name }}
                    </option>
                  </select>
                  <div v-if="districtsLoading" class="text-xs text-gray-500 mt-1">Loading...</div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="relative">
                  <label for="subdistrict" class="block text-xs text-gray-500 mb-1">
                    Get Current Location
                  </label>
                  <div class="flex space-x-2">

                    <button
                      type="button"
                      @click="getCurrentLocation"
                      :disabled="gettingLocation"
                      class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center whitespace-nowrap text-sm"
                      title="Use current location"
                    >
                      <svg v-if="gettingLocation" class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <svg v-else class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                      {{ gettingLocation ? 'Getting...' : 'GPS' }}
                    </button>
                  </div>
                  <div v-if="errors.subdistrict" class="text-red-500 text-sm mt-1">
                    <p v-for="error in errors.subdistrict" :key="error">{{ error }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="md:col-span-2">
              <label for="preferred_date" class="block text-sm font-medium text-gray-700 mb-2">
                Preferred Date
              </label>
              <input
                v-model="form.preferred_date"
                type="date"
                id="preferred_date"
                required
                :min="minDate"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <div v-if="errors.preferred_date" class="text-red-500 text-sm mt-1">
                <p v-for="error in errors.preferred_date" :key="error">{{ error }}</p>
              </div>
            </div>
          </div>

          <div v-if="form.location || currentLocationInfo" class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-medium text-blue-900 mb-2">Selected Location</h4>
            <div class="text-sm text-blue-800">
              <p><strong>Province:</strong> {{ selectedProvince?.name || currentLocationInfo?.province || '-' }}</p>
              <p><strong>City/Regency:</strong> {{ form.location || 'Getting location...' }}</p>
              <p><strong>District:</strong> {{ selectedDistrict?.name || '-' }}</p>
              <p><strong>Sub-district:</strong> {{ form.subdistrict || 'Getting location...' }}</p>
              <p v-if="currentLocationInfo" class="text-xs text-blue-600 mt-1">
                📍 Coordinates: {{ currentLocationInfo.coordinates?.lat.toFixed(6) }}, {{ currentLocationInfo.coordinates?.lng.toFixed(6) }}
              </p>
            </div>
          </div>

          <div class="flex justify-between items-center pt-6 border-t">
            <router-link
              to="/activities"
              class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-md border border-gray-300"
            >
              Cancel
            </router-link>
            <button
              type="submit"
              :disabled="loading"
              class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50 flex items-center"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loading ? 'Creating...' : 'Create & Get Suggestions' }}
            </button>
          </div>
        </form>
      </div>

      <div v-if="showResults" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-bold text-gray-900">Activity Created Successfully!</h2>
              <button @click="closeResults" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <div class="mb-6 p-4 bg-green-50 rounded-lg">
              <h3 class="font-medium text-green-800">{{ createdActivity?.name }}</h3>
              <p class="text-green-700">📍 {{ createdActivity?.location }}, {{ createdActivity?.subdistrict }}</p>
              <p class="text-green-700">📅 {{ formatDate(createdActivity?.preferred_date) }}</p>
            </div>

            <!-- Suggested Time Slots -->
            <div v-if="suggestions.length > 0" class="mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Recommended Time Slots</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="slot in suggestions"
                  :key="`${slot.date}-${slot.time}`"
                  class="p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-indigo-300 hover:bg-indigo-50"
                  :class="{ 'border-indigo-500 bg-indigo-50': selectedSlot === `${slot.date} ${slot.time}` }"
                  @click="selectedSlot = `${slot.date} ${slot.time}`"
                >
                  <div class="flex justify-between items-start mb-2">
                    <div>
                      <p class="font-medium">{{ formatDate(slot.date) }}</p>
                      <p class="text-sm text-gray-600">{{ slot.time }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-lg">{{ getWeatherIcon(slot.weather) }}</p>
                      <div class="flex">
                        <span v-for="i in Math.ceil(slot.suitability_score / 4)" :key="i" class="text-yellow-400 text-sm">⭐</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex justify-between text-sm text-gray-600">
                    <span>{{ slot.temperature }}°C</span>
                    <span>{{ getWeatherLabel(slot.weather) }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex justify-end space-x-3">
                <button
                  @click="closeResults"
                  class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                  Skip for now
                </button>
                <button
                  @click="confirmTimeSlot"
                  :disabled="!selectedSlot || confirmingSlot"
                  class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                >
                  {{ confirmingSlot ? 'Confirming...' : 'Confirm Time Slot' }}
                </button>
              </div>
            </div>

            <div v-else class="text-center py-8">
              <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.002 4.002 0 003 15z"></path>
              </svg>
              <h3 class="text-lg font-medium text-gray-900 mb-2">No suitable time slots found</h3>
              <p class="text-gray-600">The weather conditions for your preferred date may not be ideal for outdoor activities.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useActivitiesStore } from '@/stores/activities'
import api from '@/lib/api'
import AppLayout from '@/components/Layout/AppLayout.vue'

const router = useRouter()
const activitiesStore = useActivitiesStore()

const form = ref({
  name: '',
  location: '',
  subdistrict: '',
  preferred_date: '',
  location_code: '',
  coordinates: null,
  district_code: '',
  province_code: ''
})

const loading = ref(false)
const errors = ref({})
const showResults = ref(false)
const suggestions = ref([])
const createdActivity = ref(null)
const selectedSlot = ref('')
const confirmingSlot = ref(false)

// Location search states
const locationSearchQuery = ref('')
const locationResults = ref([])
const recentLocations = ref([])
const showLocationDropdown = ref(false)
const locationLoading = ref(false)
const searchTimeout = ref(null)

// Hierarchical location states
const provinces = ref([])
const regencies = ref([])
const districts = ref([])
const selectedProvince = ref(null)
const selectedRegency = ref(null)
const selectedDistrict = ref(null)
const regenciesLoading = ref(false)
const districtsLoading = ref(false)

// Current location states
const gettingLocation = ref(false)
const currentLocationInfo = ref(null)

const minDate = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const groupedResults = computed(() => {
  const groups = {}
  locationResults.value.forEach(location => {
    if (!groups[location.level]) {
      groups[location.level] = []
    }
    groups[location.level].push(location)
  })
  return groups
})

onMounted(async () => {
  await loadProvinces()
  // await loadRecentLocations()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showLocationDropdown.value = false
  }
}

const loadProvinces = async () => {
  try {
    const response = await api.get('/location-codes', {
      params: { level: 'province' }
    })
    if (response.data.success) {
      provinces.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load provinces:', error)
  }
}

const onProvinceChange = async () => {
  selectedRegency.value = null
  selectedDistrict.value = null
  regencies.value = []
  districts.value = []
  
  if (!selectedProvince.value) return
  
  regenciesLoading.value = true
  try {
    const response = await api.get('/location-codes', {
      params: { 
        level: 'regency', 
        parent_code: selectedProvince.value.code 
      }
    })
    if (response.data.success) {
      regencies.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load regencies:', error)
  } finally {
    regenciesLoading.value = false
  }
}

const onRegencyChange = async () => {
  selectedDistrict.value = null
  districts.value = []
  
  if (!selectedRegency.value) return
  
  // Update form location
  form.value.location = selectedRegency.value.name
  form.value.location_code = selectedRegency.value.code
  form.value.province_code = selectedProvince.value?.code
  
  districtsLoading.value = true
  try {
    const response = await api.get('/location-codes', {
      params: { 
        level: 'district', 
        parent_code: selectedRegency.value.code 
      }
    })
    if (response.data.success) {
      districts.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to load districts:', error)
  } finally {
    districtsLoading.value = false
  }
}

const onDistrictChange = () => {
  if (selectedDistrict.value) {
    form.value.subdistrict = selectedDistrict.value.name
    form.value.district_code = selectedDistrict.value.code
  }
}

const loadRecentLocations = async () => {
  try {
    const response = await api.get('/recent-locations')
    if (response.data.success) {
      recentLocations.value = response.data.data.map(location => ({
        ...location,
        display_name: `${location.subdistrict}, ${location.location}`,
        level: 'recent',
        hierarchy: 'Recent Location'
      }))
    }
  } catch (error) {
    console.error('Failed to load recent locations:', error)
  }
}

const handleLocationSearch = async () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  if (locationSearchQuery.value.length < 2) {
    locationResults.value = []
    return
  }
  
  searchTimeout.value = setTimeout(async () => {
    locationLoading.value = true
    try {
      // Search across multiple levels
      const searchPromises = [
        api.get('/search-location', { params: { q: locationSearchQuery.value, type: 'province' }}),
        api.get('/search-location', { params: { q: locationSearchQuery.value, type: 'regency' }}),
        api.get('/search-location', { params: { q: locationSearchQuery.value, type: 'district' }})
      ]
      
      const responses = await Promise.allSettled(searchPromises)
      const allResults = []
      
      responses.forEach((response, index) => {
        if (response.status === 'fulfilled' && response.value.data.success) {
          const levelName = ['province', 'regency', 'district'][index]
          const results = response.value.data.data.map(item => ({
            ...item,
            display_name: item.name,
            level: levelName,
            hierarchy: `${getLevelLabel(levelName)}`
          }))
          allResults.push(...results)
        }
      })
      
      locationResults.value = allResults.slice(0, 20)
    } catch (error) {
      console.error('Location search failed:', error)
      locationResults.value = []
    } finally {
      locationLoading.value = false
    }
  }, 300)
}

const selectLocationFromDropdown = async (location) => {
  locationSearchQuery.value = location.display_name
  showLocationDropdown.value = false
  locationResults.value = []
  
  if (location.type === 'recent') {
    // Handle recent location
    form.value.location = location.location
    form.value.subdistrict = location.subdistrict
    form.value.location_code = location.code
    if (location.coordinates) {
      form.value.coordinates = location.coordinates
    }
  } else {
    // Handle search result
    switch (location.level) {
      case 'province':
        selectedProvince.value = location
        await onProvinceChange()
        break
      case 'regency':
        // Need to find and set province first
        await findAndSetParentProvince(location)
        selectedRegency.value = location
        await onRegencyChange()
        break
      case 'district':
        // Need to find and set parent regency and province
        await findAndSetParentHierarchy(location)
        selectedDistrict.value = location
        onDistrictChange()
        break
    }
  }
}

const findAndSetParentProvince = async (regency) => {
  // Find parent province by searching through provinces
  for (const province of provinces.value) {
    try {
      const response = await api.get('/location-codes', {
        params: { level: 'regency', parent_code: province.code }
      })
      if (response.data.success) {
        const foundRegency = response.data.data.find(r => r.code === regency.code)
        if (foundRegency) {
          selectedProvince.value = province
          regencies.value = response.data.data
          break
        }
      }
    } catch (error) {
      continue
    }
  }
}

const findAndSetParentHierarchy = async (district) => {
  // This is more complex - would need to implement parent lookup
  // For now, just set the district info
  form.value.subdistrict = district.name
  form.value.district_code = district.code
}

const getLevelLabel = (level) => {
  const labels = {
    province: 'Provinces',
    regency: 'Cities/Regencies',
    district: 'Districts/Sub-districts',
    recent: 'Recent'
  }
  return labels[level] || level
}

const getCurrentLocation = async () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by this browser.')
    return
  }

  gettingLocation.value = true
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        const { latitude, longitude } = position.coords
        
        const response = await api.post('/reverse-geocode', {
          lat: latitude,
          lng: longitude
        })
        
        if (response.data.success) {
          const locationData = response.data.data
          currentLocationInfo.value = locationData
          
          form.value.location = locationData.location
          form.value.subdistrict = locationData.subdistrict
          form.value.location_code = locationData.location_code
          form.value.coordinates = locationData.coordinates
          locationSearchQuery.value = locationData.location
        } else {
          throw new Error(response.data.message || 'Failed to get location')
        }
      } catch (error) {
        console.error('Reverse geocoding failed:', error)
        alert('Failed to get your current location. Please try again.')
      } finally {
        gettingLocation.value = false
      }
    },
    (error) => {
      console.error('Geolocation error:', error)
      gettingLocation.value = false
      
      let message = 'Unable to get your location. '
      switch(error.code) {
        case error.PERMISSION_DENIED:
          message += 'Please enable location access in your browser settings.'
          break
        case error.POSITION_UNAVAILABLE:
          message += 'Location information is unavailable.'
          break
        case error.TIMEOUT:
          message += 'Location request timed out.'
          break
      }
      alert(message)
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 300000
    }
  )
}

const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  // Include hierarchical codes in submission
  const submissionData = {
    ...form.value,
    province_code: selectedProvince.value?.code || form.value.province_code,
    district_code: selectedDistrict.value?.code || form.value.district_code
  }

  const result = await activitiesStore.createActivity(submissionData)
  
  if (result.success) {
    createdActivity.value = result.data.activity
    suggestions.value = result.data.suggested_slots || []
    showResults.value = true
    
    // Reset form
    form.value = {
      name: '',
      location: '',
      subdistrict: '',
      preferred_date: '',
      location_code: '',
      coordinates: null,
      district_code: '',
      province_code: ''
    }
    locationSearchQuery.value = ''
    currentLocationInfo.value = null
    selectedProvince.value = null
    selectedRegency.value = null
    selectedDistrict.value = null
    regencies.value = []
    districts.value = []
  } else {
    errors.value = result.errors
  }
  
  loading.value = false
}

const confirmTimeSlot = async () => {
  if (!selectedSlot.value || !createdActivity.value) return

  confirmingSlot.value = true
  
  const result = await activitiesStore.updateActivity(createdActivity.value.id, {
    selected_slot: selectedSlot.value,
    status: 'scheduled'
  })

  if (result.success) {
    closeResults()
    router.push('/activities')
  }
  
  confirmingSlot.value = false
}

const closeResults = () => {
  showResults.value = false
  selectedSlot.value = ''
  createdActivity.value = null
  suggestions.value = []
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getWeatherIcon = (weather) => {
  const icons = {
    sunny: '☀️',
    partly_cloudy: '⛅',
    cloudy: '☁️',
    rainy: '🌧️',
    thunderstorm: '⛈️'
  }
  return icons[weather] || '🌤️'
}

const getWeatherLabel = (weather) => {
  const labels = {
    sunny: 'Sunny',
    partly_cloudy: 'Partly Cloudy',
    cloudy: 'Cloudy',
    rainy: 'Rainy',
    thunderstorm: 'Thunderstorm'
  }
  return labels[weather] || 'Unknown'
}
</script>