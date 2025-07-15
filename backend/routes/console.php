<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduled-data')->everyMinute();
