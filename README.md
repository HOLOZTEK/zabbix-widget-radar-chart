# zabbix-widget-radar-chart

English | [日本語](README.ja.md)

## Overview

Radar Chart is a Zabbix dashboard widget for comparing numeric item values across one or many hosts. It can receive host-group context from Tree Navigator, Topology Navigator, or another compatible widget, and renders three to eight selected metrics as a radar chart for each host.

<a href="screenshots/radar-chart-dashboard-multiple.png" target="_blank"><img src="screenshots/radar-chart-dashboard-multiple.png" width="750" alt="Radar Chart comparing multiple hosts" /></a>

## Why Radar Chart?

A dashboard can show each metric separately, but comparing a host's balance across CPU, memory, storage, latency, and other normalized indicators takes time. Radar Chart places the selected metrics on a common scale so operators can spot differences between hosts and investigate the outliers.

It is suited to dashboards that need a compact comparison of multiple hosts while retaining the ability to inspect the real collected values.

## Features

<table>
  <tr><th align="left" nowrap>Function</th><th align="left">Description</th></tr>
  <tr><td nowrap>Multi-host grid</td><td>Displays up to a 6 × 6 grid of host radar charts, with stable cell sizes across pages.</td></tr>
  <tr><td nowrap>Flexible host selection</td><td>Select hosts by host group, individual host, or wildcard host pattern. A host group scopes the pattern when both are configured.</td></tr>
  <tr><td nowrap>Widget integration</td><td>Receives host groups and hosts from Tree Navigator, Topology Navigator, or another compatible widget.</td></tr>
  <tr><td nowrap>Per-item aggregation</td><td>Uses Latest, Max, Min, or Avg. Max, Min, and Avg use History for periods under two hours and prefer Trends for longer periods, with a History fallback.</td></tr>
  <tr><td nowrap>Per-item scale</td><td>Normalizes each value against its configured minimum and maximum. Values outside the range are clipped; negative ranges are supported.</td></tr>
  <tr><td nowrap>Axis direction</td><td>Draws each axis normally or reversed. Reversal affects the plotted position only; tooltips always show the real value.</td></tr>
  <tr><td nowrap>Operational feedback</td><td>Shows missing-data axis labels, detailed hover tooltips, all-missing host filtering, pagination, and aggregation warnings.</td></tr>
</table>

<table>
  <tr>
    <td align="center" valign="top" width="50%">
      <strong>Single-host detail</strong><br>
      <a href="screenshots/radar-chart-dashboard-single.png" target="_blank"><img src="screenshots/radar-chart-dashboard-single.png" width="330" alt="Single-host Radar Chart" /></a>
    </td>
    <td align="center" valign="top" width="50%">
      <strong>Multi-host comparison</strong><br>
      <a href="screenshots/radar-chart-dashboard-multiple.png" target="_blank"><img src="screenshots/radar-chart-dashboard-multiple.png" width="330" alt="Multi-host Radar Chart" /></a>
    </td>
  </tr>
</table>

## Settings

<table>
  <tr><th align="left" nowrap>Setting</th><th align="left">Description</th></tr>
  <tr><td nowrap>Host groups / Hosts / Host patterns</td><td>Choose the target hosts directly or receive them from another widget.</td></tr>
  <tr><td nowrap>Items</td><td>Configure three to eight numeric items. Each item has a label, minimum, maximum, direction, and aggregation method.</td></tr>
  <tr><td nowrap>Grid</td><td>Set the number of columns and rows from 1 to 6.</td></tr>
  <tr><td nowrap>Style</td><td>Control line, point, fill, chart, and title colors and sizes.</td></tr>
  <tr><td nowrap>Data limits</td><td>Set the History row limit and the warning threshold for Latest fetches.</td></tr>
  <tr><td nowrap>Time period</td><td>Uses the dashboard time period for aggregation.</td></tr>
</table>

<a href="screenshots/radar-chart-settings-en.png" target="_blank"><img src="screenshots/radar-chart-settings-en.png" width="620" alt="Radar Chart widget settings in English" /></a>

### Per-item configuration

Set a range and aggregation method for every axis. A minimum must be lower than its maximum; an axis can be reversed without changing the collected value shown in the tooltip.

<a href="screenshots/radar-chart-item-settings-en.png" target="_blank"><img src="screenshots/radar-chart-item-settings-en.png" width="620" alt="Radar Chart item settings in English" /></a>

## Dashboard Integration

1. Add Tree Navigator, Topology Navigator, or another widget that broadcasts host groups or hosts.
2. Add Radar Chart to the same dashboard page.
3. In Radar Chart settings, connect Host groups and/or Hosts to the source widget.
4. Select a group or host in the source widget to update the chart scope.

<a href="screenshots/radar-chart-navigator-integration.png" target="_blank"><img src="screenshots/radar-chart-navigator-integration.png" width="750" alt="Radar Chart integrated with a host-group navigator" /></a>

## Details and edge cases

<table>
  <tr>
    <td align="center" valign="top" width="50%">
      <strong>Tooltip</strong><br>
      <img src="screenshots/radar-chart-tooltip.png" width="330" alt="Radar Chart tooltip showing the real value" />
    </td>
    <td align="center" valign="top" width="50%">
      <strong>Missing data and pagination</strong><br>
      <img src="screenshots/radar-chart-missing-data-pagination.png" width="330" alt="Radar Chart missing-data and pagination controls" />
    </td>
  </tr>
</table>

- Only float and unsigned-integer items are available.
- Latest retrieves the most recent History value inside the selected period; it is not the current last value.
- A History row limit can make an aggregation incomplete. The widget shows a warning when that limit is reached.
- Large numbers of hosts and Latest axes can increase API calls and dashboard rendering time.

## Requirements

- Zabbix 7.0 or later
- PHP 8.1 or later
- Rocky Linux 9 or 10 for the RPM package; other RHEL-compatible distributions are expected to work with compatible PHP and Zabbix packages
- A browser supported by the Zabbix frontend

## Installation

### Install from RPM

```bash
dnf install ./zabbix-widget-radar-chart-<version>.noarch.rpm
```

The RPM requires PHP or php-common, and php-fpm, version 8.1 or later.

### Install from DEB

```bash
apt install ./zabbix-widget-radar-chart_<version>_all.deb
```

The Debian package requires PHP 8.1 or later.

### Install from Source

Copy the module into the frontend module directory, then scan and enable it from **Administration → Modules**.

```bash
# Zabbix 7.x
cp -r zabbix-widget-radar-chart /usr/share/zabbix/modules/holoztek_radar_chart

# Zabbix 8.x
cp -r zabbix-widget-radar-chart /usr/share/zabbix/ui/modules/holoztek_radar_chart
```

## Upgrading from the legacy module ID

The module ID changed from `radar-chart` to `holoztek_radar_chart` in v1.0.1 to avoid conflicts with other vendors. When upgrading from v1.0.0 or earlier, rescan and enable the new module, retire the old module after verifying its ownership, and update existing dashboard widget types from `radar-chart` to `holoztek_radar_chart`. Existing field settings and references are preserved.

## Repository Layout

Runtime module files are in the repository root and under `actions/`, `assets/`, `includes/`, `locale/`, and `views/`. Packaging is under `packaging/rpm/` and `debian/`; screenshots are under `screenshots/`; consistency and normalization tests are under `scripts/`.

## Maintainer

Developed and maintained by HOLOZTEK.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE). It bundles Apache ECharts under the Apache License 2.0; see [NOTICE](NOTICE) and [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
