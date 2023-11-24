@extends('admin.layout')

@section('content')
    <div id="kt_app_content" class="app-content ">

        <!--begin::Layout-->
        <div class="d-flex flex-column flex-lg-row">
            <!--begin::Content-->
            <div class="flex-lg-row-fluid ms-lg-7 ms-xl-10">
                <!--begin::Messenger-->
                <div class="card" id="kt_chat_messenger">
                    <!--begin::Card header-->
                    <div class="card-header" id="kt_chat_messenger_header">
                        <!--begin::Title-->
                        <div class="card-title">
                            <!--begin::User-->
                            <div class="d-flex justify-content-center flex-column me-3">
                                <a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary me-1 mb-2 lh-1">GPT-4</a>
                            </div>
                            <!--end::User-->
                        </div>
                        <!--end::Title-->
                        <!--begin::Card toolbar-->
                        <div class="card-toolbar">
                            <!--begin::Menu-->
                            <div class="me-n3">
                                <button class="btn btn-sm btn-icon btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="fas fa-ellipsis-vertical fs-2"></i>
                                </button>

                                <!--begin::Menu 3-->
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
                                    <!--begin::Heading-->
                                    <div class="menu-item px-3">
                                        <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">
                                            Ações
                                        </div>
                                    </div>
                                    <!--end::Heading-->

                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3" data-bs-toggle="modal" data-bs-target="#kt_modal_users_search">
                                            Limpar
                                        </a>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu 3-->
                            </div>
                            <!--end::Menu-->
                        </div>
                        <!--end::Card toolbar-->
                    </div>
                    <!--end::Card header-->

                    <!--begin::Card body-->
                    <div class="card-body" id="kt_chat_messenger_body">
                        <!--begin::Messages-->
                        <div id="div-messages" class="scroll-y me-n5 pe-5 h-300px h-lg-auto" data-kt-element="messages" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_header, #kt_app_header, #kt_app_toolbar, #kt_toolbar, #kt_footer, #kt_app_footer, #kt_chat_messenger_header, #kt_chat_messenger_footer" data-kt-scroll-wrappers="#kt_content, #kt_app_content, #kt_chat_messenger_body" data-kt-scroll-offset="5px" style="max-height: 358px;">
                            @foreach(array_reverse($messages) as $message)
                                {!! $message !!}
                            @endforeach
                        </div>
                        <!--end::Messages-->
                    </div>
                    <!--end::Card body-->

                    <!--begin::Card footer-->
                    <div class="card-footer pt-4" id="kt_chat_messenger_footer">
                        <!--begin::Input-->
                        <textarea id="textarea-message" class="form-control form-control-flush mb-3 resize-none" rows="1" data-kt-element="input" placeholder="Escreva uma mensagem"></textarea>
                        <!--end::Input-->

                        <!--begin:Toolbar-->
                        <div class="d-flex justify-content-end">
                            <!--begin::Send-->
                            <button class="btn btn-secondary me-4" type="button" id="btn-execute-message">
                                <span class="indicator-label">Executar</span>
                                <span class="indicator-progress">Aguarde...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                            <button class="btn btn-primary" type="button" id="btn-send-message">
                                <span class="indicator-label">Enviar</span>
                                <span class="indicator-progress">Aguarde...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                            <!--end::Send-->
                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Card footer-->
                </div>
                <!--end::Messenger-->
            </div>
            <!--end::Content-->
        </div>
        <!--end::Layout-->
        <!--begin::Modals-->
    </div>
@endsection

@push('scripts')
    <script>
        var sendMessageButton = document.getElementById('btn-send-message');
        var executeMessageButton = document.getElementById('btn-execute-message');
        $('#btn-send-message').on('click', function (){
            sendMessageButton.setAttribute('data-kt-indicator', 'on'); // Disable button to avoid multiple click
            sendMessageButton.disabled = true;
            executeMessageButton.disabled = true;

            var messageTextarea = $('#textarea-message');
            var form = new FormData();
            form.append('message', messageTextarea.val())
            $.ajax({
                url: '{{ route('admin.messages.store') }}',
                type: 'POST',
                data: form,
                processData: false,
                contentType: false,
                success: function(data){
                    if(data.success){
                        messageTextarea.val(null);
                        $('#div-messages').append(data.messageSend);
                    }else{
                        Swal.fire('Erro', data.message, 'error');
                    }
                    sendMessageButton.removeAttribute('data-kt-indicator');
                    sendMessageButton.disabled = false;
                    executeMessageButton.disabled = false;
                },
                error: function(data){
                    sendMessageButton.removeAttribute('data-kt-indicator');
                    sendMessageButton.disabled = false;
                    executeMessageButton.disabled = false;
                    Swal.fire('Falha', 'Falha ao enviar mensagem, tente novamente', 'error');
                }
            });
        });
        $('#btn-execute-message').on('click', function (){
            executeMessageButton.setAttribute('data-kt-indicator', 'on'); // Disable button to avoid multiple click
            executeMessageButton.disabled = true;
            sendMessageButton.disabled = true;

            var messageTextarea = $('#textarea-message');
            var form = new FormData();
            form.append('message', messageTextarea.val())
            $.ajax({
                url: '{{ route('admin.messages.run') }}',
                type: 'GET',
                data: form,
                processData: false,
                contentType: false,
                success: function(data){
                    if(data.success){
                        messageTextarea.val(null);
                        $('#div-messages').append(data.messageReceive);
                    }else{
                        Swal.fire('Erro', data.message, 'error');
                    }
                    executeMessageButton.removeAttribute('data-kt-indicator');
                    executeMessageButton.disabled = false;
                    sendMessageButton.disabled = false;
                },
                error: function(data){
                    executeMessageButton.removeAttribute('data-kt-indicator');
                    executeMessageButton.disabled = false;
                    sendMessageButton.disabled = false;
                    Swal.fire('Falha', 'Falha ao executar resposta do assistente, tente novamente', 'error');
                }
            });
        });
    </script>
@endpush
