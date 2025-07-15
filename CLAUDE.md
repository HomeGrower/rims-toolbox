# RIMS Tool - Project Documentation

## Overview
This is a Laravel-based hotel setup management system that allows hotels to configure their properties for integration with various modules and systems. The application uses Docker for containerization, Vue.js/Inertia for the frontend, and Filament for the admin panel.

## Current Status (July 2, 2025)

### Recent Implementations

#### 1. PMS-Specific Policy Configuration
We've implemented a flexible system where each PMS (Property Management System) type can define which policy fields should be available:

- **Cancellation Policies**
- **Special Requests** 
- **Deposit Policies**
- **Payment Methods**
- **Transfer Types**

Each PMS type can enable/disable these fields independently through the admin panel, with example images for visual guidance.

#### 2. Dynamic Form System
The setup process is divided into teams:
- **Reservation Team**: Hotel settings, user settings, reservation settings, room types, and dynamic policy sections
- **Marketing Team**: Banners, logos, colors & fonts, room details, greetings, promotions
- **IT Team**: Email settings, PMS settings, security settings

#### 3. Key Features Implemented
- **Datastore Builder**: Visual tool for configuring module overrides
- **Dynamic Field Generation**: Fields are generated based on PMS type, selected modules, and brand configurations
- **Progress Tracking**: Dashboard shows completion status for each section
- **Conditional Fields**: Policy sections only appear when enabled in reservation settings
- **Example Images**: PMS administrators can upload example images to guide hotels

### Database Structure

#### Key Tables
- `projects`: Main project/hotel information
- `project_setup_teams`: Tracks setup progress per team/section
- `pms_types`: PMS configurations including `reservation_settings_config` and `policy_example_images`
- `project_data`: Stores all setup form data
- `modules`: Available modules that can be selected
- `hotel_chains` & `hotel_brands`: Hotel hierarchy

### File Structure

#### Backend (Laravel)
- `app/Models/ProjectSetupTeam.php`: Contains all field definitions and business logic
- `app/Http/Controllers/SetupController.php`: Handles form display and submission
- `app/Http/Controllers/ClientDashboardController.php`: Dashboard logic
- `app/Filament/Admin/Resources/PmsTypeResource.php`: Admin configuration for PMS types

#### Frontend (Vue/Inertia)
- `resources/js/Pages/Setup/Form.vue`: Main setup form component
- `resources/js/Pages/Dashboard.vue`: Client dashboard
- `resources/js/components/DatastoreBuilder.vue`: Visual configuration tool

### Current Workflow

1. **Admin Setup**:
   - Configure PMS types with enabled policy fields
   - Upload example images for each policy type
   - Set up hotel chains, brands, and modules

2. **Hotel Setup Process**:
   - Hotels access via access code
   - See dashboard with progress for each team
   - Fill out forms section by section
   - Policy sections appear dynamically based on PMS configuration

3. **Policy Configuration**:
   - In reservation settings, hotels indicate which policies they use
   - Corresponding sections appear in the dashboard
   - Hotels map their PMS codes to human-readable descriptions

### Technical Details

#### Form Field Types Supported
- text, email, url, number, password
- textarea, select, checkbox/boolean
- file/files upload
- date, time, color
- repeater (for dynamic lists)
- Special types: section_header, info_display, image_display

#### Validation & Auto-save
- All setup fields are optional (nullable)
- Checkbox auto-save disabled except for `show_room_details_in_templates`
- File uploads stored in `storage/app/public/projects/{id}/{team}/{section}`

### Recent Bug Fixes
- Fixed Vue runtime compilation error in datastore builder
- Corrected panel layout issues (side-by-side instead of stacked)
- Fixed checkbox auto-save behavior
- Resolved policy section visibility based on `completed_fields` instead of `fields`
- Replaced show_small_label/show_large_label with show_button/show_url in promotion types
- Fixed image upload display for promotion_tiles

### Commands to Run
```bash
# Start Docker containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Clear caches if needed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# Build frontend assets
docker-compose exec app npm run build

# Create a backup (requires mysqldump in container)
docker-compose exec app php artisan backup:run --only-db
```

### Greeting Text System
- **Paragraph Structure**: Up to 10 paragraph positions, each with multiple priority variations
- **Module Assignment**: Paragraphs must be assigned to modules to appear in templates
- **Conditions**: Support for both general and chain-specific conditions
- **Preview**: Real-time preview with module and condition selection

### Conditions Management
- **Admin Interface**: Located in Settings → Conditions in admin panel
- **Types**: 
  - General conditions (available for all chains)
  - Chain-specific conditions (only for selected chain)
- **Usage**: Used in greeting texts for conditional paragraph display
- **Examples**: Children, VIP Guest, Business Travel, Chain-specific loyalty tiers

### Next Steps / Known Issues
1. The promotions system uses brand-specific promotion types
2. Room types configuration depends on selected modules
3. Policy example images are stored per PMS type, not per hotel
4. Transfer types follow the same pattern as other policy types

### Important Notes
- Always load the `pmsType` relationship when working with projects
- The `shouldShowReservationPolicyField()` method determines field visibility
- Policy sections only appear if both PMS allows it AND hotel has checked the box
- Example images from PMS are shown in reservation settings for guidance

### Backup System Notes
- The backup system uses Spatie Laravel Backup package
- Backups are stored in `storage/app/backup/rims-backup/`
- Access the backup manager at `/admin/system/backups`
- **Docker Fix**: The PHP containers must have `default-mysql-client` installed for database backups to work
- Backup types: Database-only, Files-only, or Full backup
- Scheduled backups can be configured through the admin panel
- The `RunScheduledBackup` command runs daily at 2:00 AM if enabled