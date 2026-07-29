<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\MachineGroup;
use App\Models\MachineSubgroup;
use App\Services\Import\MaterialListImportService;
use Illuminate\Http\Request;

class MaterialImportController extends Controller
{
    public function __construct(private MaterialListImportService $service) {}

    public function index()
    {
        return view('admin.material_imports.index', [
            'counts' => [
                'groups' => MachineGroup::count(),
                'subgroups' => MachineSubgroup::count(),
                'machines' => Machine::count(),
            ],
        ]);
    }

    public function uploadSubgroups(Request $request)
    {
        $path = $this->storeUpload($request);
        $result = $this->service->importSubgroupList($path);

        return $this->redirectWithResult($result, 'Subgroeplijst');
    }

    public function uploadMachines(Request $request)
    {
        $path = $this->storeUpload($request);
        $result = $this->service->importMachineList($path);

        return $this->redirectWithResult($result, 'Unieke materieellijst');
    }

    private function storeUpload(Request $request): string
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:'.(config('boels.import.max_file_size_mb', 20) * 1024)],
        ]);

        return $request->file('file')->store(config('boels.import.storage_path', 'imports'));
    }

    private function redirectWithResult(array $result, string $label)
    {
        if (isset($result['error'])) {
            return redirect()->route('admin.material-imports.index')
                ->with('import_error', "$label: ".$result['error']);
        }

        return redirect()->route('admin.material-imports.index')
            ->with('import_result', ['label' => $label] + $result);
    }
}
