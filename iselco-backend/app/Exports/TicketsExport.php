<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TicketsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $request = $this->request;
        
        return Ticket::query()
            ->with(['requestor', 'department', 'category', 'assignedTo', 'priority'])
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Ticket #',
            'Title',
            'Status',
            'Priority',
            'Department',
            'Category',
            'Requestor',
            'Assigned To',
            'Created At',
            'Resolved At',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_number,
            $ticket->title,
            $ticket->status,
            $ticket->priority?->name,
            $ticket->department?->name,
            $ticket->category?->name,
            $ticket->requestor?->employee_name ?? $ticket->requestor?->username,
            $ticket->assignedTo?->employee_name ?? $ticket->assignedTo?->username,
            $ticket->created_at->format('Y-m-d H:i:s'),
            $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
