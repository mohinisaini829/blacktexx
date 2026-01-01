# Santafatex Brands Module

A comprehensive Shopware 6 plugin for managing brands with file uploads and rich media support.

## Features

### Core Functionality
- ✅ **Full CRUD Operations**: Create, Read, Update, and Delete brands
- ✅ **File Management**: Upload size charts (PDF/Images) and catalog PDFs
- ✅ **HTML Support**: Add custom HTML for video sliders
- ✅ **Ordering**: Set display order for brand sorting
- ✅ **Status Control**: Activate/deactivate brands

### Admin Interface
- Clean and intuitive admin panel integrated into Shopware backend
- Multi-language support (English & German)
- Real-time form validation
- File upload with preview
- Bulk operations support

## Installation

### Prerequisites
- Shopware 6.4.x, 6.5.x, or 6.6.x
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation Steps

1. **Place the plugin in the correct directory**:
```bash
cd /var/www/html/Santafetex/custom/plugins/
```

2. **Install the plugin**:
```bash
bin/console plugin:install --activate SantafatexBrands
```

3. **Clear cache**:
```bash
bin/console cache:clear
```

4. **Build administration assets** (if needed):
```bash
bin/console administration:build
```

## Database Structure

The plugin creates a `santafatex_brand` table with the following structure:

| Field | Type | Description |
|-------|------|-------------|
| id | BINARY(16) | Primary key |
| name | VARCHAR(255) | Brand name (required) |
| description | LONGTEXT | Brand description |
| size_chart_path | VARCHAR(500) | Path to size chart file |
| video_slider_html | LONGTEXT | HTML code for video slider |
| catalog_pdf_path | VARCHAR(500) | Path to catalog PDF |
| active | TINYINT(1) | Brand status (default: 1) |
| display_order | INT | Display order (default: 0) |
| created_at | DATETIME(3) | Creation timestamp |
| updated_at | DATETIME(3) | Last update timestamp |

## Usage

### Accessing the Module

1. Login to Shopware admin panel
2. Navigate to **Brands** in the main menu (left sidebar)
3. You will see the brand list view

### Creating a Brand

1. Click **"Create Brand"** button
2. Fill in the following information:
   - **Brand Name** (required): Enter the brand name
   - **Description** (optional): Add a detailed description
   - **Active**: Check to activate the brand
   - **Display Order**: Set the position in lists (lower numbers appear first)

3. **Upload Files**:
   - Click "Upload File" for **Size Chart** (PDF, JPG, PNG, GIF)
   - Click "Upload File" for **Catalog PDF** (PDF, JPG, PNG, GIF)

4. **Video Slider HTML**:
   - Paste your HTML code for the video slider
   - Supports: `<iframe>`, `<video>`, or custom HTML

5. Click **"Save Brand"** to save

### Editing a Brand

1. In the brand list, click on the brand name or use the "Edit" action
2. Modify the information as needed
3. Click **"Save Brand"**

### Deleting a Brand

1. In the brand list, click the "Delete" action
2. Confirm the deletion in the modal dialog
3. The brand will be permanently removed

## API Endpoints

The plugin exposes the following REST API endpoints:

### List all brands
```
GET /api/santafatex-brands?limit=25&offset=0
```

### Get single brand
```
GET /api/santafatex-brands/{brandId}
```

### Create brand
```
POST /api/santafatex-brands
Content-Type: application/json

{
  "name": "Brand Name",
  "description": "Description",
  "active": true,
  "displayOrder": 0,
  "videoSliderHtml": "<html code>"
}
```

### Update brand
```
PATCH /api/santafatex-brands/{brandId}
Content-Type: application/json

{
  "name": "Updated Name",
  "active": false
}
```

### Delete brand
```
DELETE /api/santafatex-brands/{brandId}
```

## File Upload Handling

Files are uploaded to: `/public/uploads/brands/`

### Directory Structure
```
/public/uploads/brands/
├── size-charts/     # Size chart files
└── catalogs/        # Catalog PDF files
```

### Supported File Types
- **PDF**: .pdf
- **Images**: .jpg, .jpeg, .png, .gif
- **Maximum Size**: Limited by PHP configuration (default: 2MB)

## Configuration

### Service Configuration

Services are defined in `resources/config/services.xml`:

- `Santafatex\Brands\Core\Content\Brand\BrandDefinition`: Entity definition
- `Santafatex\Brands\Service\BrandService`: Business logic service
- `Santafatex\Brands\Controller\Admin\BrandController`: API controller
- `santafatex.brand.repository`: EntityRepository for brands

## Translations

The plugin supports multiple languages:

- **English (en-GB)**: `resources/app/administration/snippet/en-GB/santafatex-brands.json`
- **German (de-DE)**: `resources/app/administration/snippet/de-DE/santafatex-brands.json`

To add more languages, create new JSON files in the respective language folders.

## Project Structure

```
SantafatexBrands/
├── src/
│   ├── Core/Content/Brand/
│   │   ├── BrandEntity.php          # Entity class
│   │   ├── BrandDefinition.php      # Entity definition
│   │   └── BrandCollection.php      # Collection class
│   ├── Controller/Admin/
│   │   └── BrandController.php      # API controller
│   ├── Service/
│   │   └── BrandService.php         # Business logic
│   └── Migration/
│       └── Migration20260101000000InitialSetup.php
├── resources/
│   ├── config/
│   │   ├── services.xml
│   │   └── routes.xml
│   ├── views/
│   └── app/administration/
│       ├── src/module/brand/
│       │   ├── page/
│       │   │   ├── brand-index.js
│       │   │   ├── brand-detail.js
│       │   │   └── ...
│       │   └── index.js
│       └── snippet/
│           ├── en-GB/
│           └── de-DE/
├── SantafatexBrands.php             # Main plugin class
└── composer.json
```

## Development

### Installing Dependencies

```bash
composer install
```

### Running Tests

```bash
bin/phpunit
```

### Building for Distribution

```bash
bin/console plugin:zip -n SantafatexBrands
```

## Troubleshooting

### Module not appearing in admin menu

1. Clear the cache:
```bash
bin/console cache:clear
```

2. Rebuild admin assets:
```bash
bin/console administration:build
```

3. Reload the admin panel in your browser

### File upload not working

1. Check file permissions on `/public/uploads/` directory
2. Ensure the directory exists: `mkdir -p public/uploads/brands/{size-charts,catalogs}`
3. Set correct permissions: `chmod 755 public/uploads/brands/`

### Database migration errors

1. Check database connectivity
2. Run migrations manually:
```bash
bin/console database:migrate --all
```

## Support

For issues and feature requests, please contact your administrator.

## License

MIT License - See LICENSE file for details

## Changelog

### Version 1.0.0 (2026-01-01)
- Initial release
- Full CRUD functionality
- File upload support
- Admin interface
- Multi-language support
