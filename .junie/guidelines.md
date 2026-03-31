# Project Development Guidelines

This project is a Drupal-based site (`roundybrookfarm`) using Lando for local development and a custom theme (`surface`) built with Vite and Storybook.

## Related Documentation
This project uses multiple documentation files for AI context:
- **`.junie/guidelines.md` (this file)**: Primary operational guide for Junie. Contains build commands, testing procedures, and high-level project rules.
- **`CLAUDE.md` (root)**: High-level repository structure overview.
- **`public_html/themes/custom/surface/CLAUDE.md`**: Deep-dive technical reference for the `surface` theme, including Twig rules, patterns, and content models. Refer to this for any frontend or schema-related work.

## Build and Configuration

### Local Environment (Lando)
The project uses Lando. To start the local environment:
```bash
# Start Lando services
lando start

# View Lando info (URLs, database credentials)
lando info
```

### Dependency Management
- **PHP**: Managed by Composer. The Drupal root is located in `public_html/`.
  ```bash
  composer install
  ```
- **Frontend (Theme)**: The `surface` theme is located in `public_html/themes/custom/surface/`.
  ```bash
  cd public_html/themes/custom/surface/
  npm install
  npm run build  # Full production build
  npm run watch  # Development mode (Vite + Storybook)
  ```

### Key Directories
- `public_html/`: Drupal web root.
- `public_html/modules/custom/`: Custom Drupal modules (e.g., `trail_mapper`, `external_pg`).
- `public_html/themes/custom/surface/`: Primary custom theme.
- `config/`: Drupal configuration exports.

---

## Testing Information

### PHP Testing
The standard for Drupal is **PHPUnit**. While not pre-configured in this repository's root, unit tests can be added to custom modules.

#### Adding a New Test
1. Create a class in `src/` (e.g., `src/Utility/Math.php`).
2. Create a corresponding test in a `tests/` directory.

#### Simple Test Example
For demonstration, here is a basic unit test process:

**1. Create a Utility Class:**
```php
// public_html/modules/custom/trail_mapper/src/Utility/UnitConverter.php
namespace Drupal\trail_mapper\Utility;

class UnitConverter {
  public static function feetToMeters(float $feet): float {
    return $feet * 0.3048;
  }
}
```

**2. Create a Test Script:**
```php
// tests/Unit/UnitConverterTest.php
require_once __DIR__ . '/../../public_html/modules/custom/trail_mapper/src/Utility/UnitConverter.php';
use Drupal\trail_mapper\Utility\UnitConverter;

$result = UnitConverter::feetToMeters(10);
assert(abs($result - 3.048) < 0.0001);
echo "Test Passed!\n";
```

**3. Run the Test:**
```bash
php tests/Unit/UnitConverterTest.php
```

### Frontend Testing
Storybook is used for visual testing and component development. Run `npm run watch` in the theme directory to access Storybook at `http://localhost:6007`.

---

## Additional Development Information

### Theme Architecture (Surface)
- **Methodology**: Modified Atomic Design.
  - `@base`: Global styles.
  - `@elements`: Smallest units (no inclusions).
  - `@components`: May include elements.
  - `@collections`: Composed of components and elements.
  - `@layouts`: Drupal region wrappers.
  - `@pages`: Full page Storybook demos.
- **Tooling**: Vite for bundling, Twig for templating, BEM for CSS naming.

### Database Integration
The `external_pg` module provides a service to connect to an external PostgreSQL database. Connection details are currently defined in `ExternalPgService.php` but should ideally be moved to `settings.php`.

### Code Style
- Follow [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards).
- Use BEM for CSS in the `surface` theme.
- Javascript should follow ES6+ standards.
