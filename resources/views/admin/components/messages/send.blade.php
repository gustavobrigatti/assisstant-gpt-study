<!--begin::Message(template for out)-->
<div class="d-flex justify-content-end mb-10" data-kt-element="template-out">
    <!--begin::Wrapper-->
    <div class="d-flex flex-column align-items-end">
        <!--begin::User-->
        <div class="d-flex align-items-center mb-2">
            <!--begin::Details-->
            <div class="me-3">
                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">Você</a>
            </div>
            <!--end::Details-->
            <!--begin::Avatar-->
            <div class="symbol  symbol-35px symbol-circle ">
                <img alt="{{Auth::user()->name}} {{Auth::user()->lastname ?? ''}}" src="{{Auth::user()->photo == null ? asset('images/default.png') : asset(str_replace('public/','storage/', Auth::user()->photo)) }}">
            </div>
            <!--end::Avatar-->
        </div>
        <!--end::User-->

        <!--begin::Text-->
        <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text">
            {!! nl2br($message) !!}
        </div>
        <!--end::Text-->
    </div>
    <!--end::Wrapper-->
</div>
<!--end::Message(template for out)-->
