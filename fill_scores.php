<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TournamentMatch;
use App\Models\GroupTeam;

echo "Filling scores...\n";

// Ensure group standings are clean before we start if we want accurate results, but let's just proceed
$matches = TournamentMatch::where('stage', 'group')->where('status', 'scheduled')->get();
$count = 0;

foreach($matches as $match) {
    if (($match->group->game_type ?? '') !== 'obstacle' && $match->home_team_id && $match->away_team_id) {
        $match->home_score = rand(0, 5);
        $match->away_score = rand(0, 5);
        $match->resolveResult();
        $count++;
    }
}

echo "Successfully completed $count matches with dummy scores!\n";
