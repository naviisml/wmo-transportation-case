<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('wmo:renew-budgets')->hourly()->withoutOverlapping()->timezone('Europe/Amsterdam');
