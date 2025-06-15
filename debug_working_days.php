<?php

require_once 'vendor/autoload.php';

use Carbon\Carbon;

$start = Carbon::parse('2025-05-26');
$end = Carbon::parse('2025-06-25');

echo "Period: " . $start->format('Y-m-d') . " to " . $end->format('Y-m-d') . PHP_EOL;

$count = 0;
$current = $start->copy();

while ($current->lte($end)) {
    if (!$current->isWeekend()) {
        echo $current->format('Y-m-d l') . PHP_EOL;
        $count++;
    }
    $current->addDay();
}

echo "Total working days: " . $count . PHP_EOL;
echo "Expected hours: " . ($count * 8) . PHP_EOL;
echo "Test expects: 176 hours (22 days)" . PHP_EOL;
echo "Test gets: 184 hours (23 days)" . PHP_EOL;
