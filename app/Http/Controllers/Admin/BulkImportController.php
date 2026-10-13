<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkImportLog;
use App\Services\BulkImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkImportController extends Controller
{
    public function __construct(
        protected BulkImportService $importService
    ) {}

    /**
     * Bulk Import Dashboard
     */
    public function index()
    {
        $logs = BulkImportLog::with('user')->latest()->paginate(10);

        return view('admin.import.index', compact('logs'));
    }

    /**
     * Download Sample CSV Template for any entity type
     */
    public function downloadTemplate(string $type)
    {
        $validTypes = ['products', 'inventory', 'locations', 'suppliers'];
        if (!in_array($type, $validTypes, true)) {
            abort(404, 'Invalid template type requested.');
        }

        $content = $this->importService->getSampleTemplate($type);
        $fileName = "wms_template_{$type}.csv";

        return response()->streamDownload(function () use ($content) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
            echo $content;
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Process CSV file upload (Preview or Execute)
     */
    public function process(Request $request)
    {
        $request->validate([
            'import_type' => 'required|string|in:products,inventory,locations,suppliers',
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB limit
            'dry_run' => 'nullable|boolean',
        ]);

        $importType = $request->input('import_type');
        $file = $request->file('file');
        $dryRun = $request->boolean('dry_run');

        $result = $this->importService->import(
            importType: $importType,
            file: $file,
            userId: auth()->id(),
            dryRun: $dryRun
        );

        if ($dryRun) {
            return back()->with('dry_run_result', $result)
                ->with('info', "Dry run complete: {$result['successful_rows']} rows valid, {$result['failed_rows']} rows with errors. No database records were modified.");
        }

        if ($result['status'] === 'completed') {
            return back()->with('import_result', $result)
                ->with('success', "Bulk import successfully processed! Total {$result['successful_rows']} records imported.");
        } elseif ($result['status'] === 'partial') {
            return back()->with('import_result', $result)
                ->with('warning', "Bulk import completed with warnings: {$result['successful_rows']} succeeded, {$result['failed_rows']} failed.");
        } else {
            return back()->with('import_result', $result)
                ->withErrors(['import_error' => "Import failed: {$result['failed_rows']} errors found. Database rolled back."]);
        }
    }

    /**
     * View detailed JSON error logs of a previous import
     */
    public function viewLog(BulkImportLog $log)
    {
        return response()->json([
            'id' => $log->id,
            'import_type' => $log->import_type,
            'file_name' => $log->file_name,
            'status' => $log->status,
            'total_rows' => $log->total_rows,
            'successful_rows' => $log->successful_rows,
            'failed_rows' => $log->failed_rows,
            'created_at' => $log->created_at->toDateTimeString(),
            'error_log' => $log->error_log ?? [],
        ]);
    }
}
