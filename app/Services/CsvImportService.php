<?php

declare(strict_types=1);

namespace App\Services;
use RuntimeException;

class CsvImportService
{
    /**
     * 楽天e-NAVIのクレカ利用明細CSVをパースする。
     *
     * - Shift-JIS / UTF-8 両対応
     * - ヘッダー行と利用日が空の補足行（為替レート等）はスキップ
     * - 利用日は取り込まず、利用店名・商品名(memo)と利用金額(amount)のみ返す
     *
     * @return array<int, array{memo: string, amount: int}>
     */
    public function parseCreditCardCsv(string $filePath): array
    {
        $rawContent = file_get_contents($filePath);

        if ($rawContent === false) {
            throw new RuntimeException("CSVファイルを読み込めません: {$filePath}");
        }

        $encoding = mb_detect_encoding($rawContent, ['UTF-8', 'SJIS-win'], true);
        $content  = ($encoding !== false && $encoding !== 'UTF-8')
            ? (string) mb_convert_encoding($rawContent, 'UTF-8', $encoding)
            : $rawContent;

        // 変換済みコンテンツをPHP一時ファイルに書き出してfgetcsvで解析
        $tmpHandle = tmpfile();

        if ($tmpHandle === false) {
            throw new RuntimeException('一時ファイルを作成できません。');
        }

        fwrite($tmpHandle, $content);
        rewind($tmpHandle);

        // ヘッダー行をスキップ
        fgetcsv($tmpHandle);

        $rows = [];
        while (($cols = fgetcsv($tmpHandle)) !== false) {
            // 利用日（col 0）が空の行は補足情報（為替レート等）なのでスキップ
            if (empty($cols[0])) {
                continue;
            }

            $rows[] = [
                'memo'   => (string) ($cols[1] ?? ''),
                'amount' => (int) ($cols[4] ?? 0),
            ];
        }

        fclose($tmpHandle);

        return $rows;
    }
}
