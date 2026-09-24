@extends('layouts.office')

@section('title', 'Co-Work Bluelane | Monitor Settings')

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
                        <span class="text-muted">LINE_CHANNEL_ACCESS_TOKEN:</span>
                        @if ($lineTokenConfigured)
                            <span class="badge bg-success">พร้อมใช้งาน</span>
                        @else
                            <span class="badge bg-danger">ยังไม่ตั้งค่า</span>
                        @endif
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
                            <label class="form-label" for="lineGroup">กลุ่ม LINE ที่จะรับแจ้งเตือน</label>
                            <select class="form-select" id="lineGroup" name="line_chat_source_id">
                                <option value="">— ยังไม่เลือก —</option>
                                @foreach ($lineGroups as $group)
                                    <option value="{{ $group->source_id }}" @selected($selectedLineSourceId === $group->source_id)>
                                        {{ $group->display_name ?: 'กลุ่มไม่มีชื่อ' }}
                                        ({{ \Illuminate\Support\Str::limit($group->source_id, 16) }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                รายการมาจากกลุ่มที่ OA เข้าอยู่แล้ว และมี webhook/ข้อความเข้ามาในระบบ
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">บันทึก</button>
                        </div>
                    </form>

                    <hr>

                    <form method="POST" action="{{ route('monitor.settings.test') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="line_chat_source_id" value="{{ $selectedLineSourceId }}">
                        <button type="submit" class="btn btn-soft-success" @disabled(! $lineTokenConfigured || ! $selectedLineSourceId)>
                            <i class="ri-send-plane-line me-1"></i>
                            ส่งข้อความทดสอบเข้ากลุ่ม
                        </button>
                    </form>
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
                        <li class="mb-2">รีเฟรชหน้านี้ แล้วเลือกกลุ่มจากรายการ</li>
                        <li class="mb-2">เปิดสวิตช์แจ้งเตือน แล้วกดบันทึก</li>
                        <li>กดส่งข้อความทดสอบเพื่อยืนยัน</li>
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
                        <div class="small text-muted mt-2">Timezone: {{ $displayTimezone }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
