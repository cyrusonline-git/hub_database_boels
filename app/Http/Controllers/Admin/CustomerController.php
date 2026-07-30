<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Import\CustomerListImportService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CustomerListImportService $service) {}

    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(fn ($w) => $w
                ->where('customer_number', 'like', "%$q%")
                ->orWhere('customer_name', 'like', "%$q%")
                ->orWhere('second_name', 'like', "%$q%")
                ->orWhere('concern_name', 'like', "%$q%")
                ->orWhere('responsible', 'like', "%$q%"));
        }
        if ($request->filled('concern')) {
            $query->where('concern_number', $request->input('concern'));
        }
        if ($request->filled('nace')) {
            $query->where('nace_code', $request->input('nace'));
        }

        $customers = $query->orderBy('customer_name')->paginate(50)->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'concerns' => Customer::whereNotNull('concern_number')
                ->select('concern_number', 'concern_name')->distinct()
                ->orderBy('concern_name')->get(),
            'naceCodes' => Customer::whereNotNull('nace_code')
                ->select('nace_code', 'nace_description')->distinct()
                ->orderBy('nace_code')->get(),
        ]);
    }

    public function show(Customer $customer)
    {
        $concernCustomers = $customer->concern_number
            ? Customer::where('concern_number', $customer->concern_number)
                ->where('id', '!=', $customer->id)
                ->orderBy('customer_name')->get(['id', 'customer_number', 'customer_name'])
            : collect();

        return view('admin.customers.show', compact('customer', 'concernCustomers'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:'.(config('boels.import.max_file_size_mb', 20) * 1024)],
        ]);

        $path = $request->file('file')->store(config('boels.import.storage_path', 'imports'));

        try {
            $result = $this->service->import($path);
        } catch (\Throwable $e) {
            report($e);
            $result = ['error' => 'Verwerking mislukt: '.$e->getMessage()];
        }

        if (isset($result['error'])) {
            return redirect()->route('admin.customers.index')->with('import_error', $result['error']);
        }

        return redirect()->route('admin.customers.index')->with('import_result', $result);
    }
}
