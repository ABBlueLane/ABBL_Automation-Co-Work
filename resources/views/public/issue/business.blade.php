@extends('layouts.public')
@section('title', 'Select Business | OneClick')
@section('navbar_container', 'container')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0">
                        <h4 class="mb-1">
                            <i class="ri-briefcase-line me-2"></i>
                            เลือกธุรกิจ
                        </h4>
                        <p class="text-muted mb-0 small">
                            กรุณาเลือกธุรกิจที่ต้องการแจ้งปัญหา
                        </p>
                    </div>
                    <div class="card-body pt-4">
                        @forelse ($businesses as $business)
                            <div class="d-grid mb-3">
                                <a href="{{ route('issue.index', $business->id) }}"
                                    class="btn btn-primary btn-lg d-flex align-items-center justify-content-center">
                                    <i class="ri-customer-service-2-line me-2"></i>
                                    แจ้งปัญหา {{ $business->business_name }}
                                </a>
                            </div>
                        @empty
                            <div class="alert alert-warning text-center mb-0">
                                <i class="ri-error-warning-line me-1"></i>
                                ไม่มีธุรกิจที่สามารถแจ้งปัญหาได้
                            </div>
                        @endforelse
                    </div> {{-- card-body --}}
                    <div class="card-footer bg-white border-0 text-start">
                        <a href="{{ route('main') }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i>
                            ย้อนกลับ
                        </a>
                    </div>
                </div> {{-- card --}}
            </div>
        </div>
    </div>
@endsection
