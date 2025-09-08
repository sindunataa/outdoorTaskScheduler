<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600">Welcome back, {{ authStore.user?.name }}!</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Activities</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Scheduled</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.scheduled }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Pending</p>
              <p class="text-2xl font-bold text-gray-900">{{ stats.pending }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
          <div class="flex flex-wrap gap-4">
            <router-link
              to="/activities/create"
              class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              Schedule New Activity
            </router-link>
            <button
              @click="checkWeather"
              :disabled="loadingWeather"
              class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center disabled:opacity-50"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.002 4.002 0 003 15z"></path>
              </svg>
              {{ loadingWeather ? 'Checking...' : 'Check Weather' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-900">Recent Activities</h2>
            <router-link to="/activities" class="text-indigo-600 hover:text-indigo-700">
              View all
            </router-link>
          </div>
          
          <div v-if="activitiesStore.loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
          </div>
          
          <div v-else-if="recentActivities.length === 0" class="text-center py-8 text-gray-500">
            No activities yet. Create your first activity!
          </div>
          
          <div v-else class="space-y-4">
            <div
              v-for="activity in recentActivities"
              :key="activity.id"
              class="border rounded-lg p-4 hover:bg-gray-50"
            >
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-medium text-gray-900">{{ activity.name }}</h3>
                  <p class="text-sm text-gray-600">
                    📍 {{ activity.location }}, {{ activity.subdistrict }}
                  </p>
                  <p class="text-sm text-gray-500">
                    📅 {{ formatDate(activity.preferred_date) }}
                  </p>
                </div>
                <span :class="getStatusClass(activity.status)" class="px-2 py-1 rounded text-xs font-medium">
                  {{ activity.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Weather Modal -->
      <div v-if="showWeatherModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Weather Forecast - Jakarta</h3>
            <button @click="showWeatherModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <div class="space-y-3">
            <div v-for="forecast in weatherData" :key="forecast.date" class="flex justify-between items-center p-3 bg-gray-50 rounded">
              <div>
                <p class="font-medium">{{ formatDate(forecast.date) }}</p>
                <p class="text-sm text-gray-600">{{ getWeatherIcon(forecast.weather) }} {{ getWeatherLabel(forecast.weather) }}</p>
              </div>
              <div class="text-right">
                <p class="font-medium">{{ forecast.temperature }}°C</p>
                <p class="text-sm text-gray-600">{{ forecast.humidity }}% humidity</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useActivitiesStore } from '@/stores/activities'
import AppLayout from '@/components/Layout/AppLayout.vue'

const authStore = useAuthStore()
const activitiesStore = useActivitiesStore()

const loadingWeather = ref(false)
const showWeatherModal = ref(false)
const weatherData = ref([])

const recentActivities = computed(() => {
  return activitiesStore.activities.slice(0, 5)
})

const stats = computed(() => {
  const activities = activitiesStore.activities
  return {
    total: activities.length,
    scheduled: activities.filter(a => a.status === 'scheduled').length,
    pending: activities.filter(a => a.status === 'pending').length
  }
})

const checkWeather = async () => {
  loadingWeather.value = true
  const result = await activitiesStore.getWeatherForecast('Jakarta')
  if (result.success) {
    weatherData.value = result.data
    showWeatherModal.value = true
  }
  loadingWeather.value = false
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    scheduled: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
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

onMounted(() => {
  activitiesStore.fetchActivities()
})
</script>