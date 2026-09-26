<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\GoGroup;
use App\Services\DataExportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExportController extends Controller
{
    public function index()
    {
        return view('owner.export.index', [
            'groups' => GoGroup::orderBy('name')->get(),
        ]);
    }

    public function go(Request $request, DataExportService $service)
    {
        $data = $request->validate([
            'go_group_id' => ['required', Rule::exists('go_groups', 'id')],
        ]);

        $group = GoGroup::findOrFail($data['go_group_id']);
        $export = $service->exportGo($group);

        return response()->download(
            $export['path'],
            $export['filename'],
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function full(DataExportService $service)
    {
        $export = $service->exportFull();

        return response()->download(
            $export['path'],
            $export['filename'],
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
