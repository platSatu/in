<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm">
               <div class="card-header text-center text-white py-5"
                    style="
                        background: url('{{ asset('frontend/img/bg1.jpg') }}') center center / cover no-repeat;
                    ">
                    <img src="{{ asset('frontend/img/Logo.png') }}" alt="Logo" class="mb-3" style="width: 130px;">
                    <p class="lead text-danger" style="font-size: 1.1rem;"><strong>YOU ARE INVITED TO</strong></p>
                    <img src="{{ asset('frontend/img/text.png') }}" alt="Logo" class="mb-3" style="width: 270px;">
                    <p class="lead text-danger fw-bold mb-3" style="font-size: 1.1rem;">
                        Departure Briefing ~ Alumni Testimonies ~ Yule Ceremony
                    </p>
                    <small class="text-danger">Fill in the details below, and the QR code will be sent to your WhatsApp</small>
                </div>

                <div class="card-body p-4">

                    {{-- Success --}}
                    @if (session('success'))
                        <div class="alert alert-success text-center fw-semibold">
                            ✅ {{ session('success') }}
                        </div>
                    @endif

                    {{-- Error --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('invitation.store') }}" method="POST">
                        @csrf

                        <!-- <div class="mb-3">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="Enter your full name"
                                   >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->
                        <div class="row mb-4 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                Name <span class="text-danger">*</span>
                            </label>

                            <div class="col-md-8">
                                <input type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    placeholder="Enter your full name">

                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                WhatsApp<span class="text-danger">*</span>
                            </label>
                          
                            <div class="col-md-8">
                                <input type="number"
                                    name="handphone"
                                    class="form-control @error('handphone') is-invalid @enderror"
                                    value="{{ old('handphone') }}"
                                    placeholder="Enter your WhatsApp number">

                                @error('handphone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>



                        <!-- <div class="mb-3">
                            <label class="form-label fw-semibold">WhatsApp Number <span class="text-danger">*</span></label>
                            <input type="number" name="handphone"
                                   class="form-control @error('handphone') is-invalid @enderror"
                                   value="{{ old('handphone') }}"
                                   placeholder="Example: 08123456789"
                                   >
                            @error('handphone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->

                        <!-- <div class="mb-3">
                            <label class="form-label fw-semibold">University / Institution</label>
                            <input type="text" name="university"
                                   class="form-control @error('university') is-invalid @enderror"
                                   value="{{ old('university') }}"
                                  >
                            @error('university')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->
                         <div class="row mb-4 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                University / Institution <span class="text-danger">*</span>
                            </label>
                           
                            <div class="col-md-8">
                                <input type="text"
                                    name="university"
                                    class="form-control @error('university') is-invalid @enderror"
                                    value="{{ old('university') }}"
                                    placeholder="Enter your University / Institution">

                                @error('university')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        

                        <!-- <div class="mb-3">
                            <label class="form-label fw-semibold">Program / Major</label>
                            <input type="text" name="program"
                                   class="form-control @error('program') is-invalid @enderror"
                                   value="{{ old('program') }}"
                                   >
                            @error('program')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->
                        <!-- <div class="mb-3">
                            <label class="form-label fw-semibold">Program / Major</label>

                            <select name="program"
                                    class="form-select @error('program') is-invalid @enderror">

                                <option value="">-- Select Program --</option>

                                <option value="Bachelor"
                                    {{ old('program') == 'Bachelor' ? 'selected' : '' }}>
                                    Bachelor
                                </option>

                                <option value="Master"
                                    {{ old('program') == 'Master' ? 'selected' : '' }}>
                                    Master
                                </option>

                                <option value="Chinese Language Program"
                                    {{ old('program') == 'Chinese Language Program' ? 'selected' : '' }}>
                                    Chinese Language Program
                                </option>

                                <option value="Diploma"
                                    {{ old('program') == 'Diploma' ? 'selected' : '' }}>
                                    Diploma
                                </option>

                            </select>

                            @error('program')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->
                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                Program / Major
                            </label>

                          

                            <div class="col-md-8">
                                <select name="program"
                                        class="form-select @error('program') is-invalid @enderror">

                                    <option value="">-- Select Program --</option>

                                    <option value="Bachelor"
                                        {{ old('program') == 'Bachelor' ? 'selected' : '' }}>
                                        Bachelor
                                    </option>

                                    <option value="Master"
                                        {{ old('program') == 'Master' ? 'selected' : '' }}>
                                        Master
                                    </option>

                                    <option value="Chinese Language Program"
                                        {{ old('program') == 'Chinese Language Program' ? 'selected' : '' }}>
                                        Chinese Language Program
                                    </option>

                                    <option value="Diploma"
                                        {{ old('program') == 'Diploma' ? 'selected' : '' }}>
                                        Diploma
                                    </option>

                                </select>

                                @error('program')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- <div class="mb-4">
                            <label class="form-label fw-semibold">Number of Attendees</label>
                            <input type="number" name="number_of_attendes"
                                   class="form-control @error('number_of_attendes') is-invalid @enderror"
                                   value="{{ old('number_of_attendes', 1) }}">
                            @error('number_of_attendes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div> -->
                        <div class="row mb-3 align-items-center">
                            <label class="col-md-4 col-form-label fw-semibold">
                                Number of Attendees
                            </label>

                         

                            <div class="col-md-8">
                                <input type="number"
                                    name="number_of_attendes"
                                    class="form-control @error('number_of_attendes') is-invalid @enderror"
                                    value="{{ old('number_of_attendes', 1) }}"
                                    min="1">

                                @error('number_of_attendes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold text-white">
                            REGISTER & SEND INVITATION TO WHATSAPP
                        </button>
                    </form>

                </div>
                <img src="{{ asset('frontend/img/Logo-uni.png') }}" alt="Logo" class="mb-3" style="">    
                <div class="card-footer text-center text-muted py-3">
                    <small>&copy; {{ date('Y') }} Inagroup. All rights reserved.</small>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
