# ApostropheEnt Core

Dependency-free WordPress core plugin for the Apostrophe Entertainment headless website.

## Architecture

- WordPress: headless CMS at `api.apostropheent.com`
- Next.js: public frontend
- Custom REST API: `/wp-json/apostrophe/v1/*`

## Included

- English / French content support
- Services, Fields and Projects content models
- Native WordPress media support
- Global site settings
- Custom REST API
- Revalidation webhook support
- Basic REST and WordPress hardening

## API

- `GET /wp-json/apostrophe/v1/health`
- `GET /wp-json/apostrophe/v1/site?lang=en`
- `GET /wp-json/apostrophe/v1/site?lang=fr`
- `GET /wp-json/apostrophe/v1/services?lang=en`
- `GET /wp-json/apostrophe/v1/fields?lang=en`
- `GET /wp-json/apostrophe/v1/projects?lang=en`

## Development

The plugin should remain self-contained for the Apostrophe data model. Third-party integrations such as Polylang or Rank Math may be supported as optional integrations, but the frontend data contract must not depend on them unless explicitly decided.
