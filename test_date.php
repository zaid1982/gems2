<?php
class TestNormalize {
    private function normalizeDate($val): ?string {
        if (!is_string($val)) { return null; }
        $val = trim($val);
        if ($val === '' || $val === '0000-00-00') { return null; }
        $dt = DateTime::createFromFormat('Y-m-d', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        $dt = DateTime::createFromFormat('d/m/Y', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        $dt = DateTime::createFromFormat('j F, Y', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        $dt = DateTime::createFromFormat('j F Y', $val);
        if ($dt instanceof DateTime) { return $dt->format('Y-m-d'); }
        try {
            $dt = new DateTime($val);
            return $dt->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }
    public function test() {
        echo 'Testing: 1 September, 2025 -> ' . $this->normalizeDate('1 September, 2025') . PHP_EOL;
        echo 'Testing: 30 September, 2026 -> ' . $this->normalizeDate('30 September, 2026') . PHP_EOL;
        echo 'Testing: 2025-09-01 -> ' . $this->normalizeDate('2025-09-01') . PHP_EOL;
    }
}
$test = new TestNormalize();
$test->test();
