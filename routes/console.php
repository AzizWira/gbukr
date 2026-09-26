<?php use Illuminate\Support\Facades\Schedule; Schedule::command('invoices:refresh-penalties')->dailyAt('00:05'); Schedule::command('orders:mark-unclaimed')->dailyAt('00:15');
