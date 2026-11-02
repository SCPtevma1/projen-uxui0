<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/pricing.php';

$settings = [
    'bw_rate' => 2.0,
    'color_rate' => 5.0,
    'shipping_fee' => 40.0,
    'paper_a3' => 3.0,
    'paper_b5' => 0.0,
    'paper_80gsm' => 0.25,
    'paper_card' => 3.0,
    'binding_staple' => 0.0,
    'binding_spiral' => 20.0,
    'binding_coil' => 30.0,
    'bulk_threshold' => 50.0,
    'bulk_bw_rate' => 0.8,
    'minimum_order' => 20.0,
];

function expect_price(float $actual, float $expected, string $case): void
{
    if (abs($actual - $expected) > 0.001) {
        throw new RuntimeException("{$case}: expected {$expected}, got {$actual}");
    }
}

$belowBulk = calculate_order($settings, 49, 1, 'bw', 'a4_70', 'none', 'pickup');
expect_price($belowBulk['printing'], 98, '49-page regular rate');

$atBulk = calculate_order($settings, 25, 2, 'bw', 'a4_70', 'none', 'pickup');
expect_price($atBulk['printing'], 40, '50-page bulk rate across copies');

$shipping = calculate_order($settings, 1, 1, 'bw', 'a4_70', 'none', 'shipping');
expect_price($shipping['subtotal'], 2, 'subtotal excludes shipping');
expect_price($shipping['shipping'], 40, 'flat shipping fee');
expect_price($shipping['total'], 42, 'shipping included in total');

$minimum = calculate_order($settings, 1, 1, 'bw', 'a4_70', 'none', 'pickup');
expect_price($minimum['total'], 20, 'minimum order');

$extras = calculate_order($settings, 2, 2, 'color', 'a4_80', 'spiral', 'pickup');
expect_price($extras['printing'], 20, 'color printing');
expect_price($extras['paper'], 1, 'paper surcharge per page');
expect_price($extras['binding'], 40, 'binding fee per copy');
expect_price($extras['total'], 61, 'paper and binding total');

echo "Pricing tests passed.\n";
