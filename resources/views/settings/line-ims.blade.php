@extends('layouts.office')

@section('title', 'OneClick | รับข้อความสร้าง IMS')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">
                    <i class="ri-chat-settings-line me-1"></i>
                    รับข้อความเพื่อสร้าง IMS
                </h4>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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
                    <h5 class="card-title mb-0">การรับข้อความจากกลุ่ม LINE</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        เมื่อเปิดใช้งาน ระบบจะรับคำสั่งและข้อความจากกลุ่ม LINE เพื่อเริ่มเก็บข้อมูลและสร้าง IMS
                        เมื่อปิด ระบบจะไม่เริ่มสร้าง IMS จากข้อความในกลุ่ม
                    </p>

                    <form id="lineImsSettingsForm" method="POST" action="{{ route('settings.line_ims.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-check form-switch form-switch-lg">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="receptionEnabled"
                                name="reception_enabled"
                                value="1"
                                @checked($receptionEnabled)
                                onchange="this.form.submit()"
                            >
                            <label class="form-check-label fw-semibold" for="receptionEnabled">
                                เปิดรับข้อความเพื่อสร้าง IMS
                            </label>
                        </div>
                        <div class="form-text mt-2">
                            @if ($receptionEnabled)
                                สถานะปัจจุบัน: <span class="badge bg-success">เปิดรับข้อความ</span>
                            @else
                                สถานะปัจจุบัน: <span class="badge bg-secondary">ปิดรับข้อความ</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">รายละเอียด</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li class="mb-2">เปิด: กลุ่มสามารถ @OA เพื่อเริ่มเก็บข้อมูลและสร้าง IMS ได้ตามปกติ</li>
                        <li class="mb-2">ปิด: ไม่ตอบรับการเริ่มสร้าง IMS จากข้อความในกลุ่ม</li>
                        <li>หากกำลังเก็บข้อมูลอยู่ขณะปิด สามารถ @OA แล้วพิมพ์หยุด เพื่อจบรอบนั้นได้</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
