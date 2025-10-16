# CREOL People API

A WordPress plugin that displays people in a customizable grid layout using data from the CREOL API.

## Description

The CREOL People API plugin provides a simple shortcode to display staff, faculty, or other personnel from the CREOL (College of Optics and Photonics) database. The plugin fetches data from the CREOL API and displays it in a responsive grid layout with customizable options.

## Features

- **Flexible Display Modes**: Choose between card view (with images) or compact grid view
- **Responsive Design**: Automatically adapts to different screen sizes
- **Smart Caching**: Reduces API calls with configurable transient caching
- **Group Filtering**: Filter people by one or two group names
- **Highly Customizable**: Control columns, limits, and display modes via shortcode attributes

## Installation

### From GitHub

1. Download the plugin files or clone this repository:
   ```bash
   git clone https://github.com/UCF/CREOL-People-API.git
   ```

2. Upload the `CREOL-People-API` folder to your `/wp-content/plugins/` directory

3. Activate the plugin through the 'Plugins' menu in WordPress

### Manual Installation

1. Download the ZIP file from the releases page
2. In WordPress admin, go to Plugins > Add New > Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

## Usage

### Basic Shortcode

Display all people from a specific group:

```
[creol_people grpname1="Faculty"]
```

### Advanced Examples

**Display with two group filters:**
```
[creol_people grpname1="Faculty" grpname2="Optics"]
```

**Limit the number of results:**
```
[creol_people grpname1="Staff" limit="6"]
```

**Use compact grid mode (no images):**
```
[creol_people grpname1="Faculty" display="grid"]
```

**Custom column layout:**
```
[creol_people grpname1="Faculty" columns="4"]
```

**Combine multiple options:**
```
[creol_people grpname1="Faculty" grpname2="Research" display="card" columns="3" limit="12"]
```

**Custom cache duration (in seconds):**
```
[creol_people grpname1="Staff" cache_ttl="600"]
```

## Shortcode Attributes

| Attribute | Description | Default | Values |
|-----------|-------------|---------|--------|
| `grpname1` | Primary group name to filter by | _(empty)_ | Any group name |
| `grpname2` | Secondary group name to filter by | _(empty)_ | Any group name |
| `limit` | Maximum number of people to display | `0` (all) | Any positive integer |
| `display` | Display mode | `card` | `card`, `grid` |
| `columns` | Number of grid columns | `3` | `1` to `6` |
| `cache_ttl` | Cache duration in seconds | `300` (5 min) | Any positive integer |

### Attribute Aliases

For convenience, the following aliases are supported (case-insensitive):
- `grpname1`, `grp1`, `GrpName1` (all equivalent)
- `grpname2`, `grp2`, `GrpName2` (all equivalent)

## Display Modes

### Card Mode (default)
Shows a full card for each person including:
- Profile image
- Name
- Position/Title
- Email address
- Phone number
- Room number

### Grid Mode
Displays a compact grid without images, showing only:
- Name
- Position/Title
- Email address
- Phone number
- Room number

## Styling

The plugin includes default styles that can be overridden in your theme. Key CSS classes:

- `.creol-people-grid` - Main container
- `.creol-people-grid-mode` - Applied when using grid display mode
- `.creol-person-card` - Individual person card
- `.creol-person-image` - Image container
- `.creol-person-body` - Text content container
- `.creol-person-name` - Person's name
- `.creol-person-position` - Job title/position
- `.creol-person-email` - Email address
- `.creol-person-phone` - Phone number
- `.creol-person-room` - Room number

### Custom CSS Example

```css
/* Override card styles in your theme */
.creol-person-card {
    border: 2px solid #000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.creol-person-name {
    color: #003366;
    font-size: 1.2rem;
}
```

## Caching

The plugin uses WordPress transients to cache API responses, reducing load times and API calls. The default cache duration is 5 minutes (300 seconds), but can be customized per shortcode using the `cache_ttl` attribute.

To clear the cache:
- Transients are automatically cleared after the TTL expires
- Manually delete transients using a plugin like "Transients Manager"
- Use WP-CLI: `wp transient delete --all`

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Active internet connection to fetch data from CREOL API

## Support

For bugs, feature requests, or contributions, please visit the [GitHub repository](https://github.com/UCF/CREOL-People-API).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on how to contribute to this plugin.

## License

This plugin is licensed under GPL3. See the main plugin file for full license information.

## Changelog

### 1.0.0
- Initial release
- Shortcode implementation with group filtering
- Card and grid display modes
- Responsive grid layout
- Transient caching support
- Customizable columns and limits

## Credits

Developed by UCF Web Communications for the University of Central Florida.
