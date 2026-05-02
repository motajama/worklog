<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\DB;
use App\Services\FootprintService;
use App\Services\PublicLogService;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

App::boot();

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    echo 'SKIP: pdo_sqlite is not available in this PHP build' . PHP_EOL;
    exit(0);
}

$databasePath = sys_get_temp_dir() . '/worklog-footprint-test.sqlite';
@unlink($databasePath);

$config = config();
$config['database']['default'] = 'sqlite';
$config['database']['connections']['sqlite']['database'] = $databasePath;
App::set('config', $config);

$pdo = DB::connection();
$pdo->exec((string) file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
$pdo->exec((string) file_get_contents(dirname(__DIR__) . '/database/seed.sql'));

$fail = static function (string $message): never {
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
};

$assert = static function (bool $condition, string $message) use ($fail): void {
    if (!$condition) {
        $fail($message);
    }
};

$userId = 1;
$factors = FootprintService::factorsForUser($userId, true);
$assert(count($factors) >= 6, 'seed footprint factors were not created');

$category = DB::selectOne("SELECT id FROM categories WHERE kind = 'work' LIMIT 1");
$assert($category !== null, 'work category missing');

DB::execute(
    "INSERT INTO entries (entry_date, entry_type, title, body, minutes, category_id, visibility, locale)
     VALUES ('2026-05-01', 'achievement', 'No footprint', 'No footprint body', 30, :category_id, 'private', 'cs')",
    ['category_id' => $category['id']]
);
$notRatedId = (int) DB::lastInsertId();
FootprintService::saveItemsForEntry($notRatedId, [], FootprintService::STATUS_NOT_RATED);
$notRated = DB::selectOne('SELECT emissions_status FROM entries WHERE id = :id', ['id' => $notRatedId]);
$assert(($notRated['emissions_status'] ?? '') === 'not_rated', 'empty footprint should be not_rated');

DB::execute(
    "INSERT INTO entries (entry_date, entry_type, title, body, minutes, category_id, visibility, locale)
     VALUES ('2026-05-01', 'achievement', 'Public footprint', 'Public footprint body', 60, :category_id, 'public', 'cs')",
    ['category_id' => $category['id']]
);
$publicId = (int) DB::lastInsertId();

$input = [
    'footprint_factor_id' => [$factors[0]['id'], $factors[1]['id']],
    'footprint_quantity' => ['2', '3'],
];
$validated = FootprintService::validateItems($input, $userId);
$assert($validated['errors'] === [], 'valid footprint items produced validation errors');
FootprintService::saveItemsForEntry($publicId, $validated['items'], $validated['status']);

$saved = DB::selectOne('SELECT emissions_total_kg, emissions_status FROM entries WHERE id = :id', ['id' => $publicId]);
$expectedTotal = (2 * (float) $factors[0]['factor_kg_per_unit']) + (3 * (float) $factors[1]['factor_kg_per_unit']);
$assert(abs((float) $saved['emissions_total_kg'] - $expectedTotal) < 0.0000001, 'multiple item total is wrong');
$assert(($saved['emissions_status'] ?? '') === 'complete', 'complete footprint was not marked complete');

$beforeItem = DB::selectOne('SELECT emissions_kg FROM event_footprint_items WHERE event_id = :event_id ORDER BY id ASC LIMIT 1', ['event_id' => $publicId]);
DB::execute('UPDATE footprint_factors SET factor_kg_per_unit = 999 WHERE id = :id', ['id' => $factors[0]['id']]);
$afterItem = DB::selectOne('SELECT emissions_kg FROM event_footprint_items WHERE event_id = :event_id ORDER BY id ASC LIMIT 1', ['event_id' => $publicId]);
$assert((string) $beforeItem['emissions_kg'] === (string) $afterItem['emissions_kg'], 'factor edit changed historical item snapshot');

DB::execute(
    "INSERT INTO entries (entry_date, entry_type, title, body, minutes, category_id, visibility, locale)
     VALUES ('2026-05-01', 'achievement', 'Private footprint', 'Private footprint body', 60, :category_id, 'private', 'cs')",
    ['category_id' => $category['id']]
);
$privateId = (int) DB::lastInsertId();
FootprintService::saveItemsForEntry($privateId, $validated['items'], $validated['status']);

$publicData = PublicLogService::build();
$publicTitles = [];
foreach ($publicData['month_groups'] as $group) {
    foreach ($group['entries'] as $entry) {
        $publicTitles[] = $entry['title'];
    }
}
$assert(in_array('Public footprint', $publicTitles, true), 'public log did not include public footprint entry');
$assert(!in_array('Private footprint', $publicTitles, true), 'public log leaked private entry');

DB::execute(
    "INSERT INTO recurring_footprint_rules (user_id, factor_id, label, quantity, frequency, start_date, active)
     VALUES (:user_id, :factor_id, 'Daily commute', 10, 'daily', '2026-05-01', 1)",
    [
        'user_id' => $userId,
        'factor_id' => $factors[2]['id'],
    ]
);
FootprintService::generateRecurringInstances('2026-05-01', '2026-05-03');
$countOne = DB::selectOne('SELECT COUNT(*) AS count FROM recurring_footprint_instances');
FootprintService::generateRecurringInstances('2026-05-01', '2026-05-03');
$countTwo = DB::selectOne('SELECT COUNT(*) AS count FROM recurring_footprint_instances');
$assert((int) $countOne['count'] === 3, 'recurring generator produced wrong number of instances');
$assert((int) $countTwo['count'] === 3, 'recurring generator produced duplicates');

echo 'OK: footprint tests passed' . PHP_EOL;
