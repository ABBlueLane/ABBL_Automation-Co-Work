<table class="table table-hover table-bordered table-striped">
    <thead>
        <tr>
            <th width="10%">#</th>
            <th width="20%">No.</th>
            <th width="50%">เรื่อง</th>
            <th width="20%">สถานะ</th>
        </tr>
    </thead>
    <tbody>
        @forelse($issues as $key => $issue)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>
                    <a href="{{ route('issue.view', [$issue->business_id, $issue->id]) }}" class="text-primary">
                        {{ $issue->issue_number }}
                    </a>
                </td>
                <td>
                    <a href="{{ route('issue.view', [$issue->business_id, $issue->id]) }}" class="text-primary">
                        {{ $issue->title }}
                    </a>
                </td>
                <td>
                    @if ($issue->status == 'pending')
                        <span class="badge bg-warning">รอรีวิว</span>
                    @elseif($issue->status == 'in_progress')
                        <span class="badge bg-info">กำลังดำเนินการ</span>
                    @elseif($issue->status == 'waiting_review')
                        <span class="badge bg-primary">รอตรวจ</span>
                    @elseif($issue->status == 'done')
                        <span class="badge bg-success">ดำเนินการแล้ว</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">ไม่พบข้อมูล</td>
            </tr>
        @endforelse
    </tbody>
</table>
