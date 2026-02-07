# Drupal EU Sovereignty Checklist

A Drupal module that helps keep your site **EU Digital Sovereignty** compliant by scanning rendered markup and configuration for external assets (CDNs, trackers, embeds) that may leak data outside the EU/GDPR zone.

Inspired by [Drupal Pivot](https://www.computerminds.co.uk/articles/drupal-pivot-eu) and [Drupal4Gov](https://drunomics.com/en/blog/drupal4gov-eu-2026-how-drupal-powers-european-institutions-and-national-governments-247): European institutions use Drupal because they can audit and control it. This module acts as a gatekeeper for your site's external footprint.

## What it checks

- **External assets**: CSS/JS from CDNs (e.g. `cdn.jsdelivr.net`, `fonts.googleapis.com`)
- **Third-party trackers**: Google Analytics, Meta Pixel, etc.
- **Embeds**: YouTube, Vimeo, Maps without No-Cookie modes

You configure an **allowlist** of permitted domains (e.g. `europa.eu`, `analytics.europa.eu`). The auditor flags any external URL in the scanned HTML that is not allowlisted.

## Configuration

After enabling the module, configure at **Configuration → Sovereignty** (or via `config/install/sovereignty_checklist.settings.yml`):

```yaml
allowlist_domains:
  - 'europa.eu'
  - 'analytics.europa.eu'
strict_mode: true
block_non_compliant_renders: false
```

## Architecture

The module uses a **Service Collector** pattern so other modules can register custom "Sovereignty Auditors." The main service is `sovereignty_checklist.auditor` with method `auditRenderedHtml(string $html): array` returning violation entries.

## Requirements

- Drupal 10 or 11
- PHP 8.2+

## License

MIT.
