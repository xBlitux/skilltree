<?php
declare(strict_types=1);

// Historical 1:n repair: task membership no longer identifies a unique unit.
// Current scenario_task constraints enforce matching scenario/task membership.
fwrite(STDERR, "ABBRUCH: Die historische Szenarioaufteilung ist seit dem m:n-Umbau abgeloest. Fuer synthetische Neuaufbauten aktuelles Schema, testdata_seed.sql und extend-testdata-organisations.php verwenden.\n");
exit(1);
