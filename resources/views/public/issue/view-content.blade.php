<div class="col-lg-12">
    {{-- เช็คแบบ !empty ป้องกันกรณีไม่ได้ส่งตัวแปร $isPreview มาจาก Controller --}}
    @if (!empty($isPreview))
        <div class="alert alert-info mb-3">
            <i class="ri-information-line me-1"></i>
            ข้อมูลด้านล่างเป็นสรุปก่อนบันทึก (ยังไม่ได้บันทึกเข้าระบบ)
        </div>
    @endif
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3 align-items-start">
                <div class="col-12 col-md">
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="ri-bug-line me-2 text-primary"></i>
                        {{ $issue?->title ?? '-' }}
                    </h3>
                    <div class="text-muted small">
                        Issue #{{ $issue?->issue_number ?? '-' }}
                    </div>
                    @php
                        $statusMeta = \App\Models\Issue::getStatusMeta($issue?->status ?? null);
                    @endphp
                    <div class="mt-1">
                        <span class="badge {{ $statusMeta['class'] }}">
                            {{ $statusMeta['label'] }}
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <div class="d-flex justify-content-end">
                        @if (empty($isPreview))
                            {{-- ปุ่มจริง --}}
                        @else
                            <span class="badge bg-warning">Preview Mode</span>
                        @endif
                    </div>
                </div>
            </div>
            <hr class="mt-2 mb-4">
            
            <div class="mb-4">
                {{-- ใช้ ?-> จัดการ Null Safety --}}
                {!! nl2br(e($issue?->firstComment?->comment ?? '-')) !!}
            </div>
            
            @if ($issue?->url)
                <div class="mb-4">
                    <label class="text-muted small d-block mb-1">
                        <i class="ri-links-line me-1"></i> ลิงก์ที่เกี่ยวข้อง
                    </label>
                    <a href="{{ $issue->url }}" target="_blank" class="fw-semibold text-primary">
                        {{ $issue->url }}
                    </a>
                </div>
            @endif
            <hr>
            
            <div class="row">
                <div class="col-md-4">
                    <label class="text-muted small">ผู้สร้าง</label>
                    <div>
                        {{ $issue?->creator?->full_name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="text-muted small">ผู้รับผิดชอบ</label>
                    <div>
                        {{ $issue?->assignee?->full_name ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="text-muted small">วันที่สร้าง</label>
                    <div>
                        {{ $issue?->created_at?->format('d M Y H:i') ?? '-' }}
                    </div>
                </div>

                <div class="col-md-4 mt-3">
                    <label class="text-muted small">ความเร่งด่วน (SLA)</label>
                    <div>
                        {!! $issue && method_exists($issue, 'getPriorityBadgeHtml') ? $issue->getPriorityBadgeHtml() : '-' !!}
                    </div>
                </div>
            </div>
            
            @php
                $issueFiles = array_filter((array) (data_get($issue, 'firstComment.files') ?: []));
            @endphp
            
            @if (count($issueFiles) > 0)
                <hr>
                <label class="text-muted small mb-2">ไฟล์แนบ</label>
                <div class="row">
                    @foreach ($issueFiles as $file)
                        @include('public.issue._file_item', [
                            'file' => $file,
                            'pdfPreview' => true,
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>