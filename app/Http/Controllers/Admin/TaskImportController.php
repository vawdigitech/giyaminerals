<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TaskImportController extends Controller
{
    public function showImport()
    {
        $projects = Project::orderBy('name')->get();
        return view('tasks.import', compact('projects'));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ---- Main sheet ----
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tasks');

        $columns = [
            'A' => 'Project Code',
            'B' => 'Task Code',
            'C' => 'Task Name',
            'D' => 'Parent Task Code',
            'E' => 'Section',
            'F' => 'Priority',
            'G' => 'Status',
            'H' => 'Progress (0-100)',
            'I' => 'Quoted Amount',
            'J' => 'Labor Cost',
            'K' => 'Material Cost',
            'L' => 'Start Date',
            'M' => 'Due Date',
            'N' => 'Completed Date',
            'O' => 'Description',
        ];

        // Header row
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a5276']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        foreach ($columns as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }
        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

        // Sample rows
        $samples = [
            ['PRJ001', '1.0', 'Master Bedroom', '', 'Carpentry', 'high', 'completed', '100', '15000', '8000', '5000', '2024-01-15', '2024-02-15', '2024-02-10', 'Master bedroom carpentry work'],
            ['PRJ001', '1.1', 'Flooring', '1.0', 'Carpentry', 'medium', 'completed', '100', '5000', '2000', '2500', '2024-01-15', '2024-01-30', '2024-01-28', ''],
            ['PRJ001', '1.2', 'Ceiling Work', '1.0', 'Carpentry', 'medium', 'in_progress', '60', '4000', '1500', '1200', '2024-02-01', '2024-02-20', '', ''],
            ['PRJ001', '2.0', 'Kitchen', '', 'Plumbing', 'high', 'pending', '0', '20000', '0', '0', '2024-03-01', '2024-04-30', '', 'Kitchen renovation'],
        ];

        foreach ($samples as $i => $row) {
            $rowNum = $i + 2;
            foreach (array_values($row) as $colIdx => $value) {
                $colLetter = chr(ord('A') + $colIdx);
                $sheet->setCellValue("{$colLetter}{$rowNum}", $value);
            }
        }

        // Auto-size columns
        foreach (array_keys($columns) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze header
        $sheet->freezePane('A2');

        // ---- Instructions sheet ----
        $info = $spreadsheet->createSheet();
        $info->setTitle('Instructions');

        $infoStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6EAF8']],
        ];
        $info->setCellValue('A1', 'Instructions for Task Import');
        $info->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);

        $instructions = [
            ['Column', 'Required', 'Notes'],
            ['Project Code', 'Yes', 'Must match an existing project code (e.g. PRJ001)'],
            ['Task Code', 'Yes', 'Unique code within the project (e.g. 1.0, 1.1, 2.0)'],
            ['Task Name', 'Yes', 'Name of the task'],
            ['Parent Task Code', 'No', 'Code of the parent task for subtasks. Leave blank for top-level tasks.'],
            ['Section', 'No', 'Section / category of work (e.g. Carpentry, Plumbing)'],
            ['Priority', 'No', 'One of: low, medium, high, critical. Defaults to medium.'],
            ['Status', 'No', 'One of: pending, in_progress, completed, on_hold. Defaults to pending.'],
            ['Progress (0-100)', 'No', 'Percentage complete (0–100). Defaults to 0.'],
            ['Quoted Amount', 'No', 'Estimated/budgeted amount for this task'],
            ['Labor Cost', 'No', 'Actual labour cost already incurred (for historical import)'],
            ['Material Cost', 'No', 'Actual material cost already incurred (for historical import)'],
            ['Start Date', 'No', 'Format: YYYY-MM-DD e.g. 2024-01-15'],
            ['Due Date', 'No', 'Format: YYYY-MM-DD'],
            ['Completed Date', 'No', 'Format: YYYY-MM-DD — required if status is completed'],
            ['Description', 'No', 'Optional description'],
        ];

        foreach ($instructions as $rowIdx => $row) {
            foreach ($row as $colIdx => $val) {
                $colLetter = chr(ord('A') + $colIdx);
                $info->setCellValue("{$colLetter}" . ($rowIdx + 3), $val);
            }
        }
        $info->getStyle('A3:C3')->applyFromArray($infoStyle);
        $info->getColumnDimension('A')->setWidth(22);
        $info->getColumnDimension('B')->setWidth(12);
        $info->getColumnDimension('C')->setWidth(70);

        $info->setCellValue('A21', 'Notes:');
        $info->setCellValue('A22', '• Duplicate task codes within the same project are skipped by default.');
        $info->setCellValue('A23', '• Check "Update existing tasks" on the import page to overwrite existing tasks.');
        $info->setCellValue('A24', '• Parent tasks must appear before their subtasks in the file (or already exist in the system).');
        $info->setCellValue('A25', '• Labor Cost and Material Cost are historical values — they do not affect live work sessions or stock.');

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'task_import_template.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'           => 'required|file|mimes:xlsx,xls|max:10240',
            'update_existing' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $updateExisting = $request->boolean('update_existing', false);

        try {
            $reader = IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, false);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }

        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'The file has no data rows.']);
        }

        // Map headers (case-insensitive)
        $headerRow = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0]);
        $colMap    = [];
        $knownCols = [
            'project code', 'task code', 'task name', 'parent task code', 'section',
            'priority', 'status', 'progress (0-100)', 'quoted amount', 'labor cost',
            'material cost', 'start date', 'due date', 'completed date', 'description',
        ];
        foreach ($knownCols as $col) {
            $idx = array_search($col, $headerRow);
            if ($idx !== false) {
                $colMap[$col] = $idx;
            }
        }

        foreach (['project code', 'task code', 'task name'] as $req) {
            if (!isset($colMap[$req])) {
                return back()->withErrors(['file' => "Missing required column: \"{$req}\". Please use the provided template."]);
            }
        }

        // Cache projects & tasks to minimise queries
        $projects = Project::get()->keyBy(fn($p) => strtolower(trim($p->code)));

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        $dataRows = array_slice($rows, 1);

        // Build a local code→id map so parent tasks added earlier in the import
        // are immediately visible as parents for rows further down the file.
        $createdCodeMap = []; // "project_id:task_code" => task_id

        foreach ($dataRows as $rowNum => $row) {
            $lineNum = $rowNum + 2;

            $projectCode = strtolower(trim((string) ($row[$colMap['project code']] ?? '')));
            $taskCode    = trim((string) ($row[$colMap['task code']] ?? ''));
            $taskName    = trim((string) ($row[$colMap['task name']] ?? ''));

            if (empty($projectCode) && empty($taskCode)) {
                continue; // blank row
            }

            if (empty($projectCode) || !isset($projects[$projectCode])) {
                $errors[] = "Row {$lineNum}: Project code \"{$projectCode}\" not found.";
                continue;
            }
            if (empty($taskCode)) {
                $errors[] = "Row {$lineNum}: Task Code is required.";
                continue;
            }
            if (empty($taskName)) {
                $errors[] = "Row {$lineNum}: Task Name is required.";
                continue;
            }

            $project   = $projects[$projectCode];
            $projectId = $project->id;

            // Parent task
            $parentId       = null;
            $parentCodeRaw  = isset($colMap['parent task code'])
                ? trim((string) ($row[$colMap['parent task code']] ?? ''))
                : '';
            if ($parentCodeRaw !== '') {
                $parentKey = "{$projectId}:{$parentCodeRaw}";
                if (isset($createdCodeMap[$parentKey])) {
                    $parentId = $createdCodeMap[$parentKey];
                } else {
                    $parentTask = Task::where('project_id', $projectId)
                        ->where('code', $parentCodeRaw)
                        ->first();
                    if ($parentTask) {
                        $parentId = $parentTask->id;
                    } else {
                        $errors[] = "Row {$lineNum}: Parent task code \"{$parentCodeRaw}\" not found in project.";
                        continue;
                    }
                }
            }

            // Other fields
            $validPriorities = ['low', 'medium', 'high', 'critical'];
            $validStatuses   = ['pending', 'in_progress', 'completed', 'on_hold'];

            $section      = isset($colMap['section']) ? trim((string) ($row[$colMap['section']] ?? '')) : '';
            $priorityRaw  = strtolower(isset($colMap['priority']) ? trim((string) ($row[$colMap['priority']] ?? '')) : '');
            $statusRaw    = strtolower(isset($colMap['status']) ? trim((string) ($row[$colMap['status']] ?? '')) : '');
            $priority     = in_array($priorityRaw, $validPriorities) ? $priorityRaw : 'medium';
            $status       = in_array($statusRaw, $validStatuses) ? $statusRaw : 'pending';
            $progress     = isset($colMap['progress (0-100)']) ? (int) ($row[$colMap['progress (0-100)']] ?? 0) : 0;
            $progress     = max(0, min(100, $progress));
            $quotedAmount = isset($colMap['quoted amount']) ? (float) ($row[$colMap['quoted amount']] ?? 0) : 0;
            $laborCost    = isset($colMap['labor cost']) ? (float) ($row[$colMap['labor cost']] ?? 0) : 0;
            $materialCost = isset($colMap['material cost']) ? (float) ($row[$colMap['material cost']] ?? 0) : 0;
            $actualAmount = $laborCost + $materialCost;
            $description  = isset($colMap['description']) ? trim((string) ($row[$colMap['description']] ?? '')) : '';

            $startDate     = $this->parseDate($row[$colMap['start date'] ?? null] ?? null);
            $dueDate       = $this->parseDate($row[$colMap['due date'] ?? null] ?? null);
            $completedDate = $this->parseDate($row[$colMap['completed date'] ?? null] ?? null);

            $existing = Task::where('project_id', $projectId)->where('code', $taskCode)->first();
            $key      = "{$projectId}:{$taskCode}";

            if ($existing) {
                if ($updateExisting) {
                    $existing->update([
                        'name'           => $taskName,
                        'parent_id'      => $parentId,
                        'section'        => $section ?: $existing->section,
                        'priority'       => $priority,
                        'status'         => $status,
                        'progress'       => $progress,
                        'quoted_amount'  => $quotedAmount,
                        'labor_cost'     => $laborCost,
                        'material_cost'  => $materialCost,
                        'actual_amount'  => $actualAmount,
                        'description'    => $description ?: $existing->description,
                        'start_date'     => $startDate ?? $existing->start_date,
                        'due_date'       => $dueDate ?? $existing->due_date,
                        'completed_date' => $completedDate ?? $existing->completed_date,
                    ]);
                    $createdCodeMap[$key] = $existing->id;
                    $updated++;
                } else {
                    $createdCodeMap[$key] = $existing->id;
                    $skipped++;
                }
                continue;
            }

            $task = Task::create([
                'project_id'     => $projectId,
                'code'           => $taskCode,
                'name'           => $taskName,
                'parent_id'      => $parentId,
                'section'        => $section,
                'priority'       => $priority,
                'status'         => $status,
                'progress'       => $progress,
                'quoted_amount'  => $quotedAmount,
                'labor_cost'     => $laborCost,
                'material_cost'  => $materialCost,
                'actual_amount'  => $actualAmount,
                'description'    => $description,
                'start_date'     => $startDate,
                'due_date'       => $dueDate,
                'completed_date' => $completedDate,
            ]);

            $createdCodeMap[$key] = $task->id;
            $imported++;
        }

        // First, recalculate aggregated values for all imported/updated tasks
        // This ensures leaf tasks have their aggregated values set before parent recalculation
        foreach (array_values($createdCodeMap) as $taskId) {
            $task = Task::find($taskId);
            if ($task) {
                $task->recalculateAggregatedCosts();
            }
        }

        // Then recalculate parent task aggregated costs
        $affectedParentIds = Task::whereIn('id', array_values($createdCodeMap))
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->unique();
        foreach ($affectedParentIds as $pid) {
            $parent = Task::find($pid);
            if ($parent) {
                $parent->recalculateAggregatedCosts();
                $parent->recalculateProgressFromSubtasks();
            }
        }

        // Finally, recalculate progress and status for affected projects
        $affectedProjectIds = collect(array_keys($createdCodeMap))
            ->map(fn($k) => explode(':', $k)[0])
            ->unique();

        foreach ($affectedProjectIds as $pid) {
            $proj = Project::find($pid);
            if ($proj) {
                $proj->updateProgressFromTasks();
                $proj->updateStatusFromTasks();
            }
        }

        $message  = "Import complete: {$imported} created";
        if ($updated) $message .= ", {$updated} updated";
        if ($skipped) $message .= ", {$skipped} skipped (already exist)";
        if ($errors)  $message .= ', ' . count($errors) . ' errors';

        return redirect()->route('tasks.index')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    private function parseDate($raw): ?string
    {
        if (empty($raw)) {
            return null;
        }
        try {
            if (is_numeric($raw)) {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                return \Carbon\Carbon::instance($dt)->format('Y-m-d');
            }
            return \Carbon\Carbon::parse((string) $raw)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
