<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$machine = \App\Models\MachineStatus::where('machine_ip', '10.10.10.79')->first();
$hik = new \App\Services\HikvisionService();
$hik->setConnection($machine->machine_ip, $machine->username, $machine->password, $machine->port);

$startTime = date('Y-m-d\T00:00:00P'); 
$endTime = date('Y-m-d\T23:59:59P');
$data = json_encode([
    "AcsEventCond" => [
        "searchID" => "1",
        "searchResultPosition" => 0,
        "maxResults" => 10, 
        "major" => 5, 
        "minor" => 0, 
        "startTime" => $startTime,
        "endTime" => $endTime
    ]
]);

$res = $hik->request('/ISAPI/AccessControl/AcsEvent?format=json', 'POST', $data);
print_r(json_decode($res['body'], true));
