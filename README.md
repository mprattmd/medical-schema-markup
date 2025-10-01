# Medical Schema Markup Generator Pro

A comprehensive WordPress plugin for medical practices to automatically generate and manage schema markup, improving local SEO and search appearance.

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-brightgreen.svg)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-red.svg)

## 🎯 Features

### Core Functionality
- **Automated Site Analysis** - Scans your WordPress site to extract practice information
- **Multiple Location Support** - Add unlimited practice locations with individual details
- **Rich Snippet Preview** - See how your practice appears in Google search results
- **Schema Validation** - Built-in validation tool to ensure proper markup
- **Social Media Integration** - Link all your social profiles for better credibility

### Schema Types Supported
- Medical Business / Medical Clinic
- Individual Physician profiles
- Dental Practices
- Hospitals
- Medical Services & Procedures

### Medical-Specific Fields
- ✅ Accepting new patients status
- 💊 Insurance providers accepted
- 🕐 Business hours (per location)
- 🏥 Medical specialties
- 🌐 Languages spoken
- ⭐ Aggregate ratings & reviews
- 📱 Social media profiles

## 🚀 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the downloaded ZIP file
5. Click "Install Now"
6. Activate the plugin

### Method 2: Manual Installation
1. Download and extract the plugin files
2. Upload the `medical-schema-markup` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### Method 3: Clone from GitHub
```bash
cd wp-content/plugins/
git clone https://github.com/mprattmd/medical-schema-markup.git
```

## 📖 Usage

### Quick Start (5 Minutes)
1. **Navigate** to WordPress Admin → Medical Schema
2. **Click** "Analyze Site Content" - Auto-populates fields from your website
3. **Review & Edit** the suggested data
4. **Add Locations** - Click "Add Location" for each office
5. **Add Physicians** - Profile each doctor with specialties and bios
6. **Add Services** - List all medical services offered
7. **Add Social Profiles** - Link Facebook, Twitter, Instagram, etc.
8. **Validate** - Click "Validate Schema" to check for issues
9. **Preview** - Click "Preview Rich Snippet" to see Google appearance
10. **Save** - Click "Save Schema Data" to activate on your site

### Advanced Features

#### Multiple Locations
Each location can have:
- Unique address and contact info
- Individual business hours
- Geo-coordinates (lat/long)
- Location-specific details

#### Social Media Integration
Supported platforms:
- Facebook
- Twitter (X)
- Instagram
- LinkedIn
- YouTube
- Yelp
- Healthgrades
- Zocdoc
- Custom URLs

## 🏗️ File Structure

```
medical-schema-markup/
├── medical-schema-markup.php  # Main plugin file (PHP)
├── admin-script.js            # Admin interface JavaScript
└── README.md                  # This file
```

## 🔧 Technical Details

### Requirements
- WordPress 5.0 or higher
- PHP 7.0 or higher
- Modern web browser with JavaScript enabled

### Schema.org Properties Used
- `@type`: MedicalBusiness, Physician, MedicalClinic, etc.
- `name`, `description`, `url`, `logo`, `image`
- `address`: PostalAddress with full details
- `telephone`, `email`
- `openingHours`: Structured business hours
- `aggregateRating`: Star ratings
- `employee`: Physician profiles
- `hasOfferCatalog`: Medical services
- `sameAs`: Social media profiles
- `geo`: GeoCoordinates for precise location

## 📝 Changelog

### Version 2.0.0 (Current)
- ✨ Added multiple location support
- ✨ Added rich snippet preview
- ✨ Added schema validation tool
- ✨ Added social media profile integration
- ✨ Improved site analysis algorithm
- ✨ Added export/import functionality

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🔗 Useful Links

- [Schema.org Medical Documentation](https://schema.org/MedicalBusiness)
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Google Search Central](https://developers.google.com/search/docs/advanced/structured-data/intro-structured-data)

---

**Made with ❤️ for medical practices**
