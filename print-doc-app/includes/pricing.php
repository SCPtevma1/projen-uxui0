<?php
declare(strict_types=1);

function calculate_order(array $settings, int $totalPages, int $copies, string $color, string $paper, string $binding, string $delivery): array
{
    $pageCount = $totalPages * $copies;
    $rate = $color === 'color' ? $settings['color_rate'] : $settings['bw_rate'];
    if ($color === 'bw' && $pageCount >= $settings['bulk_threshold']) {
        $rate = $settings['bulk_bw_rate'];
    }

    $paperSurcharges = [
        'a4_70' => 0.0,
        'a3_70' => $settings['paper_a3'],
        'b5_70' => $settings['paper_b5'],
        'a4_80' => $settings['paper_80gsm'],
        'a4_card' => $settings['paper_card'],
    ];
    $bindingFees = [
        'none' => 0.0,
        'staple' => $settings['binding_staple'],
        'spiral' => $settings['binding_spiral'],
        'coil' => $settings['binding_coil'],
    ];

    $printing = $pageCount * $rate;
    $paperCost = $pageCount * ($paperSurcharges[$paper] ?? throw new InvalidArgumentException('ตัวเลือกกระดาษไม่ถูกต้อง'));
    $bindingCost = $copies * ($bindingFees[$binding] ?? throw new InvalidArgumentException('ตัวเลือกเข้าเล่มไม่ถูกต้อง'));
    $subtotal = $printing + $paperCost + $bindingCost;
    $shipping = $delivery === 'shipping' ? $settings['shipping_fee'] : 0.0;
    $total = max($settings['minimum_order'], $subtotal + $shipping);

    return [
        'total_pages' => $pageCount,
        'printing' => round($printing, 2),
        'paper' => round($paperCost, 2),
        'binding' => round($bindingCost, 2),
        'shipping' => round($shipping, 2),
        'subtotal' => round($subtotal, 2),
        'total' => round($total, 2),
    ];
}
