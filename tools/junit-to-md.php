<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../tests/IntrospectorTest.php';

use Hudsxn\Introspection\Tests\IntrospectorTest;

/** ---------------- Paths ---------------- */
$input  = __DIR__ . '/../build/phpunit.xml';
$output = __DIR__ . '/../TESTS.md';

/** ---------------- Extract Test Descriptions ---------------- */
$descriptions = [];

$ref = new ReflectionClass(IntrospectorTest::class);
foreach ($ref->getMethods() as $method) {
    if (!$method->isPublic()) {
        continue;
    }

    $doc = $method->getDocComment();
    if (!$doc) {
        continue;
    }

    // Clean up the docblock delimiters /** and */
    $docContent = preg_replace('/^\/\*\*|\*\/$/', '', $doc);

    // Remove leading * and whitespace from each line
    $lines = array_map(
        fn ($l) => preg_replace('/^\s*\*\s?/', '', trim($l)),
        explode("\n", $docContent)
    );

    // Combine all lines into a single string
    $descriptions[$method->getName()] = trim(implode("\n", $lines));
}


/** ---------------- Load PHPUnit XML ---------------- */
$xml = simplexml_load_file($input);
if (!$xml) {
    throw new RuntimeException('Unable to read phpunit.xml');
}

/**
 * Recursively collect all test cases
 */
function collectTestCases(SimpleXMLElement $suite, array &$cases): void
{
    foreach ($suite->testcase as $testcase) {
        $cases[] = $testcase;
    }

    foreach ($suite->testsuite as $childSuite) {
        collectTestCases($childSuite, $cases);
    }
}

$cases = [];
collectTestCases($xml->testsuite, $cases);

/** ---------------- Summary ---------------- */
$summary = [
    'tests'      => (int) $xml->testsuite['tests'],
    'assertions' => (int) $xml->testsuite['assertions'],
    'failures'   => (int) $xml->testsuite['failures'],
    'errors'     => (int) $xml->testsuite['errors'],
    'skipped'    => (int) $xml->testsuite['skipped'],
    'time'       => number_format((float) $xml->testsuite['time'], 4),
];

/** ---------------- Sort Tests (Stable Output) ---------------- */
usort($cases, fn ($a, $b) => strcmp((string) $a['name'], (string) $b['name']));

/** ---------------- Markdown ---------------- */
$md = [];

$md[] = '# 🧪 PHPUnit Test Report';
$md[] = '';
$md[] = '## 📊 Summary';
$md[] = '';
$md[] = '| Metric | Value |';
$md[] = '|--------|-------|';
$md[] = "| Total Tests | {$summary['tests']} |";
$md[] = "| Assertions | {$summary['assertions']} |";
$md[] = "| Failures | {$summary['failures']} |";
$md[] = "| Errors | {$summary['errors']} |";
$md[] = "| Skipped | {$summary['skipped']} |";
$md[] = "| Total Time | {$summary['time']}s |";
$md[] = '';
$md[] = '---';
$md[] = '';
$md[] = '## ✅ Test Results';
$md[] = '';
$md[] = '| Test | What it Tests | Status | Assertions | Time |';
$md[] = '|------|---------------|--------|------------|------|';

foreach ($cases as $case) {
    $name       = (string) $case['name'];
    $assertions = (int) $case['assertions'];
    $time       = number_format((float) $case['time'], 4);
    $desc       = str_replace(["\n", "\r", "@description "], ' ', $descriptions[$name]) ?? '—';

    if (isset($case->failure) || isset($case->error)) {
        $status = '❌ Failed';
    } elseif (isset($case->skipped)) {
        $status = '⏭️ Skipped';
    } else {
        $status = '✅ Passed';
    }

    $md[] = sprintf(
        '| `%s` | %s | %s | %d | %ss |',
        $name,
        $desc,
        $status,
        $assertions,
        $time
    );
}

$md[] = '';
$md[] = '---';
$md[] = '';
$md[] = '_Generated automatically by **Hudsxn Introspection**_';

file_put_contents($output, implode("\n", $md));

echo "✔ Markdown report generated: TESTS.md\n";
unlink($input);
rmdir("build");