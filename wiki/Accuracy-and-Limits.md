# Accuracy and Limits


The tools use standard textbook and datasheet formulas. [SOURCES.md](https://github.com/MakersWorkbench/power-battery/blob/main/SOURCES.md) lists where each one comes from.

- **Thermal numbers vary.** The junction-to-ambient figure depends on the package, the copper under it, airflow, and the enclosure. Use the datasheet value and keep a margin.
- **Cell voltages are typical.** Check your cell's datasheet for charge and cut-off voltages, and use a protection circuit.
- **Batteries.** Rated capacity is measured at a low rate. Cold, high current, and cut-off voltage reduce what you get, and self-discharge is not modeled.
- **Safety.** Never rely on a bleeder resistor alone. Measure capacitors before touching a circuit, and treat mains and lithium cells with care.
