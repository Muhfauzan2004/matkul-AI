<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\AIController;
use App\Models\User;
use Illuminate\Http\Request;

$user = User::first();
if (!$user) {
    echo "No user found in DB\n";
    exit(1);
}

$controller = new AIController();

$testCases = [
    'Case 1: Pusing dan mual' => 'saat ini aku lagi sakit pusing dan mual-mual,kira-kira obat apa yang cocok ya',
    'Case 2: Pusing berputar' => 'berputar',
    'Case 3: Luka sobek (typo)' => 'jika saya mengalama luka sobek,apa yang harus dilakukan',
    'Case 4: Luka sobek lengkap' => 'jika saya mengalami luka sobek,apa yang harus dilakukan untuk mengobatinya',
    'Case 5: Luka bakar 20%' => 'jika saya mengalami luka bakar 20%, apa yang harus dilakukan untuk mengobatinya',
    'Case 6: Luka tusuk paku' => 'kaki saya tertusuk paku berkarat di jalan, obatnya apa ya?',
    'Case 7: Memar terbentur' => 'lutut terbentur lantai sampai memar dan bengkak',
    'Case 8: Mimisan' => 'hidung saya tiba-tiba keluar darah mimisan, tolong caranya',
];

foreach ($testCases as $title => $message) {
    echo "====================================================\n";
    echo "TEST: {$title}\n";
    echo "INPUT: {$message}\n";
    echo "----------------------------------------------------\n";

    $request = Request::create('/ai/chat', 'POST', ['message' => $message]);
    $request->setUserResolver(fn() => $user);

    $start = microtime(true);
    try {
        $response = $controller->chat($request);
        $duration = round(microtime(true) - $start, 3);
        $data = json_decode($response->getContent(), true);
        echo "RESPONSE TIME: {$duration}s\n";
        echo "REPLY:\n" . ($data['reply'] ?? json_encode($data)) . "\n";
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    echo "\n";
}
