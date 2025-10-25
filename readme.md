# StellarPay

<a href="https://links.stellarwp.com/stellarwp"><img alt="StellarPay" src="https://stellarwp.com/wp-content/uploads/2024/08/stellarpay-github-image.jpg"/></a>

<br />
<div align="center"><strong>The smart way to power your store's payments.</strong></div>
<div align="center">Lightweight, customizable and beautiful by default.</div>
<br />
<div align="center">
<a href="https://links.stellarwp.com/stellarpay">Website</a>
<span> · </span>
<a href="https://links.stellarwp.com/stellarpay/docs">Documentation</a>
<span> · </span>
<a href="https://links.stellarwp.com/twitter">X/Twitter</a>
</div>

<br />

## Overview

StellarPay is a next-generation payment gateway that allows you to accept one-time and subscription (coming soon!)
payments from your customers. It is a WordPress plugin that is built with the latest technologies and is designed to be
highly customizable and extendable.

## Features

-   🚀 **Built in React**
-   ⚙️ **Settings UI and Data Store** - _Customizable UI fields with Data Store_
-   🏗️ **Blocks Development Workflow**
-   📂 **PHP Autoloader**
-   ✨ **TailwindCSS**

## Getting Started

### Documentation

**[Click here for the StellarPay documentation](https://stellarwp.com/docs/stellarpay/)**. The documentation is in a
constant state of improvement. If
you notice an issue, have a questions, or would like to help, please feel free to reach out to us
at [#stellarpay-team](https://lw.slack.com/archives/C07HSF36934) on the StellarWP
Slack.

### Installation

The following steps will guide you through setting up a development environment for StellarPay.

#### Step 1

Set up a new LocalWP environment with the following settings:

1. **Web Server:** Nginx
2. **PHP Version:** 8.2.10
3. **Database:** MySQL 8.0.16
4. **Site Domain:** stellarpay.test

#### Step 2

Make sure you have PHP, Composer and NodeJS installed on your machine. And then run the following bash command -

```sh
bun run install-scripts && bun run build
```

#### Step 3

Familiarize yourself with some of the commands and start developing ⚡️

```bash
bun run dev:scripts
```

## Development

### Available Commands

The following commands are available to help you during development:

#### Installation & Building

-   `bun run install-scripts` - Install production dependencies (Composer + npm packages)
-   `bun run dev:install-scripts` - Install all dependencies including dev dependencies
-   `bun run build` - Build all assets for production
-   `bun run dev:scripts` - Start development server with hot reloading

#### Code Quality

-   `bun run format` - Format all code (JS, CSS, PHP)
-   `bun run lint:js` - Lint JavaScript files
-   `bun run lint:css` - Lint CSS/SCSS files

#### Testing

-   `bun run test:e2e` - Run end-to-end tests
-   `bun run test:e2e-debug` - Run end-to-end tests in debug mode
-   `bun run test:php` - Run PHP unit tests

#### WordPress Environment

-   `bun run env:start` - Start WordPress development environment
-   `bun run env:stop` - Stop WordPress development environment
-   `bun run env:destroy` - Remove WordPress development environment completely

#### Release & QA

-   `bun run generate:zip` - Generate plugin zip file for deployment
-   `bun run generate:zip-local` - Generate plugin zip file locally

## Contributing

### How to Contribute

Thank you for thinking about contributing to StellarPay! This project is part of the StellarWP organization and is open
source for everyone to use and contribute to. We are excited to see what you can bring to the table.

### UI Text Guidelines

#### Use of Sentence Case

To ensure consistency and readability across the user interface (UI), all text elements within the plugin's UI should
follow **Sentence case**.

**What is Sentence case?**

Sentence case is a style of writing where only the first letter of the first word in a sentence is capitalized, along
with any proper nouns. This approach is generally used to make text more readable and approachable. Here's an example:

-   **Sentence case**: "This is an example of sentence case."
-   **Title Case**: "This Is an Example of Title Case."

**Why use Sentence case?**

-   **Readability**: Sentence case is easier to read, especially for longer sentences or descriptive text.
-   **Consistency**: Maintaining Sentence case throughout the UI creates a uniform and professional appearance.
-   **User-Friendly**: This style aligns with most modern UI/UX best practices, enhancing the overall user experience.

When adding or editing any UI text, please ensure that Sentence case is applied consistently across all elements.

### Release Process

#### Asset/Readme Updates

Updates to readme.txt and assets (.wordpress-org directory) are deployed via GitHub Actions:

-   Trigger: Manual workflow dispatch from main branch
-   Updates WordPress.org assets without modifying plugin files
-   Run workflow after pushing changes to readme.txt or screenshots

#### Plugin Releases

Full plugin deployments happen automatically when:

-   A new GitHub release is published
-   The workflow generates a ZIP, deploys to WordPress.org, and attaches the ZIP to the release

Required repository secrets:

-   `SVN_USERNAME`: WordPress.org SVN username
-   `SVN_PASSWORD`: WordPress.org SVN password

## Security

Need to report a security vulnerability? Email us at [security@stellarwp.com](mailto:security@stellarwp.com).

## License

StellarPay is licensed under [GNU General Public License v2 (or later)](./license.txt).
