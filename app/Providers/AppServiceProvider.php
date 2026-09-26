<?php
namespace App\Providers;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register():void{}
    public function boot():void{Carbon::setLocale(config('app.locale','id'));Paginator::defaultView('pagination');}
}
