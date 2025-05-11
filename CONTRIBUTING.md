# Contributing to DigiCommerce

Thank you for considering contributing to DigiCommerce! We appreciate your interest in helping make our digital product & service ecommerce platform even better.

This document outlines the process for contributing to DigiCommerce and helps ensure a smooth collaboration experience.

## How to Contribute

### Reporting Bugs

If you've found a bug in DigiCommerce, we'd like to hear about it:

1. **Check Existing Issues** - Search the [existing issues](https://github.com/DigiHold/DigiCommerce/issues) to see if someone else has already reported the problem.

2. **Create a New Issue** - If your bug hasn't been reported yet, [create a new issue](https://github.com/DigiHold/DigiCommerce/issues/new/choose) with the following information:

    - A clear, descriptive title
    - Steps to reproduce the bug
    - Expected behavior
    - Actual behavior
    - WordPress version, PHP version, and other relevant environment details
    - Screenshots if applicable

3. **Isolate the Problem** - If possible, create a reduced test case that demonstrates the bug with minimal code.

### Suggesting Features

Have an idea for improving DigiCommerce?

1. **Check Existing Issues** - Search [existing issues](https://github.com/DigiHold/DigiCommerce/issues) labeled as "enhancement" to see if your idea has already been suggested.

2. **Create a Feature Request** - If your idea is new, [create a feature request](https://github.com/DigiHold/DigiCommerce/issues/new/choose) with:
    - A clear, descriptive title
    - Detailed description of the proposed functionality
    - Any relevant mockups or examples
    - Explanation of why this feature would be valuable to DigiCommerce users

### Pull Requests

We welcome code contributions through pull requests:

1. **Fork the Repository** - Create your own fork of the DigiCommerce repository.

2. **Create a Branch** - Create a feature branch for your changes:

```shell
git checkout -b feature/your-feature-name
```

or for bugfixes:

```shell
git checkout -b fix/your-bugfix-name
```

3. **Make Your Changes** - Implement your changes following our coding standards.

4. **Write Tests** - Add or update tests for your changes when applicable.

5. **Submit a Pull Request** - Open a PR against the `main` branch with:

- A clear description of the changes
- Reference to any related issues
- Explanation of the implementation approach
- Any notes on testing that you performed

6. **Code Review** - The team will review your PR, suggest changes if needed, and approve when ready.

## Development Environment

### Setting Up Locally

1. **Clone the Repository**:

```shell
git clone https://github.com/YourUsername/digicommerce.git
cd digicommerce
```

2. **Install Dependencies**:

```shell
composer install
npm install
```

3. **Local WordPress Environment**:

- We recommend using [Local](https://localwp.com/) or [XAMPP](https://www.apachefriends.org/) for local WordPress development
- Alternatively, use [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) for a containerized setup

4. **Build Assets**:

```shell
npm run build
```

### Running Tests

Run PHP tests with:

```shell
composer test
```

Run JavaScript tests with:

```shell
npm test
```

## Coding Standards

DigiCommerce follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/):

### PHP

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use PHP_CodeSniffer comments for functions, classes, and methods
- Validate and sanitize input data, escape output data

### JavaScript

- Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- Use JSDoc comments for functions and methods
- Properly handle internationalization

### CSS

- Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- Use consistent naming conventions
- Ensure responsive design principles

## Questions?

If you have any questions about contributing, please:

- Join our [Facebook Community Group](https://www.facebook.com/groups/digicommerce)

Thank you for contributing to DigiCommerce!
