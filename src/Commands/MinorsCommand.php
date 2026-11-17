<?php

namespace JeffersonGoncalves\CcpaMinors\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\CcpaMinors\MinorsManager;

class MinorsCommand extends Command
{
    public $signature = 'ccpa:minors {--json : Print the report as JSON}';

    public $description = 'List users known to be under 16 without an opt-in: do not sell or share their personal information';

    public function handle(MinorsManager $minors): int
    {
        $rows = $minors->withoutOptIn()
            ->map(fn (Model $subject): array => [
                'type' => $subject->getMorphClass(),
                'id' => $subject->getKey(),
                'age' => $minors->age($subject),
                'opt_in_by' => $minors->needsParent($subject) ? 'parent' : 'consumer',
            ])
            ->all();

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT));
        } else {
            $this->components->info(count($rows).' user(s) under 16 without an opt-in.');
            $this->table(['Type', 'ID', 'Age', 'Opt-in by'], $rows);
        }

        return self::SUCCESS;
    }
}
