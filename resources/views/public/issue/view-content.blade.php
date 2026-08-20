<div class="col-lg-12">
    {{-- เช็คแบบ !empty ป้องกันกรณีไม่ได้ส่งตัวแปร $isPreview มาจาก Controller --}}
    @if (!empty($isPreview))
        <div class="alert alert-info mb-4">
            <i class="ri-information-line me-1"></i>
            ข้อมูลด้านล่างเป็นสรุปก่อนบันทึก (ยังไม่ได้บันทึกเข้าระบบ)
        </div>
    @endif
    
    <div class="card border-0 rounded-4 mb-4" style="background-color: #fafafa; border: 1px solid #f3f4f6 !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
        <div class="card-body p-4 p-md-5">
            <!-- หัวข้อปัญหา -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                    <i class="ri-information-line fs-5 me-2"></i> หัวข้อปัญหา
                </h6>
                <div class="row g-4 ps-4">
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">โปรเจค</div>
                        <div class="text-dark fw-medium d-flex align-items-center">
                            <i class="ri-building-2-line me-2 text-muted"></i>
                            {{ $issue?->issueProject?->name ?? '-' }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">ระดับความเร่ง</div>
                        <div>
                            {!! $issue && method_exists($issue, 'getPriorityBadgeHtml') ? $issue->getPriorityBadgeHtml() : '-' !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">แจ้งวันที่</div>
                        <div class="text-dark fw-medium">
                            {{ $issue?->created_at?->format('d/m/y • H:i') ?? '-' }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small mb-1">ผู้รับผิดชอบ</div>
                        <div class="text-dark fw-medium d-flex align-items-center">
                            <div class="rounded-circle bg-secondary me-2 overflow-hidden d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                @if($issue?->assignee?->full_name)
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($issue->assignee->full_name) }}&background=random&color=fff&size=24" alt="" class="w-100 h-100 object-fit-cover">
                                @else
                                    <i class="ri-user-line text-white" style="font-size: 12px;"></i>
                                @endif
                            </div>
                            {{ $issue?->assignee?->full_name ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- รายละเอียด -->
            <div class="mb-5 mt-5">
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                    <i class="ri-file-text-line fs-5 me-2"></i> รายละเอียด
                </h6>
                <div class="ps-4 text-dark" style="line-height: 1.7;">
                    {!! nl2br(e($issue?->firstComment?->comment ?? '-')) !!}
                </div>
            </div>

            <div class="row mt-5">
                <!-- แนบลิงค์ -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                        <i class="ri-link-m fs-5 me-2"></i> แนบลิงค์
                    </h6>
                    <div class="ps-4">
                        @if ($issue?->url)
                            <div class="border rounded-3 p-3 bg-white d-flex align-items-center justify-content-between shadow-sm transition-all hover-shadow" style="border-color: #e5e7eb !important;">
                                <div class="d-flex align-items-center overflow-hidden w-100">
                                    <div class="bg-light rounded p-2 me-3 text-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                        <i class="ri-layout-grid-line fs-5"></i>
                                    </div>
                                    <div class="text-dark fw-medium text-truncate">
                                        {{ $issue->url }}
                                    </div>
                                </div>
                                <a href="{{ $issue->url }}" target="_blank" class="text-muted ms-3 p-2 rounded hover-bg-light transition-all flex-shrink-0">
                                    <i class="ri-external-link-line fs-5"></i>
                                </a>
                            </div>
                        @else
                            <div class="text-muted small">-</div>
                        @endif
                    </div>
                </div>

                <!-- แนบไฟล์ -->
                <div class="col-md-6">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                        <i class="ri-attachment-2 fs-5 me-2"></i> แนบไฟล์
                    </h6>
                    <div class="ps-4">
                        @php
                            $issueFiles = array_filter((array) (data_get($issue, 'firstComment.files') ?: []));
                        @endphp
                        
                        @if (count($issueFiles) > 0)
                            <div class="row g-3">
                                @foreach ($issueFiles as $file)
                                    <div class="col-12">
                                        @php
                                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            $url = asset('storage/' . $file);
                                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                            $fileName = basename($file);
                                        @endphp
                                        <a href="{{ $url }}" target="_blank" class="text-decoration-none border rounded-3 p-3 bg-white d-flex align-items-center shadow-sm transition-all hover-shadow" style="border-color: #e5e7eb !important;">
                                            <div class="bg-light rounded p-2 me-3 text-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                                @if ($isImage)
                                                    <i class="ri-image-line fs-5"></i>
                                                @else
                                                    <i class="ri-file-text-line fs-5"></i>
                                                @endif
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="text-dark fw-medium text-truncate mb-1" style="font-size: 0.9rem;" title="{{ $fileName }}">
                                                    {{ $fileName }}
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">
                                                    {{ $ext }}
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small">-</div>
                        @endif
                    </div>
                </div>
            </div>
            
            <style>
                .hover-shadow:hover {
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    transform: translateY(-2px);
                }
                .transition-all {
                    transition: all 0.2s ease-in-out;
                }
                .hover-bg-light:hover {
                    background-color: #f3f4f6;
                }
            </style>
        </div>
    </div>
</div>