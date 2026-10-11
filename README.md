# Power and Battery Tools

Free tools for powering your projects, by [TheMakersWorkbench.com](https://www.themakersworkbench.com). One page, five tools, no build step and no dependencies. Type values with prefixes like `4.7k` or `470u`, and the answers update as you type.

![Power and Battery Tools](wiki/images/overview.png)

## The tools

| Tab | What it does |
| --- | --- |
| Regulator Heat | Heat in a linear regulator, junction temperature, and the heatsink needed. |
| Capacitor Discharge | Discharge time through a resistor, or the bleeder resistor for a set time, with the stored energy. |
| Battery Pack | Voltage, capacity, and energy of a series and parallel pack for common chemistries. |
| Battery Life Pro | Run time from active and sleep currents, counting a linear or switching regulator. |
| Filter Capacitor | The smoothing capacitor for a rectifier, or the ripple a given capacitor leaves. |

## Run it

Open `index.html` in a browser. To serve it locally: `python -m http.server` in this folder.

Run the tests (Node 18 or newer, no packages needed):

```
node --test tests/power-logic.test.js
```

## Embed it

Load `pwr.css`, `js/power-logic.js`, and `js/power-app.js`, then add:

```html
<div class="tmwb-pwr" id="tmwb-pwr" data-heading="h2"></div>
```

All styles are scoped under `.tmwb-pwr` and sized in pixels.

### Drupal module

`drupal/tmwb_power_battery` is a Drupal 10/11 module (TMWB Power and Battery Tools) that embeds the tool from a text-format filter. See its README.

## Files

- `index.html`, `pwr.css`: the page and styles
- `js/power-logic.js`: all the math, no DOM access, tested in Node
- `js/power-app.js`: the interface
- `tests/`: unit tests
- `drupal/tmwb_power_battery/`: Drupal module (run `python scripts/sync_drupal_module.py` after changing the tool)
- `SOURCES.md`: where each formula and data table comes from

## Accuracy

The formulas are the usual textbook and datasheet ones and describe ideal parts. Thermal figures and cell voltages vary by part. Confirm them in the datasheet, and never rely on a calculation alone around mains, high-voltage capacitors, or lithium batteries.

## License

[Apache License 2.0](LICENSE). Free for commercial and non-commercial use. You may modify it and add to it, as long as the license, the [NOTICE](NOTICE), and the "Developed and Maintained by TheMakersWorkbench.com" attribution stay intact.
