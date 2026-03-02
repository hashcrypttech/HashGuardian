<?php

namespace Hashcrypttech\HashGuardian\Export;

class ExportManager
{
    public function toCsv(array $entries, array $columns = []): string
    {
        if (empty($entries)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        if (empty($columns)) {
            $first = $entries[0];
            $columns = array_keys(is_array($first) ? $first : (array) $first);
        }

        fputcsv($output, $columns);

        foreach ($entries as $entry) {
            $row = [];
            $data = is_array($entry) ? $entry : (array) $entry;
            foreach ($columns as $col) {
                $val = $data[$col] ?? '';
                $row[] = is_array($val) ? json_encode($val) : (string) $val;
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function toJson(array $entries): string
    {
        return json_encode(['data' => $entries, 'exported_at' => now()->toDateTimeString()], JSON_PRETTY_PRINT);
    }
}
