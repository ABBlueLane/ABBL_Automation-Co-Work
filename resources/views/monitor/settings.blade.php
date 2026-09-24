@extends('layouts.office')

@section('title', 'OneClick | Monitor Settings')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">
                    <i class="ri-notification-3-line me-1"></i>
                    ตั้งค่าแจ้งเตือน Uptime Monitor
                </h4>
                <a href="{{ route('monitor.index') }}" class="btn btn-sm btn-soft-secondary">
                    <i class="ri-arrow-left-line me-1"></i>
                    กลับไป Dashboard
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ช่องทาง LINE (ใช้ OA จาก .env เดิม)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div>
                            <span class="text-muted">LINE OA:</span>
                            @if ($botDisplayName)
                                <strong>{{ $botDisplayName }}</strong>
                            @else
                                <span class="text-muted">อ่านชื่อไม่ได้</span>
                            @endif
                        </div>
                        <div class="mt-1">
                            <span class="text-muted">LINE_CHANNEL_ACCESS_TOKEN:</span>
                            @if ($lineTokenConfigured)
                                <span class="badge bg-success">พร้อมใช้งาน</span>
                            @else
                                <span class="badge bg-danger">ยังไม่ตั้งค่า</span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('monitor.settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="alertsEnabled" name="alerts_enabled" value="1" @checked($alertsEnabled)>
                                <label class="form-check-label" for="alertsEnabled">เปิดแจ้งเตือน DOWN / RECOVERED</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0" for="lineGroup">กลุ่ม LINE ที่จะรับแจ้งเตือน</label>
                                <button form="refreshGroupsForm" type="submit" class="btn btn-sm btn-soft-primary">
                                    <i class="ri-refresh-line me-1"></i>
                                    ดึงชื่อกลุ่มใหม่
                                </button>
                            </div>
                            <select class="form-select" id="lineGroup" name="line_chat_source_id">
                                <option value="">— ยังไม่เลือก —</option>
                                @foreach ($lineGroups as $group)
                                    @php
                                        $label = $group->display_name
                                            ?: ('กลุ่มไม่มีชื่อ · '.\Illuminate\Support\Str::limit($group->source_id, 18));
                                    @endphp
                                    <option value="{{ $group->source_id }}" @selected($selectedLineSourceId === $group->source_id)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                รายการมาจากกลุ่มที่ OA เคยได้รับ webhook แล้ว — กด “ดึงชื่อกลุ่มใหม่” ถ้ายังขึ้นว่าไม่มีชื่อ
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="statusMessageSuffix">ข้อความต่อท้ายตอนส่งสถานะ / แจ้งเตือน</label>
                            <textarea class="form-control" id="statusMessageSuffix" name="status_message_suffix" rows="4" maxlength="1000" placeholder="เช่น ติดต่อทีม IT: 02-xxx-xxxx หรือ ดูรายละเอียดเพิ่มที่ ...">{{ old('status_message_suffix', $statusMessageSuffix) }}</textarea>
                            <div class="form-text">
                                ข้อความนี้จะถูกแนบท้ายรายงานสถานะและข้อความแจ้ง DOWN/RECOVERED ทุกครั้ง (เว้นว่างได้)
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>

                    <form id="refreshGroupsForm" method="POST" action="{{ route('monitor.settings.refresh-groups') }}" class="d-none">
                        @csrf
                    </form>

                    <hr>

                    <form id="testStatusForm" method="POST" action="{{ route('monitor.settings.test') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="line_chat_source_id" id="testLineSourceId" value="{{ $selectedLineSourceId }}">
                        <button type="submit" class="btn btn-soft-success" id="btnSendStatus" @disabled(! $lineTokenConfigured)>
                            <i class="ri-send-plane-line me-1"></i>
                            ส่งสถานะล่าสุดเข้ากลุ่ม
                        </button>
                    </form>
                    <div class="form-text mt-2">
                        จะดึงสถานะ target ล่าสุดจาก monitor แล้ว push เข้ากลุ่มที่เลือกในช่องด้านบน
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">วิธีผูกกลุ่ม</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-3 ps-3">
                        <li class="mb-2">เชิญ LINE OA เข้ากลุ่มที่ต้องการรับแจ้งเตือน</li>
                        <li class="mb-2">ส่งข้อความใดก็ได้ในกลุ่ม (เพื่อให้ webhook บันทึกกลุ่ม)</li>
                        <li class="mb-2">รีเฟรชหน้านี้ หรือกด “ดึงชื่อกลุ่มใหม่”</li>
                        <li class="mb-2">เลือกกลุ่มจากรายการ แล้วเปิดสวิตช์แจ้งเตือน</li>
                        <li>กด “ส่งสถานะล่าสุดเข้ากลุ่ม” เพื่อทดสอบ</li>
                    </ol>

                    <div class="alert alert-light border mb-0">
                        <div class="fw-semibold mb-1">สถานะตอนนี้</div>
                        <div>กลุ่มที่เลือก:
                            @if ($selectedGroup)
                                <strong>{{ $selectedGroup->display_name ?: $selectedGroup->source_id }}</strong>
                            @else
                                <span class="text-muted">ยังไม่มี</span>
                            @endif
                        </div>
                        <div>จำนวนกลุ่มที่รู้จัก: <strong>{{ $lineGroups->count() }}</strong></div>
                        <div>มีชื่อแล้ว: <strong>{{ $lineGroups->filter(fn ($g) => filled($g->display_name))->count() }}</strong></div>
                        <div class="small text-muted mt-2">Timezone: {{ $displayTimezone }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.getElementById('testStatusForm')?.addEventListener('submit', function (event) {
            const selected = document.getElementById('lineGroup')?.value || '';
            document.getElementById('testLineSourceId').value = selected;

            if (!selected) {
                event.preventDefault();
                Swal.fire('ยังไม่เลือกกลุ่ม', 'เลือกกลุ่ม LINE ก่อนส่งสถานะ', 'warning');
            }
        });
    </script>
@endsection
