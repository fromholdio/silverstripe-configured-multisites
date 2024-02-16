# SilverStripe Multisites

## v8.0.0

Requires Silverstripe v5.x.

This major version departs from the previous approach of [symbiote/silverstripe-multisites](https://github.com/symbiote/silverstripe-multisites), which handles
hosts and aliases and themes and so forth via the CMS interface. Instead, this version requires this to be defined in .env and config yml.

This allows, for example, deployment to a development environment, without requiring changes in the database to site domain names. These are set via dev identifiers in .env,
and are updated upon running dev/build in the new environment.

It also reflects the strong opinion that those types of values should not be managed in the CMS, they are not relevant to content editors. Rather, they are managed by
developers/devops.

This version also includes many fixes and improvements for Silverstripe 5.x compatibility.

Documentation to come, and likely a vendor/package name change. In the meantime, see `.env.example` and `app-config.yml.example` for how to define your sites.

The data model has not changed, making migration possible.

## Migration

- Add the module to your project
- Setup the configuration per the examples in your project .env and _config
- Ensure that your existing sites have DevID values that match your site-keys in .env and _config
- Run `dev/build` which will match existing site DevID to the site definitions in config, and update Host, Aliases, Theme, and so forth accordingly.
- Note: existing sites that have no matching DevID (site-key) definition in your config will be deleted.
