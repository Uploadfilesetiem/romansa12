<?php

namespace App\Services;

// Mengubah QRIS statis (nominal diisi manual oleh pembeli) menjadi QRIS
// dinamis (nominal sudah tertanam di dalam QR), mengikuti format standar
// EMV QR Code (EMVCo) yang dipakai QRIS di Indonesia. Semua dihitung
// sendiri secara lokal, tidak memanggil layanan/API pihak ketiga mana pun.
class QrisService
{
    public function toDinamis(string $qrisStatis, int $nominal): string
    {
        $qrisStatis = trim(preg_replace('/\s+/', '', $qrisStatis));

        $entries = $this->parseTlv($qrisStatis);
        if (count($entries) === 0) {
            throw new \Exception('Format QRIS tidak dikenali.');
        }

        // Buang tag 63 (CRC) lama, akan dihitung ulang di akhir.
        $entries = array_values(array_filter($entries, fn ($e) => $e['tag'] !== '63'));

        // Tag 01 = Point of Initiation Method. "11" = statis, "12" = dinamis.
        $found01 = false;
        foreach ($entries as &$e) {
            if ($e['tag'] === '01') {
                $e['value'] = '12';
                $found01 = true;
            }
        }
        unset($e);
        if (!$found01) {
            array_splice($entries, min(1, count($entries)), 0, [['tag' => '01', 'value' => '12']]);
        }

        // Buang tag 54 (nominal) lama kalau sudah ada.
        $entries = array_values(array_filter($entries, fn ($e) => $e['tag'] !== '54'));

        // Sisipkan tag 54 baru: idealnya setelah tag 53 (mata uang), kalau
        // tidak ada, taruh setelah tag 01.
        $posisi = null;
        foreach ($entries as $idx => $e) {
            if ($e['tag'] === '53') {
                $posisi = $idx + 1;
            }
        }
        if ($posisi === null) {
            foreach ($entries as $idx => $e) {
                if ($e['tag'] === '01') {
                    $posisi = $idx + 1;
                    break;
                }
            }
        }

        $tagNominal = ['tag' => '54', 'value' => (string) max(0, $nominal)];
        if ($posisi === null) {
            $entries[] = $tagNominal;
        } else {
            array_splice($entries, $posisi, 0, [$tagNominal]);
        }

        $tanpaCrc = $this->buildTlv($entries).'6304';
        $crc = $this->crc16($tanpaCrc);

        return $tanpaCrc.$crc;
    }

    private function parseTlv(string $str): array
    {
        $result = [];
        $i = 0;
        $len = strlen($str);
        while ($i + 4 <= $len) {
            $tag = substr($str, $i, 2);
            $length = (int) substr($str, $i + 2, 2);
            $value = substr($str, $i + 4, $length);
            $result[] = ['tag' => $tag, 'value' => $value];
            $i += 4 + $length;
        }
        return $result;
    }

    private function buildTlv(array $entries): string
    {
        $out = '';
        foreach ($entries as $e) {
            $out .= $e['tag'].str_pad((string) strlen($e['value']), 2, '0', STR_PAD_LEFT).$e['value'];
        }
        return $out;
    }

    private function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
