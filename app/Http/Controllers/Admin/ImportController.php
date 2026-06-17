<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Services\Import\EntityRegistry;
use App\Services\Import\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function __construct(private ImportService $service) {}

    public function index()
    {
        $jobs = ImportJob::with('user')->latest()->paginate(20);
        return view('admin.imports.index', compact('jobs'));
    }

    public function create()
    {
        return view('admin.imports.create', [
            'entities' => EntityRegistry::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'entity' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:'.(config('boels.import.max_file_size_mb') * 1024)],
        ]);

        $path = $request->file('file')->store(config('boels.import.storage_path'));

        $job = ImportJob::create([
            'user_id' => $request->user()->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'status' => 'mapping',
            'mapping' => ['__entity' => $request->input('entity')],
        ]);

        return redirect()->route('admin.imports.mapping', $job);
    }

    public function mapping(ImportJob $importJob)
    {
        $entity = $importJob->mapping['__entity'] ?? null;
        $config = EntityRegistry::get($entity);
        abort_if(! $config, 422, 'Onbekende entiteit op deze import.');

        $info = $this->service->readHeadersAndPreview($importJob->file_path);
        $suggested = $this->service->suggestMapping($entity, $info['headers']);

        return view('admin.imports.mapping', [
            'job' => $importJob,
            'entity' => $entity,
            'config' => $config,
            'headers' => $info['headers'],
            'preview' => $info['preview'],
            'total_rows' => $info['total_rows'],
            'suggested' => $suggested,
        ]);
    }

    public function storeMapping(Request $request, ImportJob $importJob)
    {
        $fields = $request->input('mapping', []);
        $entity = $importJob->mapping['__entity'];

        $this->service->rememberMapping($entity, $fields, $request->user()->id);

        $importJob->update([
            'mapping' => [
                '__entity' => $entity,
                'fields' => $fields,
            ],
            'sync_mode' => $request->boolean('sync_mode'),
            'status' => 'ready',
        ]);

        return redirect()->route('admin.imports.show', $importJob)->with('status', 'Mapping opgeslagen.');
    }

    public function run(ImportJob $importJob)
    {
        $this->service->run($importJob);
        return redirect()->route('admin.imports.show', $importJob)->with('status', 'Import uitgevoerd.');
    }

    public function show(ImportJob $importJob)
    {
        $importJob->load('rows');
        return view('admin.imports.show', ['job' => $importJob]);
    }
}
