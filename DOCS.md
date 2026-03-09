## Table of Contents

1. [Overview](#doc-docs-readme) (`docs/README.md`)
2. [Building](#doc-docs-building) (`docs/building.md`)
3. [Data Types](#doc-docs-data-types) (`docs/data-types.md`)
4. [Error Handling](#doc-docs-error-handling) (`docs/error-handling.md`)
5. [Parsing](#doc-docs-parsing) (`docs/parsing.md`)
<a id="doc-docs-readme"></a>

Parse TOML (Tom's Obvious Minimal Language) configuration files into PHP arrays or objects with full support for TOML specification 0.4.0.

**Use case:** Parse TOML configuration files for application settings, package configurations, or any structured data storage needs.

## Installation

Install via Composer:

```bash
composer require cline/toml
```

## Requirements

- PHP 8.5 or higher

## Basic Usage

### Parsing TOML Strings

Parse a TOML string directly into a PHP array:

```php
use Cline\Toml\Toml;

$toml = <<<'TOML'
title = "TOML Example"
version = "1.0.0"

[database]
host = "localhost"
port = 5432
enabled = true
TOML;

$config = Toml::parse($toml);

// Access values
echo $config['title'];              // "TOML Example"
echo $config['database']['host'];   // "localhost"
echo $config['database']['port'];   // 5432
```

### Parsing TOML Files

Load and parse TOML files:

```php
use Cline\Toml\Toml;

// Parse a configuration file
$config = Toml::parseFile('config.toml');

// Use the configuration
$dbHost = $config['database']['host'];
$dbPort = $config['database']['port'];
```

### Returning Objects Instead of Arrays

Get results as stdClass objects:

```php
use Cline\Toml\Toml;

$toml = 'name = "John Doe"';

// Parse as object
$config = Toml::parse($toml, true);

echo $config->name; // "John Doe"
```

## Quick Example

Create a `config.toml` file:

```toml
# Application Configuration
title = "My Application"
version = "2.1.0"

[server]
host = "0.0.0.0"
port = 8080
timeout = 30

[database]
driver = "postgresql"
host = "localhost"
port = 5432
name = "myapp"
username = "dbuser"
password = "secret"

[features]
debug = false
cache = true
logging = true
```

Parse and use it in your PHP application:

```php
use Cline\Toml\Toml;

class Config
{
    private array $config;

    public function __construct(string $configFile)
    {
        $this->config = Toml::parseFile($configFile);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

// Usage
$config = new Config('config.toml');

echo $config->get('title');           // "My Application"
echo $config->get('server.port');     // 8080
echo $config->get('database.host');   // "localhost"
echo $config->get('features.debug');  // false
echo $config->get('missing', 'default'); // "default"
```

## Error Handling

The parser throws exceptions when encountering invalid TOML:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\ParseException;

try {
    $config = Toml::parse('invalid toml syntax');
} catch (ParseException $e) {
    echo "Parse error: " . $e->getMessage();
    echo "Line: " . $e->getParsedLine();
}
```

## What's TOML?

TOML (Tom's Obvious Minimal Language) is a minimal configuration file format that is easy to read due to obvious semantics. It is designed to map unambiguously to a hash table and be easy for humans to read and write.

Example TOML features:

- **Simple key-value pairs:** `name = "value"`
- **Tables (sections):** `[database]`
- **Nested tables:** `[server.production]`
- **Arrays:** `ports = [8080, 8081, 8082]`
- **Inline tables:** `point = { x = 1, y = 2 }`
- **Comments:** `# This is a comment`
- **Multiple data types:** strings, integers, floats, booleans, dates

## Next Steps

- [Parsing](#doc-docs-parsing) - Learn about parsing options and result formats
- [Building](#doc-docs-building) - Create TOML programmatically with TomlBuilder
- [Data Types](#doc-docs-data-types) - Explore all supported TOML data types
- [Error Handling](#doc-docs-error-handling) - Handle parsing errors effectively

<a id="doc-docs-building"></a>

Build TOML documents programmatically using the fluent `TomlBuilder` API. Perfect for generating configuration files, exports, or dynamic TOML content.

## Basic Usage

### Creating Simple Key-Value Pairs

```php
use Cline\Toml\TomlBuilder;

$builder = new TomlBuilder();

$toml = $builder
    ->addValue('name', 'My Application')
    ->addValue('version', '1.0.0')
    ->addValue('debug', false)
    ->getTomlString();

echo $toml;
/*
name = "My Application"
version = "1.0.0"
debug = false
*/
```

### Adding Comments

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addComment('Application Configuration')
    ->addValue('name', 'MyApp')
    ->addComment('Version information')
    ->addValue('version', '2.0.0')
    ->getTomlString();

echo $toml;
/*
#Application Configuration
name = "MyApp"
#Version information
version = "2.0.0"
*/
```

### Inline Comments

Add comments to individual values:

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('port', 8080, 'HTTP port')
    ->addValue('timeout', 30, 'Connection timeout in seconds')
    ->getTomlString();

echo $toml;
/*
port = 8080 #HTTP port
timeout = 30 #Connection timeout in seconds
*/
```

## Working with Tables

### Simple Tables

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addTable('server')
    ->addValue('host', '0.0.0.0')
    ->addValue('port', 8080)
    ->addTable('database')
    ->addValue('host', 'localhost')
    ->addValue('port', 5432)
    ->getTomlString();

echo $toml;
/*
[server]
host = "0.0.0.0"
port = 8080

[database]
host = "localhost"
port = 5432
*/
```

### Nested Tables

Use dot notation for nested tables:

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addTable('application')
    ->addValue('name', 'MyApp')
    ->addTable('application.server')
    ->addValue('host', 'localhost')
    ->addValue('port', 8080)
    ->addTable('application.server.ssl')
    ->addValue('enabled', true)
    ->addValue('cert', '/path/to/cert.pem')
    ->getTomlString();

echo $toml;
/*
[application]
name = "MyApp"

[application.server]
host = "localhost"
port = 8080

[application.server.ssl]
enabled = true
cert = "/path/to/cert.pem"
*/
```

## Array of Tables

Create array of tables with `addArrayOfTable()`:

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addArrayOfTable('products')
    ->addValue('name', 'Hammer')
    ->addValue('sku', 738594937)
    ->addArrayOfTable('products')
    ->addValue('name', 'Nail')
    ->addValue('sku', 284758393)
    ->getTomlString();

echo $toml;
/*
[[products]]
name = "Hammer"
sku = 738594937

[[products]]
name = "Nail"
sku = 284758393
*/
```

### Nested Array of Tables

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addArrayOfTable('fruit')
    ->addValue('name', 'apple')
    ->addArrayOfTable('fruit.variety')
    ->addValue('name', 'red delicious')
    ->addArrayOfTable('fruit.variety')
    ->addValue('name', 'granny smith')
    ->addArrayOfTable('fruit')
    ->addValue('name', 'banana')
    ->addArrayOfTable('fruit.variety')
    ->addValue('name', 'plantain')
    ->getTomlString();

echo $toml;
/*
[[fruit]]
name = "apple"

[[fruit.variety]]
name = "red delicious"

[[fruit.variety]]
name = "granny smith"

[[fruit]]
name = "banana"

[[fruit.variety]]
name = "plantain"
*/
```

## Data Types

### Strings

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('title', 'TOML Example')
    ->addValue('description', 'A string with "quotes"')
    ->addValue('path', 'C:\\Users\\nodejs\\templates')
    ->getTomlString();
```

### Literal Strings

Use `@` prefix for literal strings (no escaping):

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('regex', '@<\i\c*\s*>')
    ->getTomlString();

// Output: regex = '<\i\c*\s*>'
// Backslashes are preserved without escaping
```

To include a literal `@` at the start, use `@@`:

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('email', '@@user@example.com')
    ->getTomlString();

// Output: email = '@user@example.com'
```

### Numbers

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('integer', 42)
    ->addValue('negative', -17)
    ->addValue('float', 3.14)
    ->addValue('scientific', 5e22)
    ->getTomlString();

/*
integer = 42
negative = -17
float = 3.14
scientific = 5.0e+22
*/
```

### Booleans

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('enabled', true)
    ->addValue('disabled', false)
    ->getTomlString();

/*
enabled = true
disabled = false
*/
```

### Dates and Times

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('created', new DateTime('2024-01-15 10:30:00'))
    ->getTomlString();

/*
created = 2024-01-15T10:30:00Z
*/
```

### Arrays

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('ports', [8080, 8081, 8082])
    ->addValue('hosts', ['localhost', 'example.com'])
    ->addValue('matrix', [[1, 2], [3, 4]])
    ->getTomlString();

/*
ports = [8080, 8081, 8082]
hosts = ["localhost", "example.com"]
matrix = [[1, 2], [3, 4]]
*/
```

Arrays with DateTime objects:

```php
$builder = new TomlBuilder();

$toml = $builder
    ->addValue('timestamps', [
        new DateTime('2024-01-01'),
        new DateTime('2024-06-01'),
        new DateTime('2024-12-01')
    ])
    ->getTomlString();
```

## Indentation

Control indentation (default is 4 spaces):

```php
// No indentation
$builder = new TomlBuilder(0);

// 2 spaces
$builder = new TomlBuilder(2);

// 4 spaces (default)
$builder = new TomlBuilder(4);
```

## Real-World Examples

### Generate Application Config

```php
use Cline\Toml\TomlBuilder;

function generateAppConfig(array $config): string
{
    $builder = new TomlBuilder();

    $builder->addComment('Application Configuration');
    $builder->addValue('name', $config['name']);
    $builder->addValue('version', $config['version']);
    $builder->addValue('debug', $config['debug']);

    $builder->addTable('server');
    $builder->addValue('host', $config['server']['host']);
    $builder->addValue('port', $config['server']['port']);
    $builder->addValue('timeout', $config['server']['timeout'], 'seconds');

    $builder->addTable('database');
    $builder->addValue('driver', $config['database']['driver']);
    $builder->addValue('host', $config['database']['host']);
    $builder->addValue('port', $config['database']['port']);
    $builder->addValue('name', $config['database']['name']);

    return $builder->getTomlString();
}

$config = [
    'name' => 'My Application',
    'version' => '1.0.0',
    'debug' => false,
    'server' => [
        'host' => '0.0.0.0',
        'port' => 8080,
        'timeout' => 30,
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'myapp',
    ],
];

$toml = generateAppConfig($config);
file_put_contents('config.toml', $toml);
```

### Export Database Connections

```php
use Cline\Toml\TomlBuilder;

class DatabaseExporter
{
    public function export(array $connections): string
    {
        $builder = new TomlBuilder();

        $builder->addComment('Database Connections');

        foreach ($connections as $name => $connection) {
            $builder->addTable("database.{$name}");
            $builder->addValue('driver', $connection['driver']);
            $builder->addValue('host', $connection['host']);
            $builder->addValue('port', $connection['port']);
            $builder->addValue('username', $connection['username']);
            $builder->addValue('database', $connection['database']);
        }

        return $builder->getTomlString();
    }
}

$connections = [
    'production' => [
        'driver' => 'pgsql',
        'host' => 'db.prod.internal',
        'port' => 5432,
        'username' => 'app_user',
        'database' => 'myapp',
    ],
    'staging' => [
        'driver' => 'pgsql',
        'host' => 'db.staging.internal',
        'port' => 5432,
        'username' => 'app_user',
        'database' => 'myapp_staging',
    ],
];

$exporter = new DatabaseExporter();
$toml = $exporter->export($connections);

file_put_contents('database.toml', $toml);
```

### Generate Server List

```php
use Cline\Toml\TomlBuilder;

class ServerConfigGenerator
{
    public function generate(array $servers): string
    {
        $builder = new TomlBuilder();

        $builder->addComment('Server Configuration');
        $builder->addValue('updated', new DateTime());

        foreach ($servers as $server) {
            $builder->addArrayOfTable('servers');
            $builder->addValue('name', $server['name']);
            $builder->addValue('ip', $server['ip']);
            $builder->addValue('port', $server['port']);
            $builder->addValue('active', $server['active']);

            if (!empty($server['tags'])) {
                $builder->addValue('tags', $server['tags']);
            }
        }

        return $builder->getTomlString();
    }
}

$servers = [
    [
        'name' => 'web-1',
        'ip' => '10.0.1.1',
        'port' => 80,
        'active' => true,
        'tags' => ['web', 'production'],
    ],
    [
        'name' => 'web-2',
        'ip' => '10.0.1.2',
        'port' => 80,
        'active' => true,
        'tags' => ['web', 'production'],
    ],
    [
        'name' => 'db-1',
        'ip' => '10.0.2.1',
        'port' => 5432,
        'active' => true,
        'tags' => ['database', 'production'],
    ],
];

$generator = new ServerConfigGenerator();
$toml = $generator->generate($servers);

file_put_contents('servers.toml', $toml);
```

## Validation

The builder automatically validates:

- Key names (cannot be empty)
- Table names (must be valid unquoted keys)
- Array types (all elements must be same type)
- String characters (special characters are escaped)
- Duplicate keys and tables

## Method Chaining

All builder methods return the builder instance for fluent chaining:

```php
$toml = (new TomlBuilder())
    ->addComment('Configuration')
    ->addValue('version', '1.0')
    ->addTable('server')
    ->addValue('port', 8080)
    ->getTomlString();
```

## Next Steps

- [Data Types](#doc-docs-data-types) - Learn about all supported data types
- [Error Handling](#doc-docs-error-handling) - Handle builder exceptions
- [Parsing TOML](#doc-docs-parsing) - Parse TOML back into PHP

<a id="doc-docs-data-types"></a>

The Cline TOML parser supports all data types defined in the TOML 0.4.0 specification. Learn how each type is parsed and represented in PHP.

## Strings

### Basic Strings

Enclosed in double quotes with escape sequences:

```toml
title = "TOML Example"
description = "A string with \"quotes\""
```

Parsed as:

```php
[
    'title' => 'TOML Example',
    'description' => 'A string with "quotes"',
]
```

### Escape Sequences

Supported escape sequences:

```toml
backspace = "Value \b here"
tab = "Value \t here"
newline = "Value \n here"
formfeed = "Value \f here"
carriage = "Value \r here"
quote = "Value \" here"
backslash = "Value \\ here"
```

### Unicode Escapes

```toml
unicode = "Greek delta: \u03B4"
unicode32 = "Emoji: \U0001F600"
```

Parsed as:

```php
[
    'unicode' => 'Greek delta: δ',
    'unicode32' => 'Emoji: 😀',
]
```

### Literal Strings

Enclosed in single quotes, no escaping:

```toml
path = 'C:\Users\nodejs\templates'
regex = '<\i\c*\s*>'
```

Parsed as:

```php
[
    'path' => 'C:\Users\nodejs\templates',
    'regex' => '<\i\c*\s*>',
]
```

When building with TomlBuilder, prefix with `@`:

```php
$builder->addValue('regex', '@<\i\c*\s*>');
// Output: regex = '<\i\c*\s*>'
```

### Multi-line Basic Strings

```toml
description = """
Multi-line strings
are supported.
Line breaks are preserved."""
```

### Multi-line Literal Strings

```toml
regex = '''
I [dw]on't need \d{2} apples
'''
```

## Integers

### Standard Integers

```toml
positive = 99
negative = -17
zero = 0
```

Parsed as PHP `int`:

```php
[
    'positive' => 99,
    'negative' => -17,
    'zero' => 0,
]
```

### Large Integers

```toml
# Underscores for readability
large = 1_000_000
very_large = 9_223_372_036_854_775_807
```

Parsed as:

```php
[
    'large' => 1000000,
    'very_large' => 9223372036854775807,
]
```

### Other Bases

```toml
hex = 0xDEADBEEF
octal = 0o755
binary = 0b11010110
```

Parsed as decimal integers:

```php
[
    'hex' => 3735928559,
    'octal' => 493,
    'binary' => 214,
]
```

## Floats

### Standard Floats

```toml
pi = 3.14159
negative = -0.01
```

Parsed as PHP `float`:

```php
[
    'pi' => 3.14159,
    'negative' => -0.01,
]
```

### Scientific Notation

```toml
exponent = 5e+22
negative_exp = -2e-2
```

Parsed as:

```php
[
    'exponent' => 5.0e+22,
    'negative_exp' => -0.02,
]
```

### Special Float Values

```toml
# Infinity
infinity = inf
positive_infinity = +inf
negative_infinity = -inf

# Not a number
not_a_number = nan
```

Parsed as:

```php
[
    'infinity' => INF,
    'positive_infinity' => INF,
    'negative_infinity' => -INF,
    'not_a_number' => NAN,
]
```

### Building Floats

When building, ensure decimal point is included:

```php
$builder->addValue('price', 19.99);   // price = 19.99
$builder->addValue('whole', 42.0);    // whole = 42.0
```

## Booleans

```toml
enabled = true
disabled = false
```

Parsed as PHP `bool`:

```php
[
    'enabled' => true,
    'disabled' => false,
]
```

When building:

```php
$builder->addValue('enabled', true);   // enabled = true
$builder->addValue('disabled', false); // disabled = false
```

## Dates and Times

### Offset Date-Time

```toml
odt1 = 1979-05-27T07:32:00Z
odt2 = 1979-05-27T00:32:00-07:00
odt3 = 1979-05-27T00:32:00.999999-07:00
```

Parsed as PHP `DateTime` objects with timezone:

```php
$date = $config['odt1'];
echo $date->format('Y-m-d H:i:s'); // "1979-05-27 07:32:00"
```

### Local Date-Time

```toml
ldt1 = 1979-05-27T07:32:00
ldt2 = 1979-05-27T00:32:00.999999
```

### Local Date

```toml
ld1 = 1979-05-27
```

### Local Time

```toml
lt1 = 07:32:00
lt2 = 00:32:00.999999
```

### Building Dates

```php
$builder->addValue('created', new DateTime('2024-01-15 10:30:00'));
// Output: created = 2024-01-15T10:30:00Z
```

## Arrays

### Homogeneous Arrays

All elements must be the same type:

```toml
integers = [1, 2, 3]
strings = ["red", "yellow", "green"]
floats = [1.1, 2.2, 3.3]
booleans = [true, false, true]
dates = [1979-05-27T07:32:00Z, 1980-01-01T00:00:00Z]
```

Parsed as PHP arrays:

```php
[
    'integers' => [1, 2, 3],
    'strings' => ['red', 'yellow', 'green'],
    'floats' => [1.1, 2.2, 3.3],
    'booleans' => [true, false, true],
    'dates' => [
        new DateTime('1979-05-27 07:32:00'),
        new DateTime('1980-01-01 00:00:00'),
    ],
]
```

### Nested Arrays

```toml
matrix = [[1, 2], [3, 4], [5, 6]]
mixed_types = [[1, 2], ["a", "b"], [1.1, 2.2]]
```

Parsed as:

```php
[
    'matrix' => [[1, 2], [3, 4], [5, 6]],
    'mixed_types' => [[1, 2], ['a', 'b'], [1.1, 2.2]],
]
```

### Arrays with Line Breaks

```toml
hosts = [
    "alpha",
    "beta",
    "gamma"
]
```

### Empty Arrays

```toml
empty = []
```

Parsed as:

```php
['empty' => []]
```

### Building Arrays

```php
$builder->addValue('ports', [8080, 8081, 8082]);
$builder->addValue('hosts', ['localhost', 'example.com']);
$builder->addValue('matrix', [[1, 2], [3, 4]]);
```

## Tables

### Basic Tables

```toml
[database]
host = "localhost"
port = 5432
enabled = true
```

Parsed as:

```php
[
    'database' => [
        'host' => 'localhost',
        'port' => 5432,
        'enabled' => true,
    ],
]
```

### Nested Tables

```toml
[application]
name = "MyApp"

[application.server]
host = "0.0.0.0"
port = 8080

[application.server.ssl]
enabled = true
```

Parsed as:

```php
[
    'application' => [
        'name' => 'MyApp',
        'server' => [
            'host' => '0.0.0.0',
            'port' => 8080,
            'ssl' => [
                'enabled' => true,
            ],
        ],
    ],
]
```

### Dotted Keys

Alternative to nested tables:

```toml
[application]
name = "MyApp"
server.host = "0.0.0.0"
server.port = 8080
```

Produces the same structure as nested tables.

### Building Tables

```php
$builder
    ->addTable('database')
    ->addValue('host', 'localhost')
    ->addValue('port', 5432)
    ->addTable('database.replica')
    ->addValue('host', 'replica.internal')
    ->addValue('port', 5432);
```

## Inline Tables

Compact single-line table format:

```toml
name = { first = "Tom", last = "Preston-Werner" }
point = { x = 1, y = 2 }
colors = { red = 255, green = 128, blue = 0 }
```

Parsed as:

```php
[
    'name' => [
        'first' => 'Tom',
        'last' => 'Preston-Werner',
    ],
    'point' => ['x' => 1, 'y' => 2],
    'colors' => ['red' => 255, 'green' => 128, 'blue' => 0],
]
```

## Array of Tables

### Basic Array of Tables

```toml
[[products]]
name = "Hammer"
sku = 738594937

[[products]]
name = "Nail"
sku = 284758393
```

Parsed as:

```php
[
    'products' => [
        ['name' => 'Hammer', 'sku' => 738594937],
        ['name' => 'Nail', 'sku' => 284758393],
    ],
]
```

### Nested Array of Tables

```toml
[[fruit]]
name = "apple"

[[fruit.variety]]
name = "red delicious"

[[fruit.variety]]
name = "granny smith"

[[fruit]]
name = "banana"

[[fruit.variety]]
name = "plantain"
```

Parsed as:

```php
[
    'fruit' => [
        [
            'name' => 'apple',
            'variety' => [
                ['name' => 'red delicious'],
                ['name' => 'granny smith'],
            ],
        ],
        [
            'name' => 'banana',
            'variety' => [
                ['name' => 'plantain'],
            ],
        ],
    ],
]
```

### Building Array of Tables

```php
$builder
    ->addArrayOfTable('servers')
    ->addValue('name', 'web-1')
    ->addValue('ip', '10.0.1.1')
    ->addArrayOfTable('servers')
    ->addValue('name', 'web-2')
    ->addValue('ip', '10.0.1.2');
```

## Type Conversion Examples

### Parsing Example

```php
use Cline\Toml\Toml;

$toml = <<<'TOML'
# String types
title = "My Config"
path = 'C:\Windows\System32'

# Numeric types
count = 42
price = 19.99

# Boolean
enabled = true

# Date
created = 2024-01-15T10:30:00Z

# Array
tags = ["production", "database", "critical"]

# Table
[server]
host = "localhost"
port = 8080

# Array of tables
[[backups]]
name = "daily"
time = "02:00"

[[backups]]
name = "weekly"
time = "03:00"
TOML;

$config = Toml::parse($toml);

// Access values with proper PHP types
$title = $config['title'];                    // string
$count = $config['count'];                    // int
$price = $config['price'];                    // float
$enabled = $config['enabled'];                // bool
$created = $config['created'];                // DateTime
$tags = $config['tags'];                      // array
$server = $config['server'];                  // array
$backups = $config['backups'];                // array of arrays
```

### Building Example

```php
use Cline\Toml\TomlBuilder;

$builder = new TomlBuilder();

$toml = $builder
    ->addValue('title', 'My Config')
    ->addValue('path', '@C:\Windows\System32')
    ->addValue('count', 42)
    ->addValue('price', 19.99)
    ->addValue('enabled', true)
    ->addValue('created', new DateTime())
    ->addValue('tags', ['production', 'database', 'critical'])
    ->addTable('server')
    ->addValue('host', 'localhost')
    ->addValue('port', 8080)
    ->addArrayOfTable('backups')
    ->addValue('name', 'daily')
    ->addValue('time', '02:00')
    ->addArrayOfTable('backups')
    ->addValue('name', 'weekly')
    ->addValue('time', '03:00')
    ->getTomlString();
```

## Type Restrictions

### Array Homogeneity

Arrays must contain elements of the same type:

```toml
# Valid - all integers
valid = [1, 2, 3]

# Invalid - mixed types
invalid = [1, "two", 3.0]  # ParseException
```

### Key Restrictions

Keys must be:
- Non-empty
- Valid unquoted keys (letters, numbers, underscores, dashes) or quoted

```toml
# Valid keys
name = "value"
first-name = "value"
"special key" = "value"

# Invalid - empty key
"" = "value"  # EmptyKeyException
```

## Next Steps

- [Parsing TOML](#doc-docs-parsing) - Learn how to parse TOML files
- [Building TOML](#doc-docs-building) - Create TOML programmatically
- [Error Handling](#doc-docs-error-handling) - Handle type-related errors

<a id="doc-docs-error-handling"></a>

Handle parsing and building errors effectively with the comprehensive exception hierarchy provided by the Cline TOML parser.

## Exception Hierarchy

All exceptions implement the `Cline\Toml\Exception\TomlException` interface:

```
TomlException (interface)
├── ParseException
│   ├── FileNotFoundException
│   └── FileNotReadableException
├── SyntaxErrorException
│   ├── ParserSyntaxErrorException
│   ├── ParserUnexpectedTokenException
│   └── UnexpectedTokenException
├── DumpException (building errors)
│   ├── DuplicateKeyException
│   ├── DuplicateTableKeyException
│   ├── DuplicateArrayTableKeyException
│   ├── EmptyKeyException
│   ├── InvalidStringCharactersException
│   ├── MixedArrayTypesException
│   ├── UnsupportedDataTypeException
│   ├── UnquotedKeyRequiredException
│   ├── KeyDefinedAsImplicitTableException
│   └── TableAlreadyDefinedAsArrayException
└── Other exceptions
    ├── InvalidKeyException
    ├── InvalidTableKeyException
    ├── InvalidArrayTableKeyException
    ├── InvalidUtf8Exception
    ├── LexerParseException
    ├── EmptyEosTokenNameException
    └── EmptyNewlineTokenNameException
```

## Parsing Exceptions

### ParseException

Base exception for all parsing errors:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\ParseException;

try {
    $config = Toml::parse('invalid = [toml syntax');
} catch (ParseException $e) {
    echo "Parse error: " . $e->getMessage();
    echo "Line: " . $e->getParsedLine();
    echo "File: " . $e->getParsedFile(); // null for strings
    echo "Snippet: " . $e->getSnippet();
}
```

Available methods:
- `getMessage()` - Full error message with context
- `getParsedLine()` - Line number where error occurred
- `getParsedFile()` - Filename (null for string parsing)
- `getSnippet()` - Code snippet near the error

### FileNotFoundException

Thrown when parsing a non-existent file:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\FileNotFoundException;

try {
    $config = Toml::parseFile('missing.toml');
} catch (FileNotFoundException $e) {
    echo "File not found: " . $e->getMessage();
    // Handle missing file (create default, show error, etc.)
}
```

### FileNotReadableException

Thrown when file exists but cannot be read:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\FileNotReadableException;

try {
    $config = Toml::parseFile('restricted.toml');
} catch (FileNotReadableException $e) {
    echo "Cannot read file: " . $e->getMessage();
    // Check permissions, handle access error
}
```

### InvalidUtf8Exception

Thrown when input contains invalid UTF-8:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\InvalidUtf8Exception;

try {
    $config = Toml::parse($invalidUtf8String);
} catch (InvalidUtf8Exception $e) {
    echo "Invalid UTF-8 encoding: " . $e->getMessage();
}
```

### SyntaxErrorException

Thrown when TOML syntax is invalid or input ends unexpectedly:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\SyntaxErrorException;

$toml = <<<'TOML'
[incomplete
TOML;

try {
    $config = Toml::parse($toml);
} catch (SyntaxErrorException $e) {
    echo "Syntax error: " . $e->getMessage();
}
```

## Building Exceptions (DumpException)

### DuplicateKeyException

Thrown when adding a key that already exists:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\DuplicateKeyException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addValue('name', 'First')
        ->addValue('name', 'Second') // Duplicate!
        ->getTomlString();
} catch (DuplicateKeyException $e) {
    echo "Duplicate key: " . $e->getMessage();
    // Output: The key "name" has already been defined previously.
}
```

### DuplicateTableKeyException

Thrown when adding a table that already exists:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\DuplicateTableKeyException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addTable('database')
        ->addValue('host', 'localhost')
        ->addTable('database') // Duplicate!
        ->addValue('port', 5432)
        ->getTomlString();
} catch (DuplicateTableKeyException $e) {
    echo "Duplicate table: " . $e->getMessage();
}
```

### DuplicateArrayTableKeyException

Thrown when array of table structure conflicts:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\DuplicateArrayTableKeyException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addArrayOfTable('items')
        ->addValue('name', 'First')
        ->addTable('items') // Cannot add table after array of tables!
        ->getTomlString();
} catch (DuplicateArrayTableKeyException $e) {
    echo "Invalid structure: " . $e->getMessage();
}
```

### EmptyKeyException

Thrown when key is empty or whitespace-only:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\EmptyKeyException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addValue('', 'value') // Empty key!
        ->getTomlString();
} catch (EmptyKeyException $e) {
    echo "Empty key: " . $e->getMessage();
}
```

### InvalidStringCharactersException

Thrown when string contains invalid escape sequences:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\InvalidStringCharactersException;

try {
    $builder = new TomlBuilder();
    // Invalid escape sequence in string
    $toml = $builder
        ->addValue('path', "invalid\\escape\\sequence")
        ->getTomlString();
} catch (InvalidStringCharactersException $e) {
    echo "Invalid string: " . $e->getMessage();
}
```

### MixedArrayTypesException

Thrown when array contains mixed types:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\MixedArrayTypesException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addValue('mixed', [1, 'two', 3.0]) // Mixed types!
        ->getTomlString();
} catch (MixedArrayTypesException $e) {
    echo "Mixed array types: " . $e->getMessage();
}
```

### UnsupportedDataTypeException

Thrown when value contains unsupported data types:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\UnsupportedDataTypeException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addValue('invalid', [new stdClass()]) // Objects not supported!
        ->getTomlString();
} catch (UnsupportedDataTypeException $e) {
    echo "Unsupported type: " . $e->getMessage();
}
```

### UnquotedKeyRequiredException

Thrown when table/array keys contain invalid characters:

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\UnquotedKeyRequiredException;

try {
    $builder = new TomlBuilder();
    $toml = $builder
        ->addTable('invalid table name') // Spaces require quotes!
        ->getTomlString();
} catch (UnquotedKeyRequiredException $e) {
    echo "Invalid key: " . $e->getMessage();
}
```

## Error Handling Patterns

### Basic Try-Catch

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\TomlException;

function loadConfig(string $path): array
{
    try {
        return Toml::parseFile($path);
    } catch (TomlException $e) {
        // Log error
        error_log("TOML error: " . $e->getMessage());

        // Return default configuration
        return [
            'app' => ['name' => 'Default App'],
            'debug' => false,
        ];
    }
}
```

### Specific Exception Handling

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\FileNotFoundException;
use Cline\Toml\Exception\ParseException;

function loadConfig(string $path): array
{
    try {
        return Toml::parseFile($path);
    } catch (FileNotFoundException $e) {
        // Create default config file
        $defaultConfig = "name = \"My App\"\nversion = \"1.0.0\"";
        file_put_contents($path, $defaultConfig);
        return Toml::parse($defaultConfig);
    } catch (ParseException $e) {
        // Show detailed error with line number
        throw new \RuntimeException(
            "Invalid TOML syntax in {$path} at line {$e->getParsedLine()}: {$e->getMessage()}"
        );
    }
}
```

### Validation with Custom Messages

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\DuplicateKeyException;
use Cline\Toml\Exception\EmptyKeyException;
use Cline\Toml\Exception\MixedArrayTypesException;

class ConfigBuilder
{
    private TomlBuilder $builder;
    private array $errors = [];

    public function __construct()
    {
        $this->builder = new TomlBuilder();
    }

    public function addSetting(string $key, mixed $value): self
    {
        try {
            $this->builder->addValue($key, $value);
        } catch (DuplicateKeyException $e) {
            $this->errors[] = "Setting '{$key}' is already defined";
        } catch (EmptyKeyException $e) {
            $this->errors[] = "Setting key cannot be empty";
        } catch (MixedArrayTypesException $e) {
            $this->errors[] = "Setting '{$key}' contains mixed array types";
        }

        return $this;
    }

    public function build(): string
    {
        if (!empty($this->errors)) {
            throw new \RuntimeException(
                "Configuration errors:\n- " . implode("\n- ", $this->errors)
            );
        }

        return $this->builder->getTomlString();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

// Usage
$builder = new ConfigBuilder();
$builder
    ->addSetting('name', 'App')
    ->addSetting('name', 'App2') // Duplicate
    ->addSetting('ports', [8080, 'invalid']); // Mixed types

if ($errors = $builder->getErrors()) {
    foreach ($errors as $error) {
        echo "Error: {$error}\n";
    }
}
```

### Graceful Degradation

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\TomlException;

class ConfigLoader
{
    private array $searchPaths;

    public function __construct(array $searchPaths)
    {
        $this->searchPaths = $searchPaths;
    }

    public function load(): array
    {
        foreach ($this->searchPaths as $path) {
            try {
                return Toml::parseFile($path);
            } catch (TomlException $e) {
                // Try next path
                continue;
            }
        }

        // No valid config found, use built-in defaults
        return $this->getDefaults();
    }

    private function getDefaults(): array
    {
        return [
            'app' => ['name' => 'Default Application'],
            'server' => ['host' => 'localhost', 'port' => 8080],
        ];
    }
}

// Usage
$loader = new ConfigLoader([
    '/etc/myapp/config.toml',
    '/usr/local/etc/myapp/config.toml',
    __DIR__ . '/config.toml',
]);

$config = $loader->load();
```

### Detailed Error Reporting

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\ParseException;

function parseWithDetailedErrors(string $toml, string $source = 'string'): array
{
    try {
        return Toml::parse($toml);
    } catch (ParseException $e) {
        $error = [
            'message' => $e->getMessage(),
            'source' => $source,
            'line' => $e->getParsedLine(),
            'snippet' => $e->getSnippet(),
            'file' => $e->getParsedFile(),
        ];

        // Log detailed error
        error_log(json_encode($error, JSON_PRETTY_PRINT));

        // Show user-friendly message
        $userMessage = "Configuration error";
        if ($error['line'] > 0) {
            $userMessage .= " at line {$error['line']}";
        }
        if ($error['snippet']) {
            $userMessage .= ": near \"{$error['snippet']}\"";
        }

        throw new \RuntimeException($userMessage, 0, $e);
    }
}
```

### Validation Helper

```php
use Cline\Toml\TomlBuilder;
use Cline\Toml\Exception\TomlException;

class TomlValidator
{
    public static function validateStructure(array $data): array
    {
        $errors = [];

        try {
            $builder = new TomlBuilder();
            self::buildFromArray($builder, $data);
            $builder->getTomlString();
        } catch (TomlException $e) {
            $errors[] = $e->getMessage();
        }

        return $errors;
    }

    private static function buildFromArray(TomlBuilder $builder, array $data, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && !empty($value) && !self::isAssociative($value)) {
                // Indexed array - might be array of tables
                continue;
            }

            if (is_array($value)) {
                $tableName = $prefix ? "{$prefix}.{$key}" : $key;
                $builder->addTable($tableName);
                self::buildFromArray($builder, $value, $tableName);
            } else {
                $builder->addValue($key, $value);
            }
        }
    }

    private static function isAssociative(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

// Usage
$data = [
    'name' => 'App',
    'name' => 'Duplicate', // Will be caught
    'server' => ['port' => 8080],
];

$errors = TomlValidator::validateStructure($data);
if (!empty($errors)) {
    echo "Validation errors:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}
```

## Best Practices

### Always Handle File Errors

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\FileNotFoundException;
use Cline\Toml\Exception\FileNotReadableException;

function safeParseFile(string $path): ?array
{
    if (!file_exists($path)) {
        return null;
    }

    if (!is_readable($path)) {
        return null;
    }

    try {
        return Toml::parseFile($path);
    } catch (FileNotFoundException | FileNotReadableException $e) {
        return null;
    }
}
```

### Validate User Input

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\ParseException;

function validateUserConfig(string $userInput): bool
{
    try {
        Toml::parse($userInput);
        return true;
    } catch (ParseException $e) {
        return false;
    }
}
```

### Use Type-Specific Catches

```php
use Cline\Toml\Exception\DuplicateKeyException;
use Cline\Toml\Exception\EmptyKeyException;
use Cline\Toml\Exception\MixedArrayTypesException;

try {
    // Build TOML
} catch (DuplicateKeyException $e) {
    // Handle duplicate keys specifically
} catch (EmptyKeyException $e) {
    // Handle empty keys
} catch (MixedArrayTypesException $e) {
    // Handle array type errors
}
```

## Next Steps

- [Getting Started](#doc-docs-readme) - Basic usage and installation
- [Parsing TOML](#doc-docs-parsing) - Learn about parsing options
- [Building TOML](#doc-docs-building) - Create TOML programmatically

<a id="doc-docs-parsing"></a>

Parse TOML content from strings or files into native PHP data structures with full TOML 0.4.0 specification support.

## Parsing Methods

### Parse from String

Parse TOML content directly from a string:

```php
use Cline\Toml\Toml;

$toml = <<<'TOML'
name = "Configuration"
version = "1.0.0"
TOML;

$result = Toml::parse($toml);

// Returns: ['name' => 'Configuration', 'version' => '1.0.0']
```

### Parse from File

Load and parse a TOML file:

```php
use Cline\Toml\Toml;

$config = Toml::parseFile('/path/to/config.toml');
```

The parser validates that:
- File exists (throws `FileNotFoundException`)
- File is readable (throws `FileNotReadableException`)
- Content is valid TOML (throws `ParseException`)

## Result Formats

### Array Format (Default)

By default, parsed TOML returns a PHP array:

```php
$toml = <<<'TOML'
title = "My App"

[database]
host = "localhost"
port = 5432
TOML;

$config = Toml::parse($toml);

// Array access
echo $config['title'];              // "My App"
echo $config['database']['host'];   // "localhost"
```

### Object Format

Return results as `stdClass` objects:

```php
$config = Toml::parse($toml, true);

// Object access
echo $config->title;              // "My App"
echo $config->database->host;     // "localhost"
```

### Empty Input

Empty TOML strings return `null`:

```php
$result = Toml::parse('');
// Returns: null
```

## Parsing Tables

### Simple Tables

```php
$toml = <<<'TOML'
[server]
host = "localhost"
port = 8080

[database]
host = "db.internal"
port = 5432
TOML;

$config = Toml::parse($toml);

echo $config['server']['host'];   // "localhost"
echo $config['database']['port']; // 5432
```

### Nested Tables

```php
$toml = <<<'TOML'
[application]
name = "MyApp"

[application.server]
host = "0.0.0.0"
port = 8080

[application.server.ssl]
enabled = true
cert = "/path/to/cert.pem"
TOML;

$config = Toml::parse($toml);

echo $config['application']['name'];                  // "MyApp"
echo $config['application']['server']['host'];        // "0.0.0.0"
echo $config['application']['server']['ssl']['cert']; // "/path/to/cert.pem"
```

### Dotted Keys

TOML supports dotted keys as an alternative syntax:

```php
$toml = <<<'TOML'
name = "App"
server.host = "localhost"
server.port = 8080
TOML;

$config = Toml::parse($toml);

// Same structure as table syntax
echo $config['name'];           // "App"
echo $config['server']['host']; // "localhost"
```

## Parsing Arrays

### Simple Arrays

```php
$toml = 'ports = [8080, 8081, 8082]';

$config = Toml::parse($toml);

foreach ($config['ports'] as $port) {
    echo $port . "\n";
}
// Output: 8080, 8081, 8082
```

### Nested Arrays

```php
$toml = 'matrix = [[1, 2], [3, 4], [5, 6]]';

$config = Toml::parse($toml);

echo $config['matrix'][0][0]; // 1
echo $config['matrix'][1][1]; // 4
```

### Array of Tables

```php
$toml = <<<'TOML'
[[products]]
name = "Hammer"
sku = 738594937

[[products]]
name = "Nail"
sku = 284758393
TOML;

$config = Toml::parse($toml);

foreach ($config['products'] as $product) {
    echo $product['name'] . ": " . $product['sku'] . "\n";
}
// Output:
// Hammer: 738594937
// Nail: 284758393
```

### Nested Array of Tables

```php
$toml = <<<'TOML'
[[fruit]]
name = "apple"

[[fruit.variety]]
name = "red delicious"

[[fruit.variety]]
name = "granny smith"

[[fruit]]
name = "banana"

[[fruit.variety]]
name = "plantain"
TOML;

$config = Toml::parse($toml);

// Access nested structure
echo $config['fruit'][0]['name'];                   // "apple"
echo $config['fruit'][0]['variety'][0]['name'];     // "red delicious"
echo $config['fruit'][0]['variety'][1]['name'];     // "granny smith"
echo $config['fruit'][1]['name'];                   // "banana"
echo $config['fruit'][1]['variety'][0]['name'];     // "plantain"
```

## Parsing Inline Tables

Inline tables are enclosed in curly braces:

```php
$toml = <<<'TOML'
name = { first = "Tom", last = "Preston-Werner" }
point = { x = 1, y = 2 }
TOML;

$config = Toml::parse($toml);

echo $config['name']['first'];  // "Tom"
echo $config['point']['x'];     // 1
```

## Real-World Example

Parse a complete application configuration:

```php
use Cline\Toml\Toml;
use Cline\Toml\Exception\ParseException;
use Cline\Toml\Exception\FileNotFoundException;

class ConfigManager
{
    private array $config;

    public function __construct(string $configPath)
    {
        try {
            $this->config = Toml::parseFile($configPath);
        } catch (FileNotFoundException $e) {
            throw new \RuntimeException("Config file not found: {$configPath}");
        } catch (ParseException $e) {
            throw new \RuntimeException(
                "Invalid TOML at line {$e->getParsedLine()}: {$e->getMessage()}"
            );
        }
    }

    public function getDatabaseConfig(string $connection = 'default'): array
    {
        if (!isset($this->config['database'][$connection])) {
            throw new \InvalidArgumentException("Database connection '{$connection}' not found");
        }

        return $this->config['database'][$connection];
    }

    public function getServers(): array
    {
        return $this->config['servers'] ?? [];
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->config['features'][$feature] ?? false;
    }
}

// config.toml
/*
[database.default]
driver = "mysql"
host = "localhost"
port = 3306

[database.cache]
driver = "redis"
host = "localhost"
port = 6379

[[servers]]
name = "web-1"
ip = "10.0.1.1"

[[servers]]
name = "web-2"
ip = "10.0.1.2"

[features]
api = true
webhooks = false
*/

// Usage
$config = new ConfigManager('config.toml');

$dbConfig = $config->getDatabaseConfig('default');
echo $dbConfig['host']; // "localhost"

foreach ($config->getServers() as $server) {
    echo "{$server['name']}: {$server['ip']}\n";
}

if ($config->isFeatureEnabled('api')) {
    echo "API is enabled\n";
}
```

## Performance Tips

### Caching Parsed Results

For frequently accessed configuration files, cache the parsed result:

```php
use Cline\Toml\Toml;

class CachedConfig
{
    private static ?array $cache = null;

    public static function load(string $path): array
    {
        if (self::$cache === null) {
            self::$cache = Toml::parseFile($path);
        }

        return self::$cache;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
```

### Parse Once, Use Many Times

```php
// Parse once
$config = Toml::parseFile('config.toml');

// Store in container, service, or static property
$app->instance('config', $config);

// Use throughout application
$dbHost = $app->get('config')['database']['host'];
```

## File Path Handling

The parser accepts both absolute and relative paths:

```php
// Absolute path
$config = Toml::parseFile('/var/www/config.toml');

// Relative path
$config = Toml::parseFile('config/app.toml');

// Using constants
$config = Toml::parseFile(__DIR__ . '/config.toml');

// With environment variables
$configPath = getenv('CONFIG_PATH') ?: 'config.toml';
$config = Toml::parseFile($configPath);
```

## Comments in TOML

Comments are ignored during parsing:

```php
$toml = <<<'TOML'
# This is a comment
name = "App" # This is also a comment

# Comments can be on their own line
[database]
host = "localhost" # End-of-line comment
TOML;

$config = Toml::parse($toml);
// Comments are not included in the result
```

## Next Steps

- [Building TOML](#doc-docs-building) - Create TOML programmatically
- [Data Types](#doc-docs-data-types) - Learn about all supported data types
- [Error Handling](#doc-docs-error-handling) - Handle parsing errors effectively
