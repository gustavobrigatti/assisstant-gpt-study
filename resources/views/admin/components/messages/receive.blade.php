<!--begin::Message(template for in)-->
<div class="d-flex justify-content-start mb-10" data-kt-element="template-in">
    <!--begin::Wrapper-->
    <div class="d-flex flex-column align-items-start">
        <!--begin::User-->
        <div class="d-flex align-items-center mb-2">
            <!--begin::Avatar-->
            <div class="symbol  symbol-35px symbol-circle ">
                <img alt="Pic" src="{{ asset('images/gpt-4.webp') }}">
            </div>
            <!--end::Avatar-->
            <!--begin::Details-->
            <div class="ms-3">
                <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">GPT-4</a>
            </div>
            <!--end::Details-->

        </div>
        <!--end::User-->
        <!--begin::Text-->
        <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start" data-kt-element="message-text">
            {!! nl2br($message) !!}
        </div>
        <!--end::Text-->
    </div>
    <!--end::Wrapper-->
</div>
<!--end::Message(template for in)-->
