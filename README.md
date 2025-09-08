# outdoorTaskScheduler# 

A web application for scheduling outdoor activities with weather forecast integration using BMKG API. The application suggests optimal time slots based on weather conditions to help users plan their outdoor tasks effectively.

## Tech Stack

- **Frontend**: Vue.js
- **Backend**: Laravel (PHP)
- **Database**: MySQL/PostgreSQL
- **Weather API**: BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)

## Project Structure

```
outdoorTaskScheduler/
├── frontend/          # Vue.js application
├── backend/           # Laravel API
└── README.md
```

## Prerequisites

Before running this application, make sure you have the following installed:

- **Node.js** (v16 or higher)
- **npm** or **yarn**
- **PHP** (v8.0 or higher)
- **Composer**
- **MySQL** or **PostgreSQL**
- **Git**

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd outdoorTaskScheduler
```

### 2. Backend Setup (Laravel)

#### Navigate to backend directory
```bash
cd backend
```

#### Install PHP dependencies
```bash
composer install
```

#### Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### Configure Database
Edit the `.env` file and update the database configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=outdoor_scheduler
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Run Database Migrations
```bash
# Create database tables
php artisan migrate

# (Optional) Seed sample data
php artisan db:seed
```

#### Start Laravel Development Server
```bash
php artisan serve
```

The Laravel API will be available at: `http://localhost:8000`

### 3. Frontend Setup (Vue.js)

#### Navigate to frontend directory (open new terminal)
```bash
cd frontend
```

#### Install Node.js dependencies
```bash
npm install
# or if using yarn
yarn install
```

#### Environment Configuration
Create a `.env` file in the frontend directory:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_BMKG_API_URL=https://data.bmkg.go.id/prakiraan-cuaca
```

#### Start Vue.js Development Server
```bash
npm run dev
# or if using yarn
yarn dev
```

The Vue.js application will be available at: `http://localhost:5173`

## Usage

1. **Access the Application**: Open your browser and navigate to `http://localhost:5173`

2. **Schedule Activity**:
   - Fill in the activity form with:
     - Activity name
     - Location (sub-district/village)
     - Preferred date
   - Click submit to get weather-based time slot suggestions

3. **View Suggestions**: The application will display optimal time slots based on weather conditions (clear/cloudy weather preferred over rainy conditions)

4. **Select Time Slot**: Choose your preferred time slot from the suggestions and confirm the booking

## API Endpoints

### Backend API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/activities` | Get all scheduled activities |
| POST | `/api/activities` | Create new activity |
| GET | `/api/weather-forecast` | Get weather forecast for location |
| POST | `/api/schedule` | Schedule activity with selected time slot |

### BMKG API Integration

The application integrates with BMKG Weather Forecast API:
- **Base URL**: `https://data.bmkg.go.id/prakiraan-cuaca/`
- **Forecast Period**: 3 days
- **Data Format**: XML/JSON weather data

## Database Schema

### Activities Table
```sql
- id (Primary Key)
- activity_name (VARCHAR)
- location (VARCHAR)
- preferred_date (DATE)
- selected_time_slot (DATETIME)
- weather_condition (VARCHAR)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## Features

- ✅ Activity scheduling form
- ✅ Weather forecast integration with BMKG API
- ✅ Intelligent time slot suggestions based on weather
- ✅ Activity logging in database
- ✅ Responsive web interface
- ✅ 3-day weather forecast display

## Development Notes

### Weather Filtering Logic
The application filters weather conditions as follows:
- **Favorable**: Clear, Partly Cloudy, Cloudy
- **Unfavorable**: Rain, Heavy Rain, Thunderstorm

### Mock Data Support
If BMKG API is unavailable, the application can use mock weather data for development purposes.

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   ```bash
   # Check database credentials in .env file
   # Ensure database server is running
   php artisan config:clear
   ```

2. **CORS Issues**
   ```bash
   # In Laravel backend, check config/cors.php
   # Ensure frontend URL is allowed
   ```

3. **API Integration Issues**
   ```bash
   # Check BMKG API endpoint availability
   # Verify network connectivity
   # Check API rate limits
   ```

4. **Frontend Build Issues**
   ```bash
   # Clear node_modules and reinstall
   rm -rf node_modules
   npm install
   ```

## Production Deployment

### Backend (Laravel)
1. Set environment to production in `.env`
2. Optimize application: `php artisan optimize`
3. Configure web server (Apache/Nginx)
4. Set up SSL certificate

### Frontend (Vue.js)
1. Build for production: `npm run build`
2. Deploy dist folder to web server
3. Configure environment variables for production API

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit pull request

## License

This project is licensed under the MIT License.

## Support

For questions or issues, please contact the development team or create an issue in the repository.

---

**Note**: This application was developed as part of a technical assessment for outdoor activity scheduling with weather integration capabilities.