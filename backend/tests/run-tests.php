<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/autoload.php';
require_once __DIR__ . '/IngestionServiceTest.php';
require_once __DIR__ . '/AnalyticsRepositoryTest.php';
require_once __DIR__ . '/ServerSdkTest.php';

use Analytics\Tests\IngestionServiceTest;
use Analytics\Tests\AnalyticsRepositoryTest;
use Analytics\Tests\ServerSdkTest;

$testClasses = [
    IngestionServiceTest::class,
    AnalyticsRepositoryTest::class,
    ServerSdkTest::class,
];

echo "\n======================================================\n";
echo "   Running Analytics Platform PHP Test Suite\n";
echo "======================================================\n\n";

$totalTests = 0;
$passed = 0;
$failed = 0;
$startTime = microtime(true);

foreach ($testClasses as $className) {
    echo "Class: {$className}\n";
    $ref = new ReflectionClass($className);
    $instance = new $className();

    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $methodName = $method->getName();
        if (!str_starts_with($methodName, 'test')) {
            continue;
        }

        $totalTests++;
        echo "  - {$methodName}... ";

        try {
            if (method_exists($instance, 'setUp')) {
                $instance->setUp();
            }

            $instance->$methodName();
            echo "[PASS]\n";
            $passed++;
        } catch (\Throwable $e) {
            echo "[FAIL]\n";
            echo "    -> " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
            $failed++;
        }
    }
    echo "\n";
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);

echo "------------------------------------------------------\n";
echo "Results: {$passed} passed, {$failed} failed ({$totalTests} total) in {$elapsed} ms\n";
echo "======================================================\n\n";

exit($failed === 0 ? 0 : 1);
