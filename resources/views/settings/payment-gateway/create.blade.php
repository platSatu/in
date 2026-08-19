@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('settings.payment-gateway.store') }}" method="POST">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="gateway" class="mb-2">Payment Gateway</label>
                            <select class="form-select @error('gateway') is-invalid @enderror" id="gateway" name="gateway">
                                <option value="">-- Pilih Gateway --</option>
                                <option value="duitku" {{ old('gateway') === 'duitku' ? 'selected' : '' }}>Duitku</option>
                                <option value="midtrans" {{ old('gateway') === 'midtrans' ? 'selected' : '' }}>Midtrans</option>
                                <option value="ipaymu" {{ old('gateway') === 'ipaymu' ? 'selected' : '' }}>iPaymu</option>
                            </select>
                            @error('gateway')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-sm-6">
                            <label for="environment" class="mb-2">Environment</label>
                            <select class="form-select @error('environment') is-invalid @enderror" id="environment" name="environment">
                                <option value="sandbox" {{ old('environment', 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (testing)</option>
                                <option value="production" {{ old('environment') === 'production' ? 'selected' : '' }}>Production (live)</option>
                            </select>
                            <div class="form-text">URL API yang dipakai beda antara sandbox dan production.</div>
                            @error('environment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div id="credentialFieldsContainer">
                        @foreach ($credentialFields as $gatewayKey => $fields)
                            <div class="gateway-fields" data-gateway="{{ $gatewayKey }}" style="display:none;">
                                <div class="row mb-4">
                                    @foreach ($fields as $fieldKey => $label)
                                        <div class="col-sm-6">
                                            <label for="credentials_{{ $gatewayKey }}_{{ $fieldKey }}" class="mb-2">{{ $label }}</label>
                                            <input type="text"
                                                class="form-control gateway-credential-input @error('credentials.' . $fieldKey) is-invalid @enderror"
                                                id="credentials_{{ $gatewayKey }}_{{ $fieldKey }}"
                                                data-field="{{ $fieldKey }}"
                                                placeholder="{{ $label }}"
                                                value="{{ old('credentials.' . $fieldKey) }}">
                                            @error('credentials.' . $fieldKey)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text mb-3" id="noGatewaySelectedHint">
                        Pilih gateway di atas dulu untuk menampilkan kolom kredensial yang sesuai.
                    </div>

                    {{-- Input tersembunyi ini yang benar-benar dikirim ke server, diisi oleh JS
                         dari field credentials_{gateway}_{field} yang lagi aktif/terlihat. --}}
                    <div id="credentialHiddenInputs"></div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <div class="col-xxl-12 col-xl-8 col-lg-8 col-md-7 mt-xxl-0 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-xxl-12 mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                            name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Jadikan gateway aktif
                                        </label>
                                    </div>
                                    <div class="form-text">Hanya boleh 1 gateway aktif dalam satu waktu.</div>
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Save</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('settings.payment-gateway.index') }}"
                                        class="btn btn-outline-secondary w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
    (function () {
        var gatewaySelect = document.getElementById('gateway');
        var groups = document.querySelectorAll('.gateway-fields');
        var hint = document.getElementById('noGatewaySelectedHint');
        var hiddenContainer = document.getElementById('credentialHiddenInputs');

        function rebuildHiddenInputs() {
            hiddenContainer.innerHTML = '';
            var activeGateway = gatewaySelect.value;
            if (!activeGateway) {
                return;
            }

            var group = document.querySelector('.gateway-fields[data-gateway="' + activeGateway + '"]');
            if (!group) {
                return;
            }

            group.querySelectorAll('.gateway-credential-input').forEach(function (input) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'credentials[' + input.dataset.field + ']';
                hidden.value = input.value;
                hiddenContainer.appendChild(hidden);
            });
        }

        function syncGatewayFields() {
            var selected = gatewaySelect.value;

            groups.forEach(function (group) {
                group.style.display = group.dataset.gateway === selected ? '' : 'none';
            });

            hint.style.display = selected ? 'none' : '';
        }

        gatewaySelect.addEventListener('change', syncGatewayFields);

        document.querySelectorAll('.gateway-credential-input').forEach(function (input) {
            input.addEventListener('input', rebuildHiddenInputs);
        });

        document.getElementById('gateway').closest('form').addEventListener('submit', rebuildHiddenInputs);

        syncGatewayFields();
        rebuildHiddenInputs();
    })();
</script>

@endsection
