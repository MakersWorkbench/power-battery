# Embedding

## Any web page

Copy `pwr.css`, `js/power-logic.js`, and `js/power-app.js` from the repository, then add:

```html
<link rel="stylesheet" href="pwr.css">
<div class="tmwb-pwr" id="tmwb-pwr" data-heading="h2"></div>
<script src="js/power-logic.js"></script>
<script src="js/power-app.js"></script>
```

`data-heading` sets the heading level of the tool's title (`h1` to `h6`, default `h1`). Everything is scoped under `.tmwb-pwr` and sized in pixels. Only one copy can be on a page.

## Drupal

The repository includes `drupal/tmwb_power_battery`, a Drupal 10/11 module.

1. Copy the folder to `web/modules/custom/`.
2. Enable **TMWB Power and Battery Tools** (`drush en tmwb_power_battery`).
3. Edit your text format (Configuration, Text formats and editors) and turn on **TMWB Power and Battery Tools Embed**. The format must allow `div` elements with a `class` attribute.
4. In the body, switch to source mode and add:

```html
<div class="tmwb-power-battery" data-heading="h2"></div>
```

The filter swaps the placeholder for the tool, loads its CSS and JavaScript, adds a server-rendered description for search engines, and optionally adds Schema.org `WebApplication` structured data.
