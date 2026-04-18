{{-- Recursive partial for rendering nested subtasks at any level --}}
@foreach($subtasks as $sub)
    @php
        // Calculate indentation based on nesting level
        $indentPixels = 28 * $level; // 28px per level
        $connector = str_repeat('  ', $level - 1) . '└─';

        // Determine costs to display (aggregated if has subtasks, direct otherwise)
        $hasSubtasks = $sub->relationLoaded('allSubtasks') && $sub->allSubtasks->count() > 0;
        $displayLaborCost = $hasSubtasks ? ($sub->aggregated_labor_cost ?? 0) : ($sub->labor_cost ?? 0);
        $displayMaterialCost = $hasSubtasks ? ($sub->aggregated_material_cost ?? 0) : ($sub->material_cost ?? 0);
        $displayQuoted = $hasSubtasks ? ($sub->aggregated_quoted_amount ?? 0) : ($sub->quoted_amount ?? 0);
    @endphp

    <tr class="sub-row" data-level="{{ $level }}">
        <td style="padding-left: {{ $indentPixels }}px !important;">
            @if($hasSubtasks)
                <i class="fas fa-folder-open text-muted mr-1" style="font-size:11px;"></i>
            @else
                <span class="sub-connector">{{ $connector }}</span>
            @endif
            <a href="{{ route('tasks.show', $sub) }}" class="text-secondary">{{ $sub->code }}</a>
        </td>
        <td>
            <a href="{{ route('tasks.show', $sub) }}" class="text-secondary">{{ $sub->name }}</a>
            @if($hasSubtasks)
                <small class="text-muted ml-1">({{ $sub->allSubtasks->count() }} sub)</small>
            @endif
        </td>
        <td>
            @if($sub->section)
                <span class="section-tag">{{ $sub->section }}</span>
            @else <span class="text-muted">—</span>
            @endif
        </td>
        <td class="cost-col text-muted">
            @if($displayQuoted)
                ₹{{ number_format($displayQuoted, 2) }}
            @else
                —
            @endif
        </td>
        <td class="progress-col">
            <div class="progress progress-xs mb-1">
                <div class="progress-bar bg-{{ $sub->progress >= 100 ? 'success' : 'info' }}"
                     style="width:{{ $sub->progress }}%"></div>
            </div>
            <small class="text-muted">{{ $sub->progress }}%</small>
        </td>
        <td>
            <span class="badge badge-{{ $priorityColors[$sub->priority] ?? 'secondary' }}" style="font-size:10px;">
                {{ ucfirst($sub->priority) }}
            </span>
        </td>
        <td>
            <span class="badge badge-{{ $statusColors[$sub->status] ?? 'secondary' }}" style="font-size:10px;">
                {{ ucwords(str_replace('_',' ', $sub->status)) }}
            </span>
        </td>
        <td class="cost-col text-muted">₹{{ number_format($displayLaborCost, 2) }}</td>
        <td class="cost-col text-muted">₹{{ number_format($displayMaterialCost, 2) }}</td>
        <td>
            <a href="{{ route('tasks.show', $sub) }}" class="btn btn-xs btn-info" title="View">
                <i class="fas fa-eye"></i>
            </a>
            @can('tasks.edit')
            <a href="{{ route('tasks.edit', $sub) }}" class="btn btn-xs btn-warning" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
            @endcan
        </td>
    </tr>

    {{-- Recursively render this subtask's children --}}
    @if($hasSubtasks)
        @include('tasks.partials.subtask-row', [
            'subtasks' => $sub->allSubtasks,
            'level' => $level + 1,
            'priorityColors' => $priorityColors,
            'statusColors' => $statusColors
        ])
    @endif
@endforeach
