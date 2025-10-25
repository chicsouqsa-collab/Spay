## Architecture Overview

This document provides an overview of the plugin's architecture, including the directory structure.
It also includes a brief description about directory structure.

### `./build` Directory
The `./build` directory serves as the destination for compiled resources. It contains JavaScript and SCSS files that have been processed and are ready to be loaded on the client side. This ensures that the final, optimized versions of these resources are used in the production environment.

### `./blocks` Directory
The `./blocks` directory is dedicated to housing Gutenberg blocks. Each block is organized within its own subdirectory, named after the block itself, encapsulating all the necessary files for that block's functionality. For a deeper understanding of [how blocks are structured](https://developer.wordpress.org/block-editor/getting-started/fundamentals/file-structure-of-a-block/), refer to the Gutenberg Handbook.

Structure example:

```plaintext
./blocks
├── block1
├── block2
```

### `./src` Directory
The `./src` directory is the heart of the source code, containing PHP, JavaScript, and SCSS files essential for building the plugin. This directory is organized by domain, with each domain having its resource directory for domain-specific assets.

Additionally, there's a common directory within ./src, which includes shared PHP classes (like Models and Repositories) and common resources (JavaScript, SCSS files) that are used across multiple domains.

Structure overview:

```plaintext
./src
├── domain1
│   ├── Models
│   ├── Repositories
│   ├── Controllers
│   ├── resources
├── domain2
├── common
│   ├── resources
```

## Existing Domains

This section provides an overview of the existing domains and their respective directories.
It also includes a brief description of the functionality that each domain encapsulates.
It is important to note that the plugin's architecture is designed to be modular, and new domains can be added as needed.

### `./src/PluginSetup`

The `./src/PluginSetup` includes classes responsible for environment compatibility checks, and enhancing the plugin's presentation and management in the WordPress admin area.

### `./src/AdminDashboard`

The `./src/AdminDashboard` directory contains the source code for the plugin's admin dashboard. This includes the PHP classes, JavaScript, and SCSS files that are used to build the dashboard.

**Dashboard React App**
Dashboard React App is a single-page application (SPA) that is used to build the admin dashboard. It is built using React and Webpack, and it is located in the `./src/AdminDashboard/resources/App` directory.
Each page in the dashboard is a separate React component, and the main entry point for the React app is `./src/AdminDashboard/resources/App/index.js`.
Pages are organized in the `./src/AdminDashboard/resources/App/pages` directory.
Routing is handled by the `react-router-dom` package, and the routes are defined in `./src/AdminDashboard/resources/App/routes.js`.
Helper functions are located in the `./src/AdminDashboard/resources/App/utils` directory.


### `./src/PaymentGateways`

The `./src/PaymentGateways` directory contains the source code for the plugin's support payment gateways.
Each payment gateway is organized in its own subdirectory, encapsulating all the necessary files for that payment gateway's functionality.

### `./src/Ingegrations`

This directory contains the source code for the plugin's integrations with third-party services.
Each integration is organized in its own subdirectory, encapsulating all the necessary files for that integration's functionality.

### `./src/RestApi`

This directory contains the source code for the plugin's REST API.
Endpoints are organized in the `./src/RestApi/Endpoints` directory.

### `./src/Common`

The `./src/Common` directory contains shared logic that are used across multiple domains.
This includes shared PHP classes (like Models and Repositories) and common resources (JavaScript, SCSS files).
