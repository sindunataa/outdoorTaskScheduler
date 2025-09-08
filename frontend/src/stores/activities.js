import { defineStore } from 'pinia'
import api from '@/lib/api'

export const useActivitiesStore = defineStore('activities', {
  state: () => ({
    activities: [],
    currentActivity: null,
    loading: false,
    suggestions: [],
    // Hierarchical location states
    provinces: [],
    regencies: [],
    districts: [],
    villages: [],
    locationHierarchy: {}
  }),

  actions: {
    async fetchActivities() {
      this.loading = true
      try {
        const response = await api.get('/activities')
        this.activities = response.data.data
      } catch (error) {
        console.error('Failed to fetch activities:', error)
      } finally {
        this.loading = false
      }
    },

    async createActivity(activityData) {
      try {
        const payload = {
          name: activityData.name,
          location: activityData.location,
          subdistrict: activityData.subdistrict,
          preferred_date: activityData.preferred_date,
          location_code: activityData.location_code || null,
          district_code: activityData.district_code || null,
          province_code: activityData.province_code || null,
          coordinates: activityData.coordinates ? JSON.stringify(activityData.coordinates) : null
        }

        const response = await api.post('/activities', payload)
        this.activities.unshift(response.data.activity)
        this.suggestions = response.data.suggested_slots || []
        return { success: true, data: response.data }
      } catch (error) {
        console.error('Create activity error:', error)
        return { 
          success: false, 
          errors: error.response?.data?.errors || { general: ['Failed to create activity'] }
        }
      }
    },

    async updateActivity(id, data) {
      try {
        const response = await api.put(`/activities/${id}`, data)
        const index = this.activities.findIndex(a => a.id === id)
        if (index !== -1) {
          this.activities[index] = response.data
        }
        return { success: true }
      } catch (error) {
        console.error('Update activity error:', error)
        return { success: false, errors: error.response?.data?.errors }
      }
    },

    async deleteActivity(id) {
      try {
        await api.delete(`/activities/${id}`)
        this.activities = this.activities.filter(a => a.id !== id)
        return { success: true }
      } catch (error) {
        console.error('Delete activity error:', error)
        return { success: false }
      }
    },

    async getWeatherForecast(location) {
      try {
        const response = await api.post('/weather-forecast', { location })
        return { success: true, data: response.data.weather_data }
      } catch (error) {
        console.error('Weather forecast error:', error)
        return { success: false }
      }
    },

    // Enhanced location search methods
    async searchLocations(query, type = 'regency') {
      try {
        const response = await api.get('/search-location', {
          params: { q: query, type }
        })
        return { success: true, data: response.data.data }
      } catch (error) {
        console.error('Location search error:', error)
        return { success: false, data: [] }
      }
    },

    async searchAllLocations(query) {
      try {
        // Search across all location levels
        const [provinceResponse, regencyResponse, districtResponse] = await Promise.allSettled([
          api.get('/search-location', { params: { q: query, type: 'province' }}),
          api.get('/search-location', { params: { q: query, type: 'regency' }}),
          api.get('/search-location', { params: { q: query, type: 'district' }})
        ])

        const allResults = []

        if (provinceResponse.status === 'fulfilled' && provinceResponse.value.data.success) {
          allResults.push(...provinceResponse.value.data.data)
        }
        if (regencyResponse.status === 'fulfilled' && regencyResponse.value.data.success) {
          allResults.push(...regencyResponse.value.data.data)
        }
        if (districtResponse.status === 'fulfilled' && districtResponse.value.data.success) {
          allResults.push(...districtResponse.value.data.data)
        }

        return { success: true, data: allResults }
      } catch (error) {
        console.error('Multi-level location search error:', error)
        return { success: false, data: [] }
      }
    },

    async getRecentLocations() {
      try {
        const response = await api.get('/recent-locations')
        return { success: true, data: response.data.data }
      } catch (error) {
        console.error('Recent locations error:', error)
        return { success: false, data: [] }
      }
    },

    async reverseGeocode(lat, lng) {
      try {
        const response = await api.post('/reverse-geocode', { lat, lng })
        return { success: true, data: response.data.data }
      } catch (error) {
        console.error('Reverse geocode error:', error)
        return { success: false, data: null }
      }
    },

    // Hierarchical location methods
    async getLocationCodes(level = 'regency', parentCode = '') {
      try {
        const response = await api.get('/location-codes', {
          params: { level, parent_code: parentCode }
        })
        
        if (response.data.success) {
          // Cache the results in store
          switch (level) {
            case 'province':
              this.provinces = response.data.data
              break
            case 'regency':
              this.regencies = response.data.data
              break
            case 'district':
              this.districts = response.data.data
              break
            case 'village':
              this.villages = response.data.data
              break
          }
        }
        
        return { success: true, data: response.data.data }
      } catch (error) {
        console.error('Location codes error:', error)
        return { success: false, data: [] }
      }
    },

    async getLocationHierarchy(code, level) {
      try {
        const response = await api.get('/location-hierarchy', {
          params: { code, level }
        })
        
        if (response.data.success) {
          this.locationHierarchy[`${level}_${code}`] = response.data.data
        }
        
        return { success: true, data: response.data.data }
      } catch (error) {
        console.error('Location hierarchy error:', error)
        return { success: false, data: {} }
      }
    },
    
    async loadProvinces() {
      return await this.getLocationCodes('province')
    },

    async loadRegencies(provinceCode) {
      if (!provinceCode) {
        this.regencies = []
        return { success: true, data: [] }
      }
      return await this.getLocationCodes('regency', provinceCode)
    },

    async loadDistricts(regencyCode) {
      if (!regencyCode) {
        this.districts = []
        return { success: true, data: [] }
      }
      return await this.getLocationCodes('district', regencyCode)
    },

    async loadVillages(districtCode) {
      if (!districtCode) {
        this.villages = []
        return { success: true, data: [] }
      }
      return await this.getLocationCodes('village', districtCode)
    },

    async getLocationSuggestions(query, options = {}) {
      const { 
        level = 'all', 
        parentCode = '',
        limit = 10,
        includeHierarchy = true 
      } = options

      try {
        if (query.length < 2) {
          return { success: true, data: [] }
        }

        let searchResults = []

        if (level === 'all') {
          const result = await this.searchAllLocations(query)
          searchResults = result.data
        } else {
          const result = await this.searchLocations(query, level)
          searchResults = result.data
        }

        if (includeHierarchy) {
          searchResults = await this.enhanceWithHierarchy(searchResults)
        }

        const sortedResults = this.sortLocationsByRelevance(searchResults, query)
        return { 
          success: true, 
          data: sortedResults.slice(0, limit)
        }

      } catch (error) {
        console.error('Location suggestions error:', error)
        return { success: false, data: [] }
      }
    },

    async enhanceWithHierarchy(locations) {
      const enhanced = []

      for (const location of locations) {
        try {
          const hierarchyResult = await this.getLocationHierarchy(location.code, location.level)
          if (hierarchyResult.success) {
            location.hierarchy_data = hierarchyResult.data
            location.full_hierarchy = this.buildHierarchyString(hierarchyResult.data)
          }
        } catch (error) {
        }
        enhanced.push(location)
      }

      return enhanced
    },

    buildHierarchyString(hierarchy) {
      const parts = []
      
      if (hierarchy.village) parts.push(hierarchy.village.name)
      if (hierarchy.district) parts.push(hierarchy.district.name)
      if (hierarchy.regency) parts.push(hierarchy.regency.name)
      if (hierarchy.province) parts.push(hierarchy.province.name)
      
      return parts.join(' → ')
    },

    sortLocationsByRelevance(locations, query) {
      return locations.sort((a, b) => {
        const aExact = a.name.toLowerCase() === query.toLowerCase() ? 10 : 0
        const bExact = b.name.toLowerCase() === query.toLowerCase() ? 10 : 0
        
        const aStarts = a.name.toLowerCase().startsWith(query.toLowerCase()) ? 5 : 0
        const bStarts = b.name.toLowerCase().startsWith(query.toLowerCase()) ? 5 : 0
        
        const levelPriority = { village: 4, district: 3, regency: 2, province: 1 }
        const aLevel = levelPriority[a.level] || 0
        const bLevel = levelPriority[b.level] || 0
        
        const aScore = aExact + aStarts + aLevel
        const bScore = bExact + bStarts + bLevel
        
        return bScore - aScore
      })
    },

    validateLocationData(locationData) {
      const errors = {}
      
      if (!locationData.location || locationData.location.trim().length < 2) {
        errors.location = ['Location is required and must be at least 2 characters']
      }
      
      if (!locationData.subdistrict || locationData.subdistrict.trim().length < 2) {
        errors.subdistrict = ['Sub-district is required and must be at least 2 characters']
      }
      
      return {
        isValid: Object.keys(errors).length === 0,
        errors
      }
    },

    formatLocationForSubmission(locationData) {
      return {
        location: locationData.location?.trim(),
        subdistrict: locationData.subdistrict?.trim(),
        location_code: locationData.location_code,
        district_code: locationData.district_code,
        province_code: locationData.province_code,
        coordinates: locationData.coordinates ? JSON.stringify(locationData.coordinates) : null
      }
    },

    clearLocationCache() {
      this.provinces = []
      this.regencies = []
      this.districts = []
      this.villages = []
      this.locationHierarchy = {}
    },

    getLocationDisplayName(location) {
      if (!location) return ''
      
      if (location.subdistrict && location.location) {
        return `${location.subdistrict}, ${location.location}`
      }
      
      return location.location || location.name || ''
    },

    getLocationBreadcrumb(hierarchy) {
      const parts = []
      
      if (hierarchy.province) parts.push(hierarchy.province.name)
      if (hierarchy.regency) parts.push(hierarchy.regency.name)  
      if (hierarchy.district) parts.push(hierarchy.district.name)
      if (hierarchy.village) parts.push(hierarchy.village.name)
      
      return parts.join(' › ')
    },

    async findLocationByName(name, level = 'regency') {
      try {
        const result = await this.searchLocations(name, level)
        if (result.success && result.data.length > 0) {
          const exactMatch = result.data.find(loc => 
            loc.name.toLowerCase() === name.toLowerCase()
          )
          return exactMatch || result.data[0]
        }
        return null
      } catch (error) {
        console.error('Find location by name error:', error)
        return null
      }
    },

    clearActivities() {
      this.activities = []
      this.currentActivity = null
      this.suggestions = []
      this.clearLocationCache()
    }
  }
})