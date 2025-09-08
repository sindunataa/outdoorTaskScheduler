<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Activities</h1>
        <router-link
          to="/activities/create"
          class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          New Activity
        </router-link>
      </div>

      <div v-if="activitiesStore.loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">Loading activities...</p>
      </div>

      <div v-else-if="activitiesStore.activities.length === 0" class="text-center py-12">
        <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No activities yet</h3>
        <p class="text-gray-600 mb-6">Start by creating your first outdoor activity.</p>
        <router-link
          to="/activities/create"
          class="bg-indigo-600 text-white px-6 py-3 rounded-md hover:bg-indigo-700"
        >
          Create Activity
        </router-link>
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="activity in activitiesStore.activities"
          :key="activity.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
        >
          <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
              <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ activity.name }}</h2>
              <div class="flex items-center space-x-4 text-sm text-gray-600">
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  {{ activity.location }}, {{ activity.subdistrict }}
                </div>
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  {{ formatDate(activity.preferred_date) }}
                </div>
              </div>
            </div>
            
            <div class="flex items-center space-x-3">
              <span :class="getStatusClass(activity.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ activity.status }}
              </span>
              <button
                @click="deleteActivity(activity.id)"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Selected Slot -->
          <div v-if="activity.selected_slot" class="mb-4 p-3 bg-green-50 rounded-lg">
            <p class="text-sm font-medium text-green-800">Selected Time Slot:</p>
            <p class="text-green-700">{{ activity.selected_slot }}</p>
          </div>

          <!-- Suggested Slots -->
          <div v-if="activity.suggested_slots && activity.suggested_slots.length > 0" class="mb-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Suggested Time Slots:</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
              <div
                v-for="slot in activity.suggested_slots.slice(0, 6)"
                :key="`${slot.date}-${slot.time}`"
                class="p-3 border rounded-lg cursor-pointer hover:bg-blue-50"
                :class="{ 'bg-blue-100 border-blue-300': activity.selected_slot === `${slot.date} ${slot.time}` }"
                @click="selectTimeSlot(activity, slot)"
              >
                <div class="flex justify-between items-center">
                  <div>
                    <p class="font-medium text-sm">{{ formatDate(slot.date) }}</p>
                    <p class="text-sm text-gray-600">{{ slot.time }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm">{{ getWeatherIcon(slot.weather) }} {{ slot.temperature }}°C</p>
                    <div class="flex">
                      <span v-for="i in Math.ceil(slot.suitability_score / 4)" :key="i" class="text-yellow-400">⭐</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Weather Data -->
          <div v-if="activity.weather_data && activity.weather_data.length > 0" class="border-t pt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">3-Day Weather Forecast:</p>
            <div class="flex space-x-4 overflow-x-auto">
              <div
                v-for="forecast in activity.weather_data"
                :key="forecast.date"
                class="flex-shrink-0 p-3 bg-gray-50 rounded-lg min-w-[120px]"
              >
                <p class="font-medium text-sm">{{ formatDate(forecast.date) }}</p>
                <p class="text-sm">{{ getWeatherIcon(forecast.weather) }} {{ forecast.temperature }}°C</p>
                <p class="text-xs text-gray-600">{{ forecast.humidity }}% humidity</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useActivitiesStore } from '@/stores/activities'
import AppLayout from '@/components/Layout/AppLayout.vue'

const activitiesStore = useActivitiesStore()

const selectTimeSlot = async (activity, slot) => {
  const selectedSlot = `${slot.date} ${slot.time}`
  const result = await activitiesStore.updateActivity(activity.id, {
    selected_slot: selectedSlot,
    status: 'scheduled'
  })
  
  if (result.success) {
    // Refresh activities
    activitiesStore.fetchActivities()
  }
}

const deleteActivity = async (id) => {
  if (confirm('Are you sure you want to delete this activity?')) {
    await activitiesStore.deleteActivity(id)
  }
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

onMounted(() => {
  activitiesStore.fetchActivities()
})
</script>