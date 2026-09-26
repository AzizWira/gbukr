<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{BankAccount, Batch, Country, GoGroup, ImportRun, Order, Payment, Product, Shipment, ShippingOption, StatusDefinition, Warehouse};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterDataController extends Controller
{
    public function index() { return $this->dataIndex(); }

    public function rateIndex()
    {
        $countries = Country::with('shippingOptions')->orderBy('name')->get();
        $countryUsage = $countries->mapWithKeys(fn (Country $country) => [
            $country->id => $this->countryUsage($country),
        ]);

        return view('owner.master.rate', compact('countries', 'countryUsage'));
    }

    public function dataIndex()
    {
        $countries = Country::with(['shippingOptions', 'warehouses'])->orderBy('name')->get();
        $groups = GoGroup::orderBy('name')->get();
        $banks = BankAccount::orderByDesc('active')->orderBy('bank_name')->get();
        $statuses = StatusDefinition::orderBy('sort_order')->orderBy('id')->get();

        $warehouseUsage = Warehouse::query()->pluck('id')->mapWithKeys(fn ($id) => [
            $id => ['Batch' => Batch::where('warehouse_id', $id)->count()],
        ]);
        $groupUsage = $groups->mapWithKeys(fn (GoGroup $group) => [
            $group->id => $this->groupUsage($group),
        ]);
        $bankUsage = $banks->mapWithKeys(fn (BankAccount $bank) => [
            $bank->id => ['pembayaran' => Payment::where('bank_account_id', $bank->id)->count()],
        ]);
        $statusUsage = $statuses->mapWithKeys(fn (StatusDefinition $status) => [
            $status->id => $this->statusUsage($status),
        ]);

        return view('owner.master.data', compact(
            'countries',
            'groups',
            'banks',
            'statuses',
            'warehouseUsage',
            'groupUsage',
            'bankUsage',
            'statusUsage'
        ));
    }

    public function country(Request $request, $country = null)
    {
        $country = $country ? Country::findOrFail($country) : null;
        $data = $request->validate([
            'code' => ['required', 'string', 'max:8', Rule::unique('countries', 'code')->ignore($country?->id)],
            'name' => ['required', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'max:8'],
            'currency_symbol' => ['required', 'string', 'max:12'],
            'rate' => ['required', 'numeric', 'min:0.0001'],
            'admin_fee_idr' => ['required', 'integer', 'min:0'],
        ]);
        $data['code'] = strtoupper(trim($data['code']));
        $data['currency_code'] = strtoupper(trim($data['currency_code']));
        if (!$country) $data['active'] = true;
        $country ? $country->update($data) : Country::create($data);
        return back()->with('success', 'Data rate dan mata uang disimpan.');
    }

    public function toggleCountry(Country $country)
    {
        $country->update(['active' => !$country->active]);
        return back()->with('success', $country->active ? 'Negara diaktifkan.' : 'Negara dinonaktifkan.');
    }

    public function destroyCountry(Country $country)
    {
        $this->ensureUnused(
            $this->countryUsage($country),
            'Negara ' . $country->name,
            'Nonaktifkan negara jika masih dibutuhkan oleh histori atau hapus data turunannya terlebih dahulu.'
        );
        $country->delete();
        return back()->with('success', 'Negara dan mata uang yang belum pernah dipakai berhasil dihapus permanen.');
    }

    public function shipping(Request $request, $shipping = null)
    {
        $shipping = $shipping ? ShippingOption::findOrFail($shipping) : null;
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'label' => ['required', 'string', 'max:100'],
            'amount_foreign' => ['required', 'numeric', 'min:0'],
        ]);
        $shipping ? $shipping->update($data) : ShippingOption::create($data + ['active' => true]);
        return back()->with('success', $shipping ? 'Opsi shipping diperbarui.' : 'Opsi shipping ditambahkan.');
    }

    public function toggleShipping(ShippingOption $shipping)
    {
        $shipping->update(['active' => !$shipping->active]);
        return back()->with('success', 'Status opsi shipping diperbarui.');
    }

    public function destroyShipping(ShippingOption $shipping)
    {
        // Shipping calculator tidak disimpan sebagai foreign key di transaksi; hasilnya sudah menjadi snapshot Rupiah.
        $shipping->delete();
        return back()->with('success', 'Opsi shipping berhasil dihapus permanen. Histori transaksi lama tidak berubah.');
    }

    public function warehouse(Request $request, $warehouse = null)
    {
        $warehouse = $warehouse ? Warehouse::findOrFail($warehouse) : null;
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse?->id)],
            'name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $warehouse ? $warehouse->update($data) : Warehouse::create($data + ['active' => true]);
        return back()->with('success', $warehouse ? 'Warehouse diperbarui.' : 'Warehouse ditambahkan.');
    }

    public function toggleWarehouse(Warehouse $warehouse)
    {
        $warehouse->update(['active' => !$warehouse->active]);
        return back()->with('success', $warehouse->active ? 'Warehouse diaktifkan.' : 'Warehouse dinonaktifkan.');
    }

    public function destroyWarehouse(Warehouse $warehouse)
    {
        $this->ensureUnused(
            ['Batch' => Batch::where('warehouse_id', $warehouse->id)->count()],
            'Warehouse ' . $warehouse->code,
            'Gunakan Nonaktifkan agar Batch lama tetap menunjuk ke warehouse yang benar.'
        );
        $warehouse->delete();
        return back()->with('success', 'Warehouse yang belum pernah dipakai berhasil dihapus permanen.');
    }

    public function group(Request $request, $group = null)
    {
        $group = $group ? GoGroup::findOrFail($group) : null;
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $group ? $group->update($data) : GoGroup::create($data + ['status' => 'active']);
        return back()->with('success', $group ? 'GO diperbarui.' : 'GO ditambahkan.');
    }

    public function toggleGroup(GoGroup $group)
    {
        $group->update(['status' => $group->status === 'active' ? 'inactive' : 'active']);
        return back()->with('success', $group->status === 'active' ? 'GO diaktifkan.' : 'GO dinonaktifkan.');
    }

    public function destroyGroup(GoGroup $group)
    {
        $this->ensureUnused(
            $this->groupUsage($group),
            'GO ' . $group->name,
            'Gunakan Nonaktifkan agar Batch, order, dan arsip import lama tetap konsisten.'
        );
        $group->delete();
        return back()->with('success', 'GO yang belum pernah dipakai berhasil dihapus permanen.');
    }

    public function bank(Request $request, $bank = null)
    {
        $bank = $bank ? BankAccount::findOrFail($bank) : null;
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:80'],
            'account_number' => ['required', 'string', 'max:80'],
            'account_name' => ['required', 'string', 'max:120'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);
        $duplicate = BankAccount::where('bank_name', $data['bank_name'])
            ->where('account_number', $data['account_number'])
            ->when($bank, fn ($q) => $q->where('id', '!=', $bank->id))
            ->exists();
        if ($duplicate) return back()->withErrors(['account_number' => 'Rekening tersebut sudah terdaftar.'])->withInput();
        $bank ? $bank->update($data) : BankAccount::create($data + ['active' => true]);
        return back()->with('success', $bank ? 'Rekening diperbarui.' : 'Rekening ditambahkan dan langsung aktif.');
    }

    public function toggleBank(BankAccount $bank)
    {
        $bank->update(['active' => !$bank->active]);
        return back()->with('success', 'Rekening ' . ($bank->active ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    public function destroyBank(BankAccount $bank)
    {
        $this->ensureUnused(
            ['pembayaran' => Payment::where('bank_account_id', $bank->id)->count()],
            'Rekening ' . $bank->bank_name . ' ' . $bank->account_number,
            'Gunakan Nonaktifkan agar histori mutasi tetap menunjukkan rekening tujuan pembayaran lama.'
        );
        $bank->delete();
        return back()->with('success', 'Rekening yang belum pernah dipakai berhasil dihapus permanen.');
    }

    public function status(Request $request, $status = null)
    {
        $status = $status ? StatusDefinition::findOrFail($status) : null;
        if (!$status && $request->filled('code')) $request->merge(['code' => Str::slug((string) $request->input('code'), '_')]);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'active' => ['nullable', 'boolean'],
            'code' => [$status ? 'nullable' : 'required', 'string', 'max:60', Rule::unique('status_definitions', 'code')->ignore($status?->id)],
        ]);
        if (!$status) $data['system'] = false; else unset($data['code']);
        $data['active'] = $status?->system ? true : $request->boolean('active');
        $status ? $status->update($data) : StatusDefinition::create($data);
        return back()->with('success', 'Status disimpan.');
    }

    public function destroyStatus(StatusDefinition $status)
    {
        if ($status->system) {
            throw ValidationException::withMessages([
                'delete' => 'Status inti tidak dapat dihapus karena dipakai oleh alur otomatis sistem.',
            ]);
        }

        $this->ensureUnused(
            $this->statusUsage($status),
            'Status ' . $status->label,
            'Nonaktifkan status custom jika masih terdapat histori yang menggunakannya.'
        );
        $status->delete();
        return back()->with('success', 'Status custom yang belum pernah dipakai berhasil dihapus permanen.');
    }

    private function countryUsage(Country $country): array
    {
        return [
            'produk' => Product::where('country_id', $country->id)->count(),
            'Batch' => Batch::where('country_id', $country->id)->count(),
            'tracking' => Shipment::where('country_id', $country->id)->count(),
            'opsi shipping' => ShippingOption::where('country_id', $country->id)->count(),
            'warehouse' => Warehouse::where('country_id', $country->id)->count(),
        ];
    }

    private function groupUsage(GoGroup $group): array
    {
        return [
            'Batch' => Batch::where('go_group_id', $group->id)->count(),
            'order' => Order::where('go_group_id', $group->id)->count(),
            'riwayat import' => ImportRun::where('go_group_id', $group->id)->count(),
        ];
    }

    private function statusUsage(StatusDefinition $status): array
    {
        return [
            'Batch' => Batch::where('status', $status->code)->count(),
            'order' => Order::where('status', $status->code)->count(),
            'tracking' => Shipment::where('status', $status->code)->count(),
            'riwayat status' => DB::table('status_histories')
                ->where('from_status', $status->code)
                ->orWhere('to_status', $status->code)
                ->count(),
        ];
    }

    private function ensureUnused(array $usage, string $label, string $advice): void
    {
        $used = array_filter($usage, fn ($count) => (int) $count > 0);
        if (!$used) return;

        $details = collect($used)
            ->map(fn ($count, $name) => number_format((int) $count, 0, ',', '.') . ' ' . $name)
            ->implode(', ');

        throw ValidationException::withMessages([
            'delete' => $label . ' tidak dapat dihapus permanen karena masih dipakai oleh ' . $details . '. ' . $advice,
        ]);
    }
}
