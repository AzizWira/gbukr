<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{BankAccount,Country,GoGroup,ShippingOption,StatusDefinition,Warehouse};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    public function index() { return $this->dataIndex(); }

    public function rateIndex()
    {
        return view('owner.master.rate', [
            'countries'=>Country::with('shippingOptions')->orderBy('name')->get(),
        ]);
    }

    public function dataIndex()
    {
        return view('owner.master.data', [
            'countries'=>Country::with(['shippingOptions','warehouses'])->orderBy('name')->get(),
            'groups'=>GoGroup::orderBy('name')->get(),
            'banks'=>BankAccount::orderByDesc('active')->orderBy('bank_name')->get(),
            'statuses'=>StatusDefinition::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function country(Request $request, ?Country $country=null)
    {
        $data=$request->validate([
            'code'=>['required','string','max:8',Rule::unique('countries','code')->ignore($country?->id)],
            'name'=>['required','string','max:100'],
            'currency_code'=>['required','string','max:8'],
            'currency_symbol'=>['required','string','max:12'],
            'rate'=>['required','numeric','min:0.0001'],
            'admin_fee_idr'=>['required','integer','min:0'],
        ]);
        $data['code']=strtoupper(trim($data['code']));
        $data['currency_code']=strtoupper(trim($data['currency_code']));
        $data['active']=true;
        $country ? $country->update($data) : Country::create($data);
        return back()->with('success','Data rate dan mata uang disimpan.');
    }

    public function shipping(Request $request)
    {
        $data=$request->validate(['country_id'=>['required','exists:countries,id'],'label'=>['required','string','max:100'],'amount_foreign'=>['required','numeric','min:0']]);
        ShippingOption::create($data+['active'=>true]);
        return back()->with('success','Opsi shipping ditambahkan.');
    }

    public function toggleShipping(ShippingOption $shipping)
    {
        $shipping->update(['active'=>!$shipping->active]);
        return back()->with('success','Status opsi shipping diperbarui.');
    }

    public function warehouse(Request $request)
    {
        $data=$request->validate(['country_id'=>['required','exists:countries,id'],'code'=>['required','string','max:50','unique:warehouses,code'],'name'=>['nullable','string','max:100'],'notes'=>['nullable','string','max:1000']]);
        Warehouse::create($data+['active'=>true]);
        return back()->with('success','Warehouse ditambahkan.');
    }

    public function group(Request $request)
    {
        $data=$request->validate(['name'=>['required','string','max:120'],'notes'=>['nullable','string','max:1000']]);
        GoGroup::create($data+['status'=>'active']);
        return back()->with('success','GO ditambahkan.');
    }

    public function bank(Request $request)
    {
        $data=$request->validate([
            'bank_name'=>['required','string','max:80'],
            'account_number'=>['required','string','max:80'],
            'account_name'=>['required','string','max:120'],
            'instructions'=>['nullable','string','max:1000'],
        ]);
        $duplicate=BankAccount::where('bank_name',$data['bank_name'])->where('account_number',$data['account_number'])->exists();
        if($duplicate) return back()->withErrors(['account_number'=>'Rekening tersebut sudah terdaftar.'])->withInput();
        BankAccount::create($data+['active'=>true]);
        return back()->with('success','Rekening ditambahkan dan langsung aktif.');
    }

    public function toggleBank(BankAccount $bank)
    {
        $bank->update(['active'=>!$bank->active]);
        return back()->with('success','Rekening '.($bank->active?'diaktifkan':'dinonaktifkan').'.');
    }

    public function status(Request $request, ?StatusDefinition $status=null)
    {
        if (!$status && $request->filled('code')) {
            $request->merge(['code'=>Str::slug((string)$request->input('code'),'_')]);
        }
        $data=$request->validate([
            'label'=>['required','string','max:100'],
            'color'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order'=>['required','integer','min:0','max:9999'],
            'active'=>['nullable','boolean'],
            'code'=>[$status?'nullable':'required','string','max:60',Rule::unique('status_definitions','code')->ignore($status?->id)],
        ]);
        if(!$status){
            $data['system']=false;
        } else {
            unset($data['code']);
        }
        $data['active']=$status?->system ? true : $request->boolean('active');
        $status ? $status->update($data) : StatusDefinition::create($data);
        return back()->with('success','Status disimpan.');
    }
}
