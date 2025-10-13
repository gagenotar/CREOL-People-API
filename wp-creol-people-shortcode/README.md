# CREOL People Shortcode

Drop the `wp-creol-people-shortcode` folder into your `wp-content/plugins/` directory and activate the plugin.

Usage:

- Basic: [creol_people]
- Filter by group (use attribute names accepted by the plugin):
	- Single group: [creol_people GrpName1="Research Scientists"]
	- Two groups: [creol_people GrpName1="Research Scientists" GrpName2="Faculty"]
	- Lowercase keys also work: [creol_people grpname1="Research Scientists" grpname2="Faculty"]
- Limit results: [creol_people GrpName1="Research Scientists" limit="10"]
- Override cache TTL (seconds): [creol_people GrpName1="Research Scientists" cache_ttl="60"]
- Display modes:
	- Card (default): shows image + info. Example: [creol_people GrpName1="Research Scientists" display="card"]
	- Grid: compact rows without images. Example: [creol_people GrpName1="Research Scientists" display="grid"]
	- The `display` attribute supports multiple named modes (not boolean); future modes can be added.
 - Columns:
	 - Control number of columns across display modes using `columns` (integer 1-6).
	 - Example (3 columns): [creol_people GrpName1="Research Scientists" columns="3"]
	 - Example (grid mode, 2 columns): [creol_people GrpName1="Research Scientists" display="grid" columns="2"]

Notes:
- The plugin caches API responses using WordPress transients. Default TTL is 300 seconds.
- The plugin sanitizes and escapes output but trusts the API for image URLs.
- Styling is minimal; override via theme CSS as needed.
