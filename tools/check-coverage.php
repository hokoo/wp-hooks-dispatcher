<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: check-coverage.php <clover.xml> <minimum-percent>\n");
    exit(2);
}

$reportPath = $argv[1];
$minimum = filter_var($argv[2], FILTER_VALIDATE_FLOAT);

if ($minimum === false || $minimum < 0 || $minimum > 100) {
    fwrite(STDERR, "Minimum coverage must be a number from 0 to 100.\n");
    exit(2);
}

if (!is_file($reportPath) || !is_readable($reportPath)) {
    fwrite(STDERR, sprintf("Coverage report is not readable: %s\n", $reportPath));
    exit(2);
}

libxml_use_internal_errors(true);
$report = simplexml_load_file($reportPath);

if ($report === false || !isset($report->project->metrics)) {
    fwrite(STDERR, sprintf("Invalid Clover coverage report: %s\n", $reportPath));
    exit(2);
}

$metrics = $report->project->metrics;
$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];

if ($statements <= 0 || $coveredStatements < 0 || $coveredStatements > $statements) {
    fwrite(STDERR, sprintf("Invalid line metrics in coverage report: %s\n", $reportPath));
    exit(2);
}

$coverage = ($coveredStatements / $statements) * 100;

printf(
    "Production line coverage: %.2f%% (%d/%d), required: %.2f%%\n",
    $coverage,
    $coveredStatements,
    $statements,
    $minimum
);

if ($coverage < $minimum) {
    fwrite(STDERR, "Production line coverage is below the required minimum.\n");
    exit(1);
}
